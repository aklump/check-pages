<?php

namespace AKlump\CheckPages\Handlers;

use AKlump\CheckPages\Assert;
use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\AssertEventInterface;
use AKlump\CheckPages\Exceptions\TestFailedException;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Implements the Xpath handler.
 */
final class Xpath implements HandlerInterface {

  public static function doesApply($context): bool {
    if ($context instanceof Assert) {
      return array_key_exists('xpath', $context->getConfig());
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Event::ASSERT_CREATED => [
        function (AssertEventInterface $event) {
          if (!self::doesApply($event->getAssert())) {
            return;
          }
          try {
            // Here will will create an instance and call the method because the
            // implementation is going to be more complicated and involve
            // multiple methods.
            $xpath = new self();
            $xpath->setHaystack($event);
          }
          catch (\Exception $e) {
            throw new TestFailedException($event->getTest()
              ->getConfig(), $e);
          }
        },
      ],
    ];
  }

  protected function setHaystack(AssertEventInterface $event) {
    $assert = $event->getAssert();
    $assert->setSearch('xpath', $assert->xpath);
    $haystack = $assert->getHaystack();
    if (!$haystack instanceof Crawler) {
      $haystack = new Crawler($haystack);
    }
    $haystack = $haystack->filterXPath($assert->xpath);
    $assert->setHaystack($haystack);
  }

  public static function getId(): string {
    return 'xpath';
  }

}
