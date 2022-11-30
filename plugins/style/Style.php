<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\AssertEventInterface;
use AKlump\CheckPages\Event\DriverEventInterface;
use AKlump\CheckPages\Event\TestEventInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Implements the style plugin.
 */
final class Style implements EventSubscriberInterface {

  const SEARCH_TYPE = 'style';

  const MODIFIER_TYPE = 'style';

  public static function getSubscribedEvents() {
    return [
      Event::TEST_STARTED => [
        function (TestEventInterface $event) {
          $test = $event->getTest();
          $config = $test->getConfig();
          if (empty($config['find'])) {
            return;
          }
          $config['extras']['plugin_style'] = [];
          foreach ($config['find'] as $assertion) {
            if (is_array($assertion) && array_key_exists(self::SEARCH_TYPE, $assertion)) {
              // Force JS to be enabled when we have to capture styles.
              $config['js'] = TRUE;
              $config['extras']['plugin_style'][] = $assertion;
            }
          }
          $test->setConfig($config);
        },
      ],
      Event::REQUEST_CREATED => [
        function (DriverEventInterface $event) {

          // Add the style requests to the driver.
          $assertions = $event->getTest()
                          ->getConfig()['extras']['plugin_style'] ?? [];
          foreach ($assertions as $assertion) {
            if (!empty($assertion[Dom::SEARCH_TYPE])) {
              $event->getDriver()
                ->addStyleRequest($assertion[Dom::SEARCH_TYPE]);
            }
          }
        },
      ],
      Event::ASSERT_CREATED => [
        function (AssertEventInterface $event) {
          $assert = $event->getAssert();
          $should_apply = $assert->{self::SEARCH_TYPE};
          if (!$should_apply) {
            return;
          }

          $response = $event->getDriver()->getResponse();
          $dom_path = $assert->{Dom::SEARCH_TYPE};
          $style_property = $assert->{self::MODIFIER_TYPE};
          $assert
            ->setSearch(self::SEARCH_TYPE, $dom_path)
            ->setModifer(self::MODIFIER_TYPE, $style_property);
          $haystack = json_decode($response->getHeader('X-Computed-Styles')[0] ?? '{}', TRUE);
          $assert->setHaystack([$haystack[$dom_path][$style_property] ?? NULL]);
        },
      ],
    ];
  }
}
