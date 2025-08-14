<?php

namespace AKlump\CheckPages\EventSubscriber;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\DriverEventInterface;
use AKlump\CheckPages\Event\SuiteEventInterface;
use AKlump\CheckPages\Files\FilesProviderInterface;
use AKlump\CheckPages\Output\Message\Message;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\CheckPages\Parts\Suite;
use AKlump\CheckPages\Parts\Test;
use AKlump\Messaging\MessageType;
use Mimey\MimeTypes;
use PrettyXml\Formatter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Yaml\Yaml;
use AKlump\CheckPages\Output\Icons;

/**
 * Writes HTTP responses to files for certain mime types.
 */
final class SaveResponseToFile implements EventSubscriberInterface {

  /**
   * @var int
   */
  static $counter;

  /**
   * One or more mime types that if received will be written to file.
   *
   * @var array[] Keyed by the mime type, values are file extension(s) to write.
   * By providing more than one file extension, multiple files will be written.
   * E.g. if you want 'text/html' to write both .txt and .html.
   */
  private static array $mime_types = [
    'text/html' => ['txt', 'html'],
    'application/json' => ['json'],
    'application/xml' => ['xml'],
  ];

  private static function getRelativePathBySuite(Suite $suite): string {
    return 'response/' . $suite->toFilepath();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [

      //
      // Remove previous files.
      //
      Event::SUITE_STARTED => [
        function (SuiteEventInterface $event) {
          $log_files = $event->getSuite()->getRunner()->getLogFiles();
          if (!$log_files) {
            return '';
          }
          $relative_path = self::getRelativePathBySuite($event->getSuite());
          $absolute_path = $log_files->tryResolveDir($relative_path, FilesProviderInterface::RESOLVE_NON_EXISTENT_PATHS)[0];
          if ($absolute_path && is_dir($absolute_path)) {
            $event->getSuite()
              ->getRunner()
              ->getLogFiles()
              ->tryEmptyDir($absolute_path);
          }
        },
      ],

      //
      // Write request responses to file.
      //
      Event::TEST_FINISHED => [
        function (DriverEventInterface $event) {
          try {
            $content_type = $event->getDriver()
                              ->getResponse()
                              ->getHeader('content-type')[0] ?? '';
          }
          catch (\Exception $exception) {
            $content_type = NULL;
          }
          if (!$content_type) {
            return;
          }

          $handler = new self();

          $regex = '/^(' . implode('|', array_map(function ($item) {
              return preg_quote($item, '/');
            }, array_keys(self::$mime_types))) . ')/i';
          if (!preg_match($regex, $content_type, $mime_type)) {
            return;
          }
          $mime_type = $mime_type[0];

          $test = $event->getTest();
          $path_by_suite = self::getRelativePathBySuite($test->getSuite());
          $relative_path = dirname($path_by_suite);
          // We cannot use the test ID as a sequence ID because it cannot be
          // guaranteed to be unique or not empty, since tests may be inserted
          // during bootstrap, resulting in test IDs that are out of sequence.
          // Therefor we use our own ad hoc sequence and we put it at the front
          // to ensure our directory listings appear in sequential order.
          $relative_path .= '/' . str_pad((string) self::$counter++, 3, 0, STR_PAD_LEFT);
          $relative_path .= '_' . basename($path_by_suite);
          // The test ID is helpful, but not guaranteed to be there.
          $relative_path .= rtrim('_' . $test->id(), '_');

          $response = $event->getDriver()->getResponse();
          $content = $response->getBody();
          switch ($content_type) {
            case 'application/xml':
              $formatter = new Formatter();
              $content = $formatter->format($content);
              break;

            case 'application/json':
              $content = json_encode(json_decode($content), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
              break;
          }
          $test = $event->getTest();
          $runner = $test->getRunner();

          // Write the headers to a separate file.
          $headers = $response->getHeaders();
          $headers['Status'] = [
            sprintf('%s %s', $response->getStatusCode(), $response->getReasonPhrase()),
          ];
          ksort($headers);
          $absolute_path = $runner->writeToFile($relative_path . ".headers.yml", [Yaml::dump($headers)], 'w+');
          $handler->addPathSavedMessage($test, $absolute_path);

          // Now write the request content as appropriate by mimetype.
          $extensions = self::$mime_types[$mime_type];
          foreach ($extensions as $extension) {
            $absolute_path = $runner->writeToFile($relative_path . ".$extension", [$content], 'w+');
            $handler->addPathSavedMessage($test, $absolute_path);
          }
        },
        -1,
      ],
    ];
  }

  private function addPathSavedMessage(Test $test, string $path) {
    $test->addMessage(new Message([
      sprintf("%s%s%s", Icons::RESPONSE, Icons::FILE, basename($path)),
    ], MessageType::INFO, Verbosity::VERBOSE));

    $test->addMessage(new Message([
      $path,
    ], MessageType::TODO, Verbosity::DEBUG));
  }

}
