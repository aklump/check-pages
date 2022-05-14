<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Assert;
use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Output\Feedback;
use AKlump\CheckPages\Parts\SetTrait;
use AKlump\LoftLib\Bash\Color;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Implements the Value plugin.
 */
final class Value implements EventSubscriberInterface {

  use SetTrait;

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
            $message = $obj->setKeyValuePair(
              $test->getSuite()->variables(),
              $config['set'],
              $config['value']
            );
            Feedback::updateTestStatus($test->getRunner()
              ->getOutput(), $message, TRUE);
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
                Feedback::updateTestStatus($test->getRunner()
                  ->getOutput(), $assert, TRUE);
              }
              else {
                Feedback::$testDetails->overwrite(Color::wrap('red', $assert->getReason()));
                Feedback::updateTestStatus($test->getRunner()
                  ->getOutput(), $assert, FALSE);
              }
            }
          }
        },
      ],
    ];
  }

}
