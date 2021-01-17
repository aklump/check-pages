<?php

namespace AKlump\CheckPages;

use ChromeDevtoolsProtocol\Context;
use ChromeDevtoolsProtocol\Instance\Launcher;
use ChromeDevtoolsProtocol\Model\DOM\GetDocumentRequest;
use ChromeDevtoolsProtocol\Model\DOM\GetOuterHTMLRequest;
use ChromeDevtoolsProtocol\Model\Page\NavigateRequest;
use Psr\Http\Message\ResponseInterface;

/**
 * Provide a driver that can run Javascript by using headless chrome.
 *
 * @link https://chromedevtools.github.io/devtools-protocol/1-2/
 * @link https://stackoverflow.com/questions/55441105/running-google-chrome-headless-with-php-exec-doesn-t-return-output-till-iis-rest
 */
final class ChromeDriver extends GuzzleDriver {

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

    // TODO Get headers with Chrome.
    // TODO Get status with Chrome.

    // Context creates deadline for operations.
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
        $this->devtools->page()->enable($this->ctx);
        $this->devtools->page()->navigate($this->ctx, NavigateRequest::builder()
          ->setUrl($this->url)
          ->build());
        $this->devtools->page()->awaitLoadEventFired($this->ctx);
        $page_contents = $this->getPage();

        // TODO Get the computed CSS for elements.
        // TODO Handle a click before getting page.

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
      $response->getHeaders()
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

}
