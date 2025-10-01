<?php

namespace AKlump\CheckPages\EventSubscriber;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\HttpMessageEvent;
use AKlump\CheckPages\Event\SuiteEventInterface;
use AKlump\CheckPages\Files\FilesProviderInterface;
use AKlump\CheckPages\Files\GetShortPath;
use AKlump\CheckPages\Helpers\NormalizeHeaders;
use AKlump\CheckPages\Output\Message\Message;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\CheckPages\Parts\Suite;
use AKlump\CheckPages\Parts\Test;
use AKlump\CheckPages\Traits\HasTestTrait;
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

  use HasTestTrait;

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
      Event::RESPONSE_RECEIVED => [
        function (HttpMessageEvent $event) {
          $test = $event->getTest();
          $handler = new self();
          $handler->setTest($test);
          $runner = $test->getRunner();
          $headers = $event->getHeaders();
          $content_type = $headers['content-type'][0] ?? '';
          $content = $event->getBody();
          $mime_type = $handler->parseMimeFromContentTypeHeader($content_type);
          switch ($content_type) {
            case 'application/xml':
              $formatter = new Formatter();
              $prepared_content_as_string = $formatter->format($content);
              break;

            case 'application/json':
              $prepared_content_as_string = json_encode(json_decode($content), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
              break;

            default:
              $prepared_content_as_string = $content;
              break;
          }
          $base_path = $handler->getOutputBasePath();

          $test->addMessage(new Message([Icons::RESPONSE . Icons::FILE], MessageType::INFO, Verbosity::VERBOSE));

          // Write the headers to a separate file.
          $headers['Status'] = [$event->getStatusCode()];
          $headers = (new NormalizeHeaders())($headers);
          $headers_as_string = Yaml::dump($headers);

          $absolute_path = $runner->writeToFile($base_path . '.headers.yml', [$headers_as_string], 'w+');
          $handler->addFileSavedMessages($absolute_path);

          // Now write the request content as appropriate by mimetype.
          $extensions = self::$mime_types[$mime_type];
          foreach ($extensions as $extension) {
            $absolute_path = $runner->writeToFile($base_path . '.' . $extension, [$prepared_content_as_string], 'w+');
            $handler->addFileSavedMessages($absolute_path);
          }
          $test->echoMessages();
        },
      ],
    ];
  }

  private function addFileSavedMessages(string $path) {
    $short_path = (new GetShortPath(getcwd()))($path);
    $test = $this->getTest();
    $indent = str_repeat(' ', mb_strlen(Icons::RESPONSE . Icons::FILE));
    $test->addMessage(new Message([$indent . $short_path], MessageType::TODO, Verbosity::VERBOSE));
    $test->echoMessages();
  }

  private function parseMimeFromContentTypeHeader(string $content_type): string {
    $regex = '/^(' . implode('|', array_map(function ($item) {
        return preg_quote($item, '/');
      }, array_keys(self::$mime_types))) . ')/i';
    preg_match($regex, $content_type, $mime_type);

    return $mime_type[0] ?? '';
  }

  private function getOutputBasePath(): string {
    $test = $this->getTest();
    $path_by_suite = self::getRelativePathBySuite($test->getSuite());
    $base_path = dirname($path_by_suite);
    // We cannot use the test ID as a sequence ID because it cannot be
    // guaranteed to be unique or not empty, since tests may be inserted
    // during bootstrap, resulting in test IDs that are out of sequence.
    // Therefore, we use our own ad hoc sequence and we put it at the front
    // to ensure our directory listings appear in sequential order.
    static $counters = [];
    $counters[$base_path] ??= -1;
    $counters[$base_path]++;
    $base_path .= '/' . str_pad((string) $counters[$base_path], 3, 0, STR_PAD_LEFT);
    $base_path .= '_' . basename($path_by_suite);
    // The test ID is helpful, but not guaranteed to be there.  So we will add
    // it if possible, and if not make sure we don't end the file in an
    // underscore.
    $base_path .= rtrim('_' . $test->id(), '_');

    return $base_path;
  }

}
