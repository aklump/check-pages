<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\LoftLib\Bash\Color;
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
          $config = $event->getTest()->getConfig();
          //          $input = $event->getTest()
          //            ->getSuite()
          //            ->getRunner()
          //            ->getInput();
          $output = $event->getTest()
            ->getSuite()
            ->getRunner()
            ->getOutput();
          $should_apply = array_key_exists('breakpoint', $config);
          //          $should_apply = $should_apply && ($output->isDebug() || $input->getOption('debug'));
          $should_apply = $should_apply && $output->isDebug();
          if (!$should_apply) {
            return;
          }

          echo '🛑 ' . Color::wrap('light gray', 'Press any key ');

          // @link https://www.sitepoint.com/howd-they-do-it-phpsnake-detecting-keypresses/
          // @link https://stackoverflow.com/a/15322457/3177610
          system('stty cbreak -echo');
          $stdin = fopen('php://stdin', 'r');
          ord(fgetc($stdin));
          echo PHP_EOL;
        },
      ],
    ];
  }


}
