<?php

namespace AKlump\CheckPages\Handlers;

use AKlump\CheckPages\Assert;
use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Exceptions\TestFailedException;
use AKlump\CheckPages\Output\Icons;
use AKlump\CheckPages\Output\Message\Message;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\CheckPages\Messaging\MessageType;
use AKlump\CheckPages\TAP\Result;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Implements the Evaluate handler.
 */
final class Evaluate implements HandlerInterface {

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
          $assert = new Assert(self::getId(), [
            'eval' => $config['eval'],
          ], $test);
          $assert->setAssertion(Assert::ASSERT_CALLABLE, [
            self::class,
            'evaluateExpression',
          ]);
          $assert->run();

          if ($test->get(Assert::ASSERT_SETTER)) {
            $set_value = $assert->get('value');
            $test->set('value', $set_value);
          }

          if ($assert->hasPassed()) {
            $test->setPassed();
            $test->setResult(Result::OK);
            $test->addMessage(new Message(
              [
                strval($assert),
              ],
              MessageType::CODE,
              Verbosity::DEBUG
            ));
          }
          elseif ($assert->hasFailed()) {
            $test->setFailed();
            $test->setResult(Result::NOT_OK);

            $test->addMessage((new Message(
              [
                $assert->getReason(),
              ],
              MessageType::CODE,
              Verbosity::DEBUG
            ))->setIcon(Icons::FAIL));
          }
        },
      ],

      Event::ASSERT_CREATED => [
        function (Event\AssertEventInterface $event) {
          $assert = $event->getAssert();
          $config = $assert->getConfig();
          $should_apply = array_key_exists('eval', $config);
          if (!$should_apply) {
            return;
          }
          $assert->setAssertion(Assert::ASSERT_CALLABLE, [
            self::class,
            'evaluateExpression',
          ]);
          $assert->run();
        },
      ],
    ];
  }

  public static function evaluateExpression(Assert $assert) {
    $assert->setToStringOverride([self::class, 'stringify']);
    $expression = $assert->eval;
    $assert->getTest()->interpolate($expression);

    // Remove "px" to allow math.
    $expression = preg_replace('/(\d+)px/i', '$1', $expression);

    try {
      $eval = new ExpressionLanguage();
      $result = $eval->evaluate($expression);
      $assert->set('value', $result);
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

  public static function getId(): string {
    return 'evaluate';
  }

}
