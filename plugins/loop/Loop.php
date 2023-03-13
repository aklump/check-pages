<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\SuiteEventInterface;
use AKlump\CheckPages\Exceptions\BadSyntaxException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Implements the Sleep plugin.
 */
final class Loop implements PluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      // It's important that loop runs last over others because others may need
      // to do things first, e.g., the "import" plugin.
      Event::SUITE_LOADED => [[self::class, 'expandLoops'], -100],
    ];
  }

  /**
   * Event handler to expand loops.
   *
   * @param \AKlump\CheckPages\Event\SuiteEventInterface $event
   *
   * @return void
   * @throws \AKlump\CheckPages\Exceptions\StopRunnerException
   */
  public static function expandLoops(SuiteEventInterface $event) {
    $suite = $event->getSuite();
    $current_loop = NULL;
    foreach ($suite->getTests() as $test) {
      $test_config = $test->getConfig();

      // Watch for "endloop" instead of "end_loop" as a closure.
      if (array_key_exists('endloop', $test_config) && count($test_config) === 1 && is_null($test_config['endloop']) && $current_loop) {
        throw new BadSyntaxException('Found "endloop", did you mean "end_loop"?');
      }

      if (array_key_exists('loop', $test_config)) {
        if ($current_loop) {
          throw new BadSyntaxException('Loops may not be nested; you must end the first loop before starting a new one.', $suite);
        }
        try {
          $current_loop = new LoopCurrentLoop($test_config['loop']);
        }
        catch (BadSyntaxException $exception) {

          // Catch and throw with the test context.
          $message = str_replace(BadSyntaxException::PREFIX, '', $exception->getMessage());
          throw new BadSyntaxException($message, $test);
        }
        $suite->removeTest($test);
      }
      elseif (array_key_exists('end_loop', $test_config)) {
        if (!$current_loop) {
          throw new BadSyntaxException('Invalid `end_loop` found; no `loop` was found.', $suite);
        }
        $loop_tests = $current_loop->execute();
        $suite->replaceTestWithMultiple($test, $loop_tests);
        $current_loop = NULL;
      }
      elseif ($current_loop) {
        $current_loop->addTest($test);
        $suite->removeTest($test);
      }
    }
  }

  public static function getPluginId(): string {
    return 'loop';
  }

}
