<?php

namespace AKlump\CheckPages;

use ChromeDevtoolsProtocol\Context;
use ChromeDevtoolsProtocol\Exception\ErrorException;
use ChromeDevtoolsProtocol\Instance\Launcher;
use ChromeDevtoolsProtocol\Model\CSS\GetComputedStyleForNodeRequest;
use ChromeDevtoolsProtocol\Model\DOM\GetDocumentRequest;
use ChromeDevtoolsProtocol\Model\DOM\GetOuterHTMLRequest;
use ChromeDevtoolsProtocol\Model\DOM\QuerySelectorRequest;
use ChromeDevtoolsProtocol\Model\Network\EnableRequest;
use ChromeDevtoolsProtocol\Model\Network\Headers;
use ChromeDevtoolsProtocol\Model\Network\SetExtraHTTPHeadersRequest;
use ChromeDevtoolsProtocol\Model\Page\NavigateRequest;
use ChromeDevtoolsProtocol\Model\Runtime\EvaluateRequest;
use Psr\Http\Message\ResponseInterface;

/**
 * Provide a driver that can run Javascript by using headless chrome.
 *
 * @link https://chromedevtools.github.io/devtools-protocol/1-2/
 * @link https://stackoverflow.com/questions/55441105/running-google-chrome-headless-with-php-exec-doesn-t-return-output-till-iis-rest
 */
final class ChromeDriver extends GuzzleDriver {

  /**
   * An array of optional query selectors to fill with computed CSS style info.
   *
   * @var array
   */
  private $styleRequests = [];

  /**
   * An array keyed by expression whose values will be filled with the result.
   *
   * @var array
   */
  private $javascriptEvals = [];

  /**
   * @var \ChromeDevtoolsProtocol\DevtoolsClient
   */
  private $devtools;

  /**
   * @var \ChromeDevtoolsProtocol\Context
   */
  private $ctx;

  /**
   * @var string
   */
  private $pathToChrome;

  /**
   * ChromeDriver constructor.
   *
   * @param string $path_to_chrome
   *   The system path to the chrome binary.  It should NOT be shell escaped,
   *   that will happen automatically.  E.g. '/Applications/Google
   *   Chrome.app/Contents/MacOS/Google Chrome'.
   */
  public function __construct(string $path_to_chrome) {
    if (!$path_to_chrome || !file_exists($path_to_chrome)) {
      throw new \InvalidArgumentException(sprintf('Missing or invalid path to Chrome "%s".', $path_to_chrome));
    }
    $this->pathToChrome = $path_to_chrome;
  }

