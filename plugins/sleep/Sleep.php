<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\LoftLib\Bash\Color;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
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
          $test->write('😴', FALSE, OutputInterface::VERBOSITY_VERBOSE);
          $test->write(Color::wrap('light gray', sprintf(' Sleep for %s second(s)', $sleep_seconds)), FALSE, OutputInterface::VERBOSITY_VERY_VERBOSE);

          $elapsed = 0;
          $test->write(' ', FALSE, OutputInterface::VERBOSITY_VERBOSE);
          while ($elapsed++ < $sleep_seconds) {
            sleep(1);
            $test->write('z', FALSE, OutputInterface::VERBOSITY_VERBOSE);
          }
          $test->writeln('', OutputInterface::VERBOSITY_VERBOSE);
        },
      ],
    ];
  }

}
