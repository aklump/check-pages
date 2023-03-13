<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Output\Message;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\Messaging\MessageType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use AKlump\CheckPages\Event;

/**
 * Implements the Sleep plugin.
 */
final class Sleep implements PluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Event::TEST_CREATED => [
        function (TestEventInterface $event) {
          $test = $event->getTest();
          $test_config = $test->getConfig();
          $should_apply = array_key_exists('sleep', $test_config);
          if (!$should_apply) {
            return;
          }

          $test->setPassed();
          $sleep_seconds = intval($test_config['sleep']);
          $elapsed = 0;

          $title = $test->getDescription();
          if (empty($title)) {
            $title = sprintf('Sleep for %s second(s)', $sleep_seconds);
          }
          $test->addMessage(new Message(
            [
              '😴 ' . $title,
            ],
            MessageType::INFO,
            Verbosity::VERBOSE
          ));
          $test->echoMessages();
          while ($elapsed++ <= $sleep_seconds) {
            sleep(1);
          }
        },
      ],
    ];
  }

  public static function getPluginId(): string {
    return 'sleep';
  }

}
