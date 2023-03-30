<?php

namespace AKlump\CheckPages\Browser;


use AKlump\CheckPages\Exceptions\RequestTimedOut;
use AKlump\CheckPages\Output\Icons;
use AKlump\CheckPages\Output\Message;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\CheckPages\Response;
use AKlump\CheckPages\Service\Assertion;
use AKlump\Messaging\MessageType;
use Exception;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Exception\OperationTimedOut;
use HeadlessChromium\Page;

/*
 * @url https://github.com/chrome-php/chrome
 * @link https://chromedevtools.github.io/devtools-protocol/1-2/
 * @link https://stackoverflow.com/questions/55441105/running-google-chrome-headless-with-php-exec-doesn-t-return-output-till-iis-rest
 */

final class ChromeDriver extends RequestDriver implements HeadlessBrowserInterface {

  /**
   * @var array
   */
  private $styleRequests;

  /**
   * @var array
   */
  private $jsEvaluations;

  /**
   * @var \HeadlessChromium\Page
   */
  private $page;

  /**
   * @var array
   */
  private $limits;

  /**
   * @var array
   */
  private $deviceOverrides;

  public function __construct() {
    // Set the max memory to 50% of the allowed to prevent out of memory errors.
    $this->limits = ['memory' => intval(ini_get('memory_limit')) * 1000000 * 0.5];
  }

  /**
   * {@inheritdoc}
   */
  public function addJavascriptEval(string $expression): HeadlessBrowserInterface {
    // It's very important to use the key so as not to duplicate the
    // evaluations, duplicated evaluations lead to funny results.
    $this->jsEvaluations[$expression] = [
      'eval' => $expression,
      'result' => NULL,
    ];

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addStyleRequest(string $dom_query_selector, string $style_name): HeadlessBrowserInterface {
    $this->styleRequests[$dom_query_selector][] = $style_name;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getComputedStyles(): array {
    $escape_chars = function ($value) {
      return str_replace('"', '\"', $value);;
    };

    $computed_styles = [];
    foreach (($this->styleRequests ?? []) as $selector => $style_names) {
      $js_selector = $escape_chars($selector);
      foreach (array_unique($style_names) as $style_name) {
        $eval = sprintf(
          'window.getComputedStyle(document.querySelector("%s"))["%s"]',
          $js_selector,
          $escape_chars($style_name)
        );
        try {
          $computed_styles[$selector][$style_name] = $this->page->evaluate($eval)
            ->getReturnValue();
        }
        catch (Exception $e) {
          $class = get_class($e);
          // Catch exceptions long enough to add context.
          $message = sprintf('%s.  Evaluated code: %s', $e->getMessage(), $eval);
          throw new $class($message, $e->getCode(), $e);
        }
      }
    }

    return $computed_styles;
  }

  /**
   * {@inheritdoc}
   */
  public function request(array $assertions = NULL): RequestDriverInterface {
    $this->response = NULL;
    $browserFactory = new BrowserFactory();

    // starts headless chrome
    $browser = $browserFactory->createBrowser([
      'ignoreCertificateErrors' => $this->allowInvalidCertificate(),
      'headers' => $this->getHeaders(),
//      'debugLogger' => 'php://stdout',
//      'headless' => FALSE,
    ]);

    try {
      // creates a new page and navigate to an URL
      $this->page = $browser->createPage();
      if (!empty($this->deviceOverrides)) {
        // @link https://chromedevtools.github.io/devtools-protocol/1-2/Emulation/#method-setDeviceMetricsOverride
        $this->page->setDeviceMetricsOverride($this->deviceOverrides);
      }

      $response = [];

      // TODO Pass a session ID?
      $this->page->getSession()
        ->once("method:Network.responseReceived", function (array $params) use (&$response): void {
          $response['status'] = $params['response']['status'] ?? '';
          $response['headers'] = $params['response']['headers'] ?? [];
        });

      $this->page->navigate($this->getUrl())
        ->waitForNavigation(Page::LOAD, $this->getRequestTimeout() * 1000);
      $page_contents = $this->getPageContents($assertions);

      $computed_styles = $this->getComputedStyles();
      if ($computed_styles) {
        // By passing as a custom header, we are able to still utilize the
        // ResponseInterface because the called need only access this header.
        $response['headers']['X-Computed-Styles'] = json_encode($computed_styles);
      }

      $x_javascript_evals = $this->getEvaluatedJavascript();
      if ($x_javascript_evals) {
        $response['headers']['X-Javascript-Evals'] = json_encode($x_javascript_evals);
      }

      $this->response = new Response(
        $page_contents,
        $response['status'],
        array_map(function ($item) {
          return explode("\n", $item);
        }, $response['headers'])
      );
    }
    catch (OperationTimedOut $exception) {
      throw new RequestTimedOut($exception->getMessage(), $exception->getCode(), $exception);
    }
    finally {
      $browser->close();
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setViewport(int $width = NULL, int $height = NULL, float $pixel_ratio = NULL) {
    if (!is_null($width)) {
      $this->deviceOverrides['width'] = $width;
    }
    if (!is_null($height)) {
      $this->deviceOverrides['height'] = $height;
    }
    if (!is_null($pixel_ratio)) {
      $this->deviceOverrides['deviceScaleFactor'] = $pixel_ratio;
    }

    return $this;
  }

  /**
   * Return the entire page HTML.
   *
   * @return string
   *   HTML for the page.
   */
  private function getPageContents(array $assertions = NULL): string {
    $messenger = $this->getMessenger();
    $next_message_time = NULL;
    $this->limits['time'] = time() + $this->getRequestTimeout();
    do {
      $page_contents = $this->page->getHtml();
      $assertions = array_filter($assertions, function (Assertion $assertion) use ($page_contents) {
        // Only keep those that have not yet passed.  We'll try again as long as
        // there is time left.
        return $assertion->runAgainst($page_contents) === FALSE;
      });
      $remaining_time = max(0, $this->limits['time'] - time());
      $remaining_memory = max(0, $this->limits['memory'] - memory_get_usage());

      if ($messenger) {
        if (is_null($next_message_time) || $remaining_time < $next_message_time) {
          $next_message_time = $remaining_time - 10;
          $lines = array_map(function (Assertion $assert) {
            return '├── ' . Icons::CLOCK . 'Waiting for: ' . $assert;
          }, $assertions);
          $messenger->deliver(new Message($lines, MessageType::INFO, Verbosity::VERBOSE));
        }
      }

    } while (!empty($assertions) && $remaining_time > 0 && $remaining_memory > 0);

    return $page_contents;
  }


  /**
   * {@inheritdoc}
   */
  public function getEvaluatedJavascript(): array {
    $evaluated = $this->jsEvaluations ?? [];
    foreach ($evaluated as &$eval) {
      $eval['result'] = $this->page->evaluate($eval['eval'])->getReturnValue();
    }

    return $evaluated;
  }

}
