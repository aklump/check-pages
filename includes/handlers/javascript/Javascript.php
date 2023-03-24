<?php

namespace AKlump\CheckPages\Handlers;

use AKlump\CheckPages\Assert;
use AKlump\CheckPages\Browser\HeadlessBrowserInterface;
use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\AssertEventInterface;
use AKlump\CheckPages\Event\DriverEventInterface;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Parts\SetTrait;

/**
 * Implements the Javascript handler.
 */
final class Javascript implements HandlerInterface {

  const SELECTOR = 'javascript';

  /**
   * {@inheritdoc}
   */
  public static function getId(): string {
    return 'javascript';
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    // If a class instance is not needed you can simplify this by doing
    // everything inside of the function, rather than instantiating and calling
    // a method.  Depends on implementation.
    return [
      Event::TEST_CREATED => [
        function (TestEventInterface $event) {
          $test = $event->getTest();
          $config = $test->getConfig();
          foreach (($config['find'] ?? []) as $assertion) {
            if (is_array($assertion) && array_key_exists(self::SELECTOR, $assertion)) {
              $config['js'] = TRUE;
              $config['extra']['plugin_javascript'][] = $assertion;
            }
          }
          $test->setConfig($config);
        },
      ],
      Event::REQUEST_CREATED => [
        function (DriverEventInterface $event) {
          $config = $event->getTest()->getConfig();
          $assertions = $config['extra']['plugin_javascript'] ?? [];
          $driver = $event->getDriver();
          if (empty($assertions) || !$driver instanceof HeadlessBrowserInterface) {
            return;
          }
          foreach ($assertions as $assertion) {
            if (!empty($assertion[self::SELECTOR])) {
              $driver->addJavascriptEval($assertion[self::SELECTOR]);
            }
          }
        },
      ],
      Event::ASSERT_CREATED => [
        function (AssertEventInterface $event) {
          $assert = $event->getAssert();
          if (!$assert->has(self::SELECTOR)) {
            return;
          }
          $assertions = $event->getTest()
                          ->getConfig()['extra']['plugin_javascript'] ?? [];
          if (empty($assertions)) {
            return;
          }
          $response = $event->getDriver()->getResponse();
          $search_value = $assert->{self::SELECTOR};
          $assert->setSearch(self::SELECTOR, $search_value);

          $haystack = json_decode($response->getHeader('X-Javascript-Evals')[0] ?? '{}', TRUE);
          $haystack = array_reduce($haystack, function ($result, $item) use ($search_value) {
            if ($search_value === $item['eval']) {
              $result .= $item['result'];
            }

            return $result;
          });

          // If we are simply running JS, then the assertion is not necessary and
          // we'll make the check the haystack so it will always pass.
          list($assertion_type) = $assert->getAssertion();
          if (is_null($assertion_type)) {
            $assert->setAssertion(Assert::ASSERT_EQUALS, $haystack);
          }

          $assert->setHaystack([$haystack]);
        },
      ],
    ];
  }

}
