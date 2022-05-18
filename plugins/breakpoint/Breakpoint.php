<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Output\Feedback;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Implements the Breakpoint plugin.
 */
final class Breakpoint implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Event::TEST_CREATED => [
        function (TestEventInterface $event) {
          $test = $event->getTest();
          $config = $test->getConfig();
          $output = $test->getRunner()->getOutput();
          $is_breakpoint = array_key_exists('breakpoint', $config);
          if ($is_breakpoint) {
            $test->setPassed();
          }
          $should_apply = $is_breakpoint && $output->isDebug();
          if (!$should_apply) {
            return;
          }

          Feedback::updateTestStatus($test->getRunner(), $test->getDescription(), NULL, '🛑 ', 'Press any key ');

          // @link https://www.sitepoint.com/howd-they-do-it-phpsnake-detecting-keypresses/
          // @link https://stackoverflow.com/a/15322457/3177610
          system('stty cbreak -echo');
          $stdin = fopen('php://stdin', 'r');
          ord(fgetc($stdin));

          // Vanilla echo is sufficient because we're clearing out the keypress.
          echo PHP_EOL;
        },
      ],
    ];
  }


}
