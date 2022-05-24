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

          $test = $event->getTest();
          $path = str_replace('\\', '_', $test);
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
