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
use ChromeDevtoolsProtocol\Model\Network\ResponseReceivedEvent;
use ChromeDevtoolsProtocol\Model\Network\SetExtraHTTPHeadersRequest;
use ChromeDevtoolsProtocol\Model\Page\NavigateRequest;
use ChromeDevtoolsProtocol\Model\Runtime\EvaluateRequest;
use ChromeDevtoolsProtocol\Model\Security\CertificateErrorActionEnum;
use ChromeDevtoolsProtocol\Model\Security\CertificateErrorEvent;
use ChromeDevtoolsProtocol\Model\Security\HandleCertificateErrorRequest;
use ChromeDevtoolsProtocol\Model\Security\SetOverrideCertificateErrorsRequest;
use Psr\Http\Message\ResponseInterface;

/**
 * Provide a driver that can run Javascript by using headless chrome.
 *
 * @link https://chromedevtools.github.io/devtools-protocol/1-2/
 * @link https://stackoverflow.com/questions/55441105/running-google-chrome-headless-with-php-exec-doesn-t-return-output-till-iis-rest
 * @link https://github.com/jakubkulhan/chrome-devtools-protocol/blob/master/test/ChromeDevtoolsProtocol/Domain/NetworkDomainTest.php
 */
final class ChromeDriver extends RequestDriver {

  /**
   * @var mixed|null
   */
  protected $location;

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
  public function request(): RequestDriverInterface {

    // Context creates deadline for operations.
    $this->ctx = Context::withTimeout(Context::background(), static::SERVER_TIMEOUT);

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
        $this->handleCertificatePolicy();
        $this->devtools->dom()->enable($this->ctx);
        $this->devtools->page()->enable($this->ctx);
        $this->devtools->css()->enable($this->ctx);
        $this->devtools->network()->enable($this->ctx, EnableRequest::make());

        /** @var \ChromeDevtoolsProtocol\Model\Network\Response $response */
        $this->evResponse = NULL;
        $this->devtools->network()
          ->addResponseReceivedListener(function (ResponseReceivedEvent $ev) {
            // This will will be called potentially many times, but it's the
            // first response that we want to deal with.
            $this->evResponse = $this->evResponse ?? $ev->response;
          });

        $request_headers = $this->getHeaders();
        if ($request_headers) {
          $this->devtools->network()
            ->setExtraHTTPHeaders($this->ctx, SetExtraHTTPHeadersRequest::builder()
              ->setHeaders(Headers::fromJson($request_headers))
              ->build());
        }

        $this->devtools->page()->navigate($this->ctx, NavigateRequest::builder()
          ->setUrl($this->url)
          ->build());
        $this->devtools->page()->awaitLoadEventFired($this->ctx);
        $page_contents = $this->getPage();

        if (!$this->evResponse) {
          throw new \RuntimeException('No response.');
        }
        $response_headers = $this->evResponse->headers->all();

        if ($this->styleRequests) {
          foreach ($this->styleRequests as $selector => &$request) {
            $request = $this->getStyle($selector);
          }

          // By passing as a custom header, we are able to still utilize the
          // ResponseInterface.
          $response_headers['X-Computed-Styles'] = json_encode($this->styleRequests);
        }

        // In order to get the entire URL including the fragment we evaluate
        // using JS, it did not seem possible to get this from the response,
        // which didn't include the fragment.
        $this->location = $this->devtools->runtime()
          ->evaluate($this->ctx, EvaluateRequest::builder()
            ->setExpression('window.location.href')
            ->build())->result->value;

        if ($this->javascriptEvals) {
          foreach ($this->javascriptEvals as &$eval) {
            $eval['result'] = $this->devtools->runtime()
              ->evaluate($this->ctx, EvaluateRequest::builder()
                ->setExpression($eval['eval'])
                ->build())->result->value;
          }

          // By passing as a custom header, we are able to still utilize the
          // ResponseInterface.
          $response_headers['X-Javascript-Evals'] = json_encode($this->javascriptEvals);
        }
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

    $this->response = new Response(
      $page_contents,
      $this->evResponse->status,
      array_map(function ($item) {
        return explode("\n", $item);
      }, $response_headers ?? [])
    );

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse(): ResponseInterface {
    return $this->response;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocation(): string {
    return $this->location;
  }

  public function getRedirectCode(): int {

    // TODO Get redirect code from $this->response; is it possible?
    // Not sure how to get the redirect code with the ChromeDriver, but we do
    // know it with the GuzzleDriver.  Maybe this can be figured out some day.
    $redirect_driver = new GuzzleDriver();
    foreach ($this->getHeaders() as $key => $value) {
      $redirect_driver->setHeader($key, $value);
    }
    $redirect_driver
      ->setMethod($this->method)
      ->setUrl($this->url)
      ->setBody($this->body)
      ->request()
      ->getResponse();

    return $redirect_driver->getRedirectCode();
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
   * @link https://github.com/jakubkulhan/chrome-devtools-protocol/blob/master/test/ChromeDevtoolsProtocol/DevtoolsClientTest.php
   */
  private function handleCertificatePolicy() {
    if (!$this->allowInvalidCertificate()) {
      return;
    }
    $this->devtools->security()->enable($this->ctx);
    $this->devtools->security()->setOverrideCertificateErrors(
      $this->ctx,
      SetOverrideCertificateErrorsRequest::builder()
        ->setOverride(TRUE)
        ->build()
    );
    $this->devtools->security()
      ->addCertificateErrorListener(function (CertificateErrorEvent $ev) {
        $this->devtools->security()->handleCertificateError(
          $this->ctx,
          HandleCertificateErrorRequest::builder()
            ->setEventId($ev->eventId)
            ->setAction(CertificateErrorActionEnum::CONTINUE)
            ->build()
        );
      });
  }

}
