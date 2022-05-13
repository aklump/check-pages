<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Assert;
use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\AssertEventInterface;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Output\FeedbackInterface;
use AKlump\CheckPages\Variables;
use AKlump\LoftLib\Bash\Color;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Implements the Value plugin.
 */
final class Value implements EventSubscriberInterface, PluginInterface {

  /**
   * @var \AKlump\CheckPages\Parts\Runner
   */
  protected $runner;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [

      //
      // Handle setting/asserting from test-level.
      //
      Event::TEST_CREATED => [
        function (TestEventInterface $event) {
          $test = $event->getTest();
          $config = $test->getConfig();
          $should_apply = array_key_exists('value', $config);
          if (!$should_apply) {
            return;
          }

          // Handle a test-level setter.
          $test_result = NULL;
          $assert = NULL;
          if (array_key_exists('set', $config)) {
            $obj = new self();
            $obj->setValue(
              $test->getSuite()->variables(),
              $test,
              $config['set'],
              $config['value']
            );
            $test_result = TRUE;
          }

          // Handle a test-level assertion.
          $match = array_intersect_key([
            'is' => Assert::ASSERT_EQUALS,
            'is not' => Assert::ASSERT_NOT_EQUALS,
            'contains' => Assert::ASSERT_CONTAINS,
            'not contains' => Assert::ASSERT_NOT_CONTAINS,
            'matches' => Assert::ASSERT_MATCHES,
            'not matches' => Assert::ASSERT_NOT_MATCHES,
          ], $config);
          if ($match) {
            $type = key($match);
            $definition = [
              $type => $config['value'],
            ];
            $assert = new Assert($definition, 'value');
            $assert->setHaystack([$config['value']]);
            $assert->setAssertion($match[$type], $config[$type]);
            $assert->run();
            $test_result = $assert->getResult();
          }

          if (is_bool($test_result)) {
            $test_result ? $test->setPassed() : $test->setFailed();
            if ($assert) {
              if ($test_result) {
                $test->writeln(Color::wrap('green', '├── ' . $assert), OutputInterface::VERBOSITY_VERY_VERBOSE);
              }
              else {
                $test->writeln(Color::wrap('white on red', '├── ' . $assert));
                $test->writeln(Color::wrap('red', $assert->getReason()));
              }
            }
          }
        },
      ],

      //
      // Handle setting from an assertion.
      //
      Event::ASSERT_CREATED => [
        function (AssertEventInterface $event) {
          $assert = $event->getAssert();
          $should_apply = boolval($assert->value);
          if ($should_apply) {
            $obj = new self();
            if ($assert->set) {
              $obj->setValue(
                $event->getTest()->getSuite()->variables(),
                $assert,
                $assert->set,
                $assert->value
              );
            }
          }
        },
      ],
    ];
  }

  /**
   * Handles the setting of a key/value pair.
   *
   * @param \AKlump\CheckPages\Variables $vars
   * @param \AKlump\CheckPages\Output\FeedbackInterface $feedback
   * @param string $key
   * @param $value
   *
   * @return void
   */
  protected function setValue(Variables $vars, FeedbackInterface $feedback, string $key, $value) {
    $vars->setItem($key, $value);
    $feedback->writeln(Color::wrap('green', sprintf('├── ${%s} set to "%s"', $key, $value)), OutputInterface::VERBOSITY_VERY_VERBOSE);
  }

}
