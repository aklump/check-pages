<?php

namespace AKlump\CheckPages\Browser;

use AKlump\CheckPages\DataStructure\ContentTypeHeader;
use AKlump\CheckPages\DataStructure\HttpHeader;
use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\HttpMessageEvent;
use AKlump\CheckPages\Exceptions\RequestTimedOut;
use AKlump\CheckPages\Output\Icons;
use AKlump\CheckPages\Output\Message\Message;
use AKlump\CheckPages\Output\Message\YamlMessage;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\CheckPages\Response;
use AKlump\CheckPages\Service\Assertion;
use AKlump\CheckPages\Messaging\MessageType;
use Exception;
use HeadlessChromium\BrowserFactory;
use AKlump\CheckPages\Exceptions\JavaScriptException;
use HeadlessChromium\Exception\OperationTimedOut;
use HeadlessChromium\Page;
use Symfony\Component\Console\Terminal;
use Symfony\Component\EventDispatcher\EventDispatcher;

/*
 * @url https://github.com/chrome-php/chrome
 * @link https://chromedevtools.github.io/devtools-protocol/1-2/
 * @link https://stackoverflow.com/questions/55441105/running-google-chrome-headless-with-php-exec-doesn-t-return-output-till-iis-rest
 */

final class ChromeDriver extends RequestDriver implements HeadlessBrowserInterface {

  private string $responseBody;

  private array $styleRequests;

  private array $jsEvaluations;

  private Page $page;

  private array $limits;

  private array $deviceOverrides;

  public function __construct(EventDispatcher $dispatcher) {
    parent::__construct($dispatcher);
    // Set the max memory to 50% of the allowed to prevent out-of-memory errors.
    $this->limits = [
      'php_memory_limit' => intval(ini_get('memory_limit')),
      'test_use_allocation' => 0.5,
    ];
    $this->limits['memory'] = intval($this->limits['php_memory_limit'] * 1000000 * $this->limits['test_use_allocation']);
  }

