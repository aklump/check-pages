<?php

namespace AKlump\CheckPages;

use Psr\Http\Message\ResponseInterface;

/**
 * Provide a driver that can run Javascript by using headless chrome.
 *
 * @link https://stackoverflow.com/questions/55441105/running-google-chrome-headless-with-php-exec-doesn-t-return-output-till-iis-rest
 */
class ChromeDriver extends GuzzleDriver {

  /**
   * @var string
   */
  protected $pathToChrome;

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

    $command = escapeshellarg($this->pathToChrome) . " --headless --disable-gpu --dump-dom " . escapeshellarg($this->url);
    $descriptorspec = [
      0 => ["pipe", "rb"],
      1 => ["pipe", "wb"],
      2 => ["pipe", "wb"],
    ];
    $chrome_process = proc_open($command, $descriptorspec, $pipes);
    if (!$chrome_process) {
      throw new \RuntimeException("failed to create process! \"{$command}\"");
    }
    $stdout = "";
    $stderr = "";
    $fetch = function () use (&$stdout, &$stderr, &$pipes) {
      $tmp = stream_get_contents($pipes[1]);
      if (is_string($tmp) && strlen($tmp) > 0) {
        $stdout .= $tmp;
      }
      $tmp = stream_get_contents($pipes[2]);
      if (is_string($tmp) && strlen($tmp) > 0) {
        $stderr .= $tmp;
      }
    };
    fclose($pipes[0]);

    while (($status = proc_get_status($chrome_process))['running']) {
      $fetch();
    }
    $fetch();
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($chrome_process);

    return new Response(
      $stdout,
      $response->getStatusCode(),
      $response->getHeaders()
    );
  }

}
