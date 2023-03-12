<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\AssertEventInterface;
use AKlump\CheckPages\Exceptions\TestFailedException;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Implements the Xpath plugin.
 */
final class Xpath implements PluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Event::ASSERT_CREATED => [
        function (AssertEventInterface $event) {
          $should_apply = array_key_exists('xpath', $event->getAssert()
            ->getConfig());
          if ($should_apply) {
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

  public static function getPluginId(): string {
    return 'xpath';
  }

}
