<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Assert;
use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Output\Feedback;
use AKlump\LoftLib\Bash\Color;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Implements the Evaluate plugin.
 */
final class Evaluate implements EventSubscriberInterface {

  const SEARCH_TYPE = 'evaluate';

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
          $should_apply = array_key_exists('eval', $config);
          if (!$should_apply) {
            return;
          }

          $assert = new Assert([
            'eval' => $config['eval'],
          ], self::SEARCH_TYPE);
          $assert->setAssertion(Assert::ASSERT_CALLABLE, [
            self::class,
            'evaluateExpression',
          ]);
          $assert->run();
          $test_result = $assert->getResult();
          $test_result ? $test->setPassed() : $test->setFailed();

          //          Feedback::$testDetails->write('', OutputInterface::VERBOSITY_VERBOSE);
          if ($test_result) {
            Feedback::updateTestStatus($test->getRunner(), $assert, TRUE);
          }
          else {
            Feedback::updateTestStatus($test->getRunner(), $assert, FALSE);
            Feedback::$testDetails->write(Color::wrap('red', '├── ' . $assert->getReason()));
          }
        },

        //        Event::ASSERT_CREATED => [
        //          function (AssertEventInterface $event) {
        //            $assert = $event->getAssert();
        //            $config = $assert->getConfig();
        //            $should_apply = array_key_exists('eval', $config);
        //            if (!$should_apply) {
        //              return;
        //            }
        //          },
        //        ],
      ],
    ];
  }

  public static function evaluateExpression(Assert $assert) {
    $assert->setToStringOverride([self::class, 'stringify']);

    $expression = $assert->eval;

    // Remove "px" to allow math.
    $expression = preg_replace('/(\d+)px/i', '$1', $expression);

    $eval = new ExpressionLanguage();
    $result = $eval->evaluate($expression);
    if ($result) {
      $reason = "%s === true";
    }
    else {
      $reason = "%s !== true";
    }
    $assert->setResult($result, sprintf($reason, sprintf('( %s )', $assert->eval)));
    if ($result) {
      return TRUE;
    }
    throw new \AKlump\CheckPages\Exceptions\TestFailedException($assert->getConfig(), $reason);
  }

  /**
   * {@inheritdoc}
   */
  public static function stringify(string $stringified, Assert $assert): string {
    return sprintf('%s', $assert->eval);
  }
}
