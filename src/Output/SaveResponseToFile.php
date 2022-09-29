<?php

namespace AKlump\CheckPages\Output;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\DriverEventInterface;
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

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Event::SUITE_LOADED => [
        function (Event\SuiteEventInterface $event) {
          $suite = $event->getSuite();
          $suite->getRunner()->deleteFiles([
            'response/' . str_replace('\\', '_', $suite) . '*',
          ]);
          self::$counter = 1;
        },
      ],
      Event::TEST_FINISHED => [
        function (DriverEventInterface $event) {
          $content_type = $event->getDriver()
                            ->getResponse()
                            ->getHeader('content-type')[0] ?? '';

          $regex = '/' . implode('|', array_map(function ($item) {
              return preg_quote($item, '/');
            }, self::$mime_types)) . '/i';
          if (!$content_type
            || !preg_match($regex, $content_type, $mime_type)) {
            return;
          }

          $mime_type = $mime_type[0];
          $mimes = new MimeTypes();

          $path = str_replace('\\', '_', $event->getTest()->getSuite());

          // We cannot use the test ID because it cannot be guaranteed to be
          // sequential, since tests may be inserted during bootstrap, resulting
          // in test IDs that are out of sequence.
          $path .= '_' . str_pad(self::$counter++, 3, 0, STR_PAD_LEFT);
          $path .= '.' . $mimes->getExtension($mime_type);

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
          $filepath = $test->getRunner()
            ->writeToFile('response/' . $path, [$content], 'w+');

          if ($test->hasFailed() || $test->getSuite()
              ->getRunner()
              ->getOutput()
              ->isVerbose()) {
            Feedback::$responseBody->overwrite([
              NULL,
              '├── ' . $filepath,
              NULL,
            ]);
          }
        },
        -1,
      ],
    ];
  }

}