  /**
   * {@inheritdoc}
   */
  public function addJavaScriptEval(string $expression): HeadlessBrowserInterface {
    // It's very important to use the key so as not to duplicate the
    // evaluations, duplicated evaluations lead to funny results.  We hash the
    // key because super long $expressions.
    $key = md5($expression);
    $this->jsEvaluations[$key] = [
      'eval' => $expression,
      'result' => NULL,
    ];

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addStyleRequest(string $domQuerySelector, string $styleName): HeadlessBrowserInterface {
    $this->styleRequests[$domQuerySelector][] = $styleName;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getComputedStyles(): array {
    $escapeChars = function ($value) {
      return str_replace('"', '\"', $value);;
    };

    $computedStyles = [];
    foreach (($this->styleRequests ?? []) as $selector => $styleNames) {
      $javaScriptSelector = $escapeChars($selector);
      foreach (array_unique($styleNames) as $styleName) {
        $javaScriptCode = sprintf(
          'window.getComputedStyle(document.querySelector("%s"))["%s"]',
          $javaScriptSelector,
          $escapeChars($styleName)
        );
        try {
          $computedStyles[$selector][$styleName] = $this->evaluateJavaScript($javaScriptCode);
        }
        catch (Exception $e) {
          $class = get_class($e);
          // Catch exceptions long enough to add context.
          $message = sprintf('%s.  Evaluated code: %s', $e->getMessage(), $javaScriptCode);
          throw new $class($message, $e->getCode(), $e);
        }
      }
    }

    return $computedStyles;
  }

  /**
   * {@inheritdoc}
   */
  public function request(array $assertions = []): RequestDriverInterface {
    $this->response = NULL;
    $browserFactory = new BrowserFactory();

    // starts headless chrome
    $browser = $browserFactory->createBrowser([
      'ignoreCertificateErrors' => $this->allowInvalidCertificate(),
      'headers' => array_map(function ($header) {
        // TODO Sort out the not-need for name 'foo', make it optional perhaps?
        return (string) (new HttpHeader('foo', $header));
      }, $this->getHeaders()),
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

      $page_url = (string) $this->getUri();
      if (!empty($this->body)) {
        $content_type = (string) (new ContentTypeHeader($this->getHeader('content-type')));
        if ($content_type !== 'application/x-www-form-urlencoded') {
          throw new \RuntimeException(sprintf('Only application/x-www-form-urlencoded is supported for request bodies with %s.', __CLASS__));
        }
        $page_url = (string) $this->getUri()->withQuery($this->body);
      }

      $javaScriptResults = [];
      $computedStyles = [];

      /**
       * Take a snapshot of the browser window
       */
      try {
        $this->page->navigate($page_url)
          ->waitForNavigation(Page::LOAD, $this->getRequestTimeout() * 1000);
        $this->runAssertions($response, $assertions);
        $computedStyles = $this->getComputedStyles();
        $javaScriptResults = $this->jsEvaluations ?? [];
        foreach ($javaScriptResults as &$jsResult) {
          $jsResult['result'] = $this->evaluateJavaScript($jsResult['eval']);
        }
        unset($jsResult);
      }
      catch (\Exception $exception) {
        $this->getMessenger()->writeln(new Message([
          $exception->getMessage(),
        ], MessageType::ERROR, Verbosity::NORMAL));
      }

      /**
       * Read the requested CSS
       *
       * By passing as a custom header, we are able to still utilize the ResponseInterface because the called need only access this header.
       */
      $response['headers']['X-Computed-Styles'] = $computedStyles ? json_encode($computedStyles) : '';

      /**
       * Read the Javascript evaulated results
       */
      $response['headers']['X-JavaScript-Evals'] = $javaScriptResults ? json_encode($javaScriptResults) : '';

      /**
       * Create a response object
       */
      $this->response = new Response(
        $this->responseBody,
        $response['status'] ?? 0,
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
  public function setViewport(int $width = NULL, int $height = NULL, float $pixelRatio = NULL): HeadlessBrowserInterface {
    if (!is_null($width)) {
      $this->deviceOverrides['width'] = $width;
    }
    if (!is_null($height)) {
      $this->deviceOverrides['height'] = $height;
    }
    if (!is_null($pixelRatio)) {
      $this->deviceOverrides['deviceScaleFactor'] = $pixelRatio;
    }

    return $this;
  }

  /**
   * Return the entire page HTML.
   *
   * @param array $response
   * @param Assertion[] $assertions
   *
   * @return string
   *   HTML for the page.
   * @throws \HeadlessChromium\Exception\CommunicationException
   * @throws \HeadlessChromium\Exception\JavascriptException
   */
  private function runAssertions(array $response, array $assertions = []): string {
    $messenger = $this->getMessenger();
    $unevaluatedAssertionCount = 0;
    $waitingMessage = '';
    $initialWaitingMessage = 'Waiting for assertion(s) to evaluate.';
    $subsequentMessages = [
      '...still waiting',
      'TICK TOCK',
      'hmmmmm',
      'Nothing to see here.',
      'Well alrighty, then!',
      'um...',
    ];
    $nextMessageTime = NULL;
    $this->limits['time'] = time() + $this->getRequestTimeout();
    $fireMessageOn = time() + 10;

    $io = $this->getTest()->getRunner()->io();
    $bar = $io->createProgressBar();

    $this->getTest()->echoMessages();
    $bar->start();

    do {
      $this->responseBody = $this->page->getHtml();
      $assertions = array_filter($assertions, function (Assertion $assertion) {
        // Only keep those that have not yet passed.  We'll try again as long as
        // there is time left.
        return $assertion->reRunAgainst($this->responseBody) === FALSE;
      });
      $remainingTime = max(0, $this->limits['time'] - time());
      $remaining_memory = max(0, $this->limits['memory'] - memory_get_usage());
      // Fire this at once, then possibly once more to help debug if the request
      // is timing out.  Given an AJAX context the first and last $this->responseBody
      // could be different.  Capturing both of these states will be the most
      // helpful to the failing test debugging.
      if (time() > $fireMessageOn && $remainingTime > 0 && $remaining_memory > 0) {
        $this->getDispatcher()
          ->dispatch(new HttpMessageEvent(
            $response['headers'],
            $this->responseBody,
            $response['status'],
            $this->getTest(),
          ), Event::RESPONSE_RECEIVED);
        $fireMessageOn = time() + 10;
      }

      // TODO Instead of using messenger here, we should be firing some kind of "waiting for assert in DOM to load" event.
      // TODO We should be able to remove the messenger property for simplification?
      if ($messenger) {
        if (is_null($nextMessageTime) || $remainingTime < $nextMessageTime) {
          $nextMessageTime = $remainingTime - 10;
          $count = count($assertions);
          // Only print the config if the assertion list changes, so the user
          // knows for what he is waiting.
          if ($unevaluatedAssertionCount !== $count) {
            foreach ($assertions as $assertion) {
              $message = new YamlMessage($assertion->getConfig());
              $messenger->deliver($message);
            }
            $unevaluatedAssertionCount = $count;
          }

          if ($waitingMessage) {
            $bar->setMessage($waitingMessage);
            $waitingMessage = $waitingMessage ? $subsequentMessages[array_rand($subsequentMessages)] : $initialWaitingMessage;
            $bar->advance();
          }

          //          // This is designed to only show on the second pass and beyond
          //          if ($waitingMessage) {
          //            $messenger->deliver((new Message([$waitingMessage], MessageType::INFO, Verbosity::VERBOSE))->setIcon(Icons::WAIT));
          //          }
          //          $waitingMessage = $waitingMessage ? $subsequentMessages[array_rand($subsequentMessages)] : $initialWaitingMessage;
        }
      }
      if ($remainingTime <= 0) {
        if ($messenger) {
          $messenger->deliver(new Message([
            sprintf('%srequest.timeout reached.', Icons::NO),
          ], MessageType::ERROR, Verbosity::NORMAL));
          $messenger->deliver(new Message([
            sprintf('%sUse `request.time` in your test with a value > %s', Icons::HINT, $this->getRequestTimeout()),
          ], MessageType::TODO, Verbosity::VERY_VERBOSE));
        }
        break;
      }
      if ($remaining_memory <= 0) {
        if ($messenger) {
          $messenger->deliver(new Message([
            sprintf('%sMemory limit for testing was reached.', Icons::NO),
          ], MessageType::ERROR, Verbosity::NORMAL));
          $messenger->deliver(new Message([
            sprintf('%sTry increasing PHP\'s memory_limit > %s.', Icons::HINT, $this->limits['php_memory_limit']),
          ], MessageType::TODO, Verbosity::VERY_VERBOSE));
        }
        break;
      }
    } while (!empty($assertions));

    $bar->finish();
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedMethods(): array {
    return ['get'];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluateJavaScript(mixed $javaScriptCode): string {
    try {
      return $this->page->evaluate($javaScriptCode)->getReturnValue();
    }
    catch (\Exception $exception) {
      throw new JavaScriptException($exception->getMessage(), $exception->getCode(), $exception);
    }
  }

}
