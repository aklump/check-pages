<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Output\Feedback;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use AKlump\CheckPages\Event;

/**
 * Implements the Sleep plugin.
 */
final class Sleep implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Event::TEST_CREATED => [
        function (TestEventInterface $event) {
          $test = $event->getTest();
          $should_apply = array_key_exists('sleep', $test->getConfig());
          if (!$should_apply) {
            return;
          }

          $test->setPassed();
          $sleep_seconds = intval($test->getConfig()['sleep']);
          $elapsed = 0;
          $title = $test->getDescription();
          if (empty($title)) {
            $title = sprintf('Sleep for %s second(s)', $sleep_seconds);
          }
          // Provide a gutter before the 'zzz'.
          $title .= ' ';
          while ($elapsed++ <= $sleep_seconds) {
            Feedback::updateTestStatus($title, NULL, '😴 ');
            sleep(1);
            $title .= 'z';
          }
          Feedback::updateTestStatus(sprintf('%d second wait is over.', $sleep_seconds), TRUE);
        },
      ],
    ];
  }

}
