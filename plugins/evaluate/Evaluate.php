<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Assert;
use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Exceptions\TestFailedException;
use AKlump\CheckPages\Output\Message;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\Messaging\MessageType;
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
          $event->getTest()->interpolate($config['eval']);
          $assert = new Assert(self::SEARCH_TYPE, [
            'eval' => $config['eval'],
          ], $test);
          $assert->setAssertion(Assert::ASSERT_CALLABLE, [
            self::class,
            'evaluateExpression',
          ]);
          $assert->run();

          if ($assert->hasPassed()) {
            $test->setPassed();
            $test->addMessage(new Message(
              [
                strval($assert),
              ],
              MessageType::SUCCESS,
              Verbosity::VERBOSE
            ));
          }
          elseif ($assert->hasFailed()) {
            $test->hasFailed();
            $test->addMessage(new Message(
              [
                $assert->getReason(),
              ],
              MessageType::ERROR,
              Verbosity::VERBOSE
            ));
          }
        },
      ],
    ];
  }

  public static function evaluateExpression(Assert $assert) {
    $assert->setToStringOverride([self::class, 'stringify']);
    $expression = $assert->eval;

    // Remove "px" to allow math.
    $expression = preg_replace('/(\d+)px/i', '$1', $expression);

    try {
      $eval = new ExpressionLanguage();
      $result = $eval->evaluate($expression);
    }
    catch (\Exception $exception) {
      throw new TestFailedException($assert->getConfig(), $exception);
    }
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
    throw new TestFailedException($assert->getConfig(), new \Exception($reason));
  }

  /**
   * {@inheritdoc}
   */
  public static function stringify(string $stringified, Assert $assert): string {
    return sprintf('%s', $assert->eval);
  }
}