  /**
   * @inheritDoc
   */
  public function getResponse(): ResponseInterface {

    // We use Guzzle to get the headers and the status code.  Maybe we can do
    // that with Chrome too, but I didn't quickly see a way and  need to move on
    // right now.
    $response = parent::getResponse();
    $headers = $response->getHeaders();

    // TODO Get headers with Chrome.
    // TODO Get status with Chrome.
    // TODO This may help: https://github.com/jakubkulhan/chrome-devtools-protocol/blob/master/test/ChromeDevtoolsProtocol/Domain/NetworkDomainTest.php

    // Context creates deadline for operations.
    // TODO Make the timeout configurable.
    $this->ctx = Context::withTimeout(Context::background(), 30 /* seconds */);

    // Launcher starts chrome process ($instance)
    $launcher = new Launcher();
    $instance = $launcher->launch($this->ctx);

    try {

      // Create a new browser tab with our URL.
      $tab = $instance->open($this->ctx);
      $tab->activate($this->ctx);
      // Open the devtools tab in the browser.
      $this->devtools = $tab->devtools();
      try {
        $this->devtools->dom()->enable($this->ctx);
        $this->devtools->page()->enable($this->ctx);
        $this->devtools->css()->enable($this->ctx);
        $this->devtools->network()->enable($this->ctx, EnableRequest::make());

        $headers = $this->getHeaders();
        if ($headers) {
          $this->devtools->network()
            ->setExtraHTTPHeaders($this->ctx, SetExtraHTTPHeadersRequest::builder()
              ->setHeaders(Headers::fromJson($headers))
              ->build());
        }

        $this->devtools->page()->navigate($this->ctx, NavigateRequest::builder()
          ->setUrl($this->url)
          ->build());
        $this->devtools->page()->awaitLoadEventFired($this->ctx);
        $page_contents = $this->getPage();

        if ($this->styleRequests) {
          foreach ($this->styleRequests as $selector => &$request) {
            $request = $this->getStyle($selector);
          }

          // By passing as a custom header, we are able to still utilize the
          // ResponseInterface.
          $headers['X-Computed-Styles'] = json_encode($this->styleRequests);
        }

        if ($this->javascriptEvals) {
          foreach ($this->javascriptEvals as &$eval) {
            $eval['result'] = $this->devtools->runtime()
              ->evaluate($this->ctx, EvaluateRequest::builder()
                ->setExpression($eval['eval'])
                ->build())->result->value;
          }

          // By passing as a custom header, we are able to still utilize the
          // ResponseInterface.
          $headers['X-Javascript-Evals'] = json_encode($this->javascriptEvals);
        }

        // TODO Handle a click before getting page.

      }
      catch (\Exception $e) {
        $class = get_class($e);

        // Add some additional context to any errors.
        $message = sprintf("Test failure in %s(): %s", __METHOD__, $e->getMessage());
        throw new $class($message, $e->getCode(), $e);
      }
      finally {
        // Close the devtools tab.
        $this->devtools->close();
      }
    }
    finally {
      // Kill the system process that is running the Chrome instance.
      $instance->close();
    }

    return new Response(
      $page_contents,
      $response->getStatusCode(),
      $headers,
    );
  }

  /**
   * Return the entire page HTML.
   *
   * @return string
   *   HTML for the page.
   */
  private function getPage(): string {
    $document = $this->devtools->dom()
      ->getDocument($this->ctx, GetDocumentRequest::make());
    $page_contents = $this->devtools->dom()
      ->getOuterHTML($this->ctx, GetOuterHTMLRequest::fromJson((object) ['nodeId' => $document->root->nodeId]));
    $page_contents = json_encode($page_contents);

    return json_decode($page_contents)->outerHTML;
  }

  /**
   * Get computed style for a query selection.
   *
   * @param string $selector
   *   The query selector to get the style for.
   */
  private function getStyle(string $selector): array {
    $document = $this->devtools->dom()
      ->getDocument($this->ctx, GetDocumentRequest::make());

    try {
      $node = $this->devtools->dom()
        ->querySelector($this->ctx, QuerySelectorRequest::fromJson((object) [
          'nodeId' => $document->root->nodeId,
          'selector' => $selector,
        ]));
      $computed_style = $this->devtools->css()
        ->getComputedStyleForNode($this->ctx, GetComputedStyleForNodeRequest::fromJson((object) [
          'nodeId' => $node->nodeId,
        ]));
      $computed_style = json_decode(json_encode($computed_style), TRUE)['computedStyle'] ?? [];
    }
    catch (ErrorException $exception) {
      $computed_style = [];
    }
    $style = [];
    foreach ($computed_style as $datum) {
      $style[$datum['name']] = $datum['value'];
    }

    return $style;
  }

  /**
   * Make a request for a computed styles value to be retrieved.
   *
   * @param string $query_selector
   *   The query selector.
   *
   * @return $this
   *   Self for chaining.
   */
  public function addStyleRequest(string $query_selector): ChromeDriver {
    $this->styleRequests[$query_selector] = [];

    return $this;
  }

  /**
   * Make a request for a computed styles value to be retrieved.
   *
   * @param string $query_selector
   *   The query selector.
   *
   * @return $this
   *   Self for chaining.
   */
  public function addJavascriptEval(string $expression): ChromeDriver {

    // It's very important to use the key so as not to duplicate the
    // evaluations, duplicated evaluations lead to funny results.
    $this->javascriptEvals[$expression] = [
      'eval' => $expression,
      'result' => NULL,
    ];

    return $this;
  }

}
