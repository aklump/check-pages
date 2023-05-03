<?php

namespace AKlump\CheckPages\Output;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\DriverEventInterface;
use AKlump\CheckPages\Event\SuiteEventInterface;
use AKlump\CheckPages\Files\FilesProviderInterface;
use AKlump\CheckPages\Parts\Suite;
use AKlump\Messaging\MessageType;
use Mimey\MimeTypes;
use PrettyXml\Formatter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
   * @var string[]
   */
  private static $mime_types = [
    'text/html',
    'application/json',
    'application/xml',
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
            $event->getRunner()->getLogFiles()->tryEmptyDir($absolute_path);
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

          $regex = '/' . implode('|', array_map(function ($item) {
              return preg_quote($item, '/');
            }, self::$mime_types)) . '/i';
          if (!preg_match($regex, $content_type, $mime_type)) {
            return;
          }

          $mime_type = $mime_type[0];
          $mimes = new MimeTypes();

          $test = $event->getTest();
          $path_by_suite = self::getRelativePathBySuite($test->getSuite());
          $relative_path = dirname($path_by_suite);
          // We cannot use the test ID as a sequence ID because it cannot be
          // guaranteed to be unique or not empty, since tests may be inserted
          // during bootstrap, resulting in test IDs that are out of sequence.
          // Therefore we use our own ad hoc sequence and we put it at the front
          // to ensure our directory listings appear in sequential order.
          $relative_path .= '/' . str_pad(self::$counter++, 3, 0, STR_PAD_LEFT);
          $relative_path .= '_' . basename($path_by_suite);
          // The test ID is helpful, but not guaranteed to be there.
          $relative_path .= rtrim('_' . $test->id(), '_');
          $relative_path .= '.' . $mimes->getExtension($mime_type);

          $content = $event->getDriver()->getResponse()->getBody();
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
          $absolute_path = $runner->writeToFile($relative_path, [$content], 'w+');
          $test->addMessage(new Message([$absolute_path], MessageType::TODO, Verbosity::VERBOSE));
        },
        -1,
      ],
    ];
  }

}
