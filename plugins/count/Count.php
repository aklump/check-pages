<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\AssertEventInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Implements the Count plugin.
 */
final class Count implements PluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Event::ASSERT_FINISHED => [

        /**
         * Modify an assert instance if it has a count parameter.
         */
        function (AssertEventInterface $event) {
          $assert = $event->getAssert();
          if (!is_numeric($assert->count) && !$assert->count) {
            return;
          }

          $expected = $assert->count;
          $actual_count = count($assert->getHaystack());

          // If we don't have a comparator then we need to add '=='.
          if (!preg_match('/==|!=|<|>|<=|>=/', $expected)) {
            $expected = "== $expected";
          }

          $eval = new ExpressionLanguage();
          $expression = "$actual_count $expected";
          $pass = $eval->evaluate($expression);
          if (!$pass) {
            $reason = sprintf('Actual count %d did not evaluate true for %s.', $actual_count, $expected);
          }
          $assert->setResult($pass, $reason ?? '');

          // Add to the label an indication of the count expectation.
          $label = $assert->getLabel();
          if (substr($label, -1, 1) === '.') {
            $label .= sprintf(' Assert count is %s.', $assert->count);
          }
          $assert->setLabel($label);
        },
      ],
    ];
  }

  public static function getPluginId(): string {
    return 'count';
  }

}
