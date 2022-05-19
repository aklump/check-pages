<?php

namespace AKlump\CheckPages\Output;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\DriverEventInterface;
use Mimey\MimeTypes;
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
  private static $mime_types = ['text/html'];

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Event::REQUEST_FINISHED => [
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
          $filepath = str_replace('\\', '_', $test);
          $filepath .= '.' . $mimes->getExtension($mime_type);

          $content = $event->getDriver()->getResponse()->getBody();
          $test->getRunner()->writeToFile($filepath, [$content], 'w+');
        },
      ],
    ];
  }
}
