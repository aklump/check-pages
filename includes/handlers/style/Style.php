<?php

namespace AKlump\CheckPages\Handlers;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\AssertEventInterface;
use AKlump\CheckPages\Event\DriverEventInterface;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Exceptions\TestFailedException;
use AKlump\CheckPages\Output\Message\Message;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\Messaging\MessageType;

/**
 * Implements the style handler.
 */
final class Style implements HandlerInterface {

  public static function getSubscribedEvents() {
    return [
      Event::TEST_CREATED => [
        function (TestEventInterface $event) {
          $test = $event->getTest();
          if (!$test->has('find')) {
            return;
          }
          $config = $test->getConfig();
          $config['extras']['plugin_style'] = [];
          foreach ($config['find'] as $assertion) {
            if (is_array($assertion) && array_key_exists('style', $assertion)) {
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
            if (!empty($assertion[Dom::SELECTOR])) {
              $event->getDriver()
                ->addStyleRequest($assertion[Dom::SELECTOR], $assertion['style']);
            }
          }
        },
      ],
      Event::ASSERT_CREATED => [
        function (AssertEventInterface $event) {
          $assert = $event->getAssert();
          $should_apply = array_key_exists('style', $assert->getConfig());
          if ($should_apply) {
            try {
              $response = $event->getDriver()->getResponse();
              $dom_path = $assert->{Dom::SELECTOR};
              $style_property = $assert->style;
              $assert
                ->setSearch(self::getId(), $dom_path)
                ->setModifer('style', $style_property);
              $styles_header = $response->getHeader('X-Computed-Styles')[0] ?? NULL;
              if (is_null($styles_header)) {
                $event->getTest()
                  ->addMessage(new Message([
                    "Missing X-Computed-Styles header from response; cause unknown.",
                  ], MessageType::ERROR, Verbosity::VERBOSE));
              }
              $haystack = json_decode($styles_header ?? '{}', TRUE);
              $assert->setHaystack([$haystack[$dom_path][$style_property] ?? NULL]);
            }
            catch (\Exception $e) {
              throw new TestFailedException($event->getTest()->getConfig(), $e);
            }
          }
        },
      ],

    ];
  }

  public static function getId(): string {
    return 'style';
  }

}
