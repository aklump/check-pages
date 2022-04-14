<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Event\AssertEventInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Implements the Xpath plugin.
 */
final class Xpath extends LegacyPlugin {

  const SEARCH_TYPE = 'xpath';

  /**
   * {@inheritdoc}
   */
  public function onBeforeAssert(AssertEventInterface $event) {
    $assert = $event->getAssert();
    $search_value = $assert->{self::SEARCH_TYPE};
    $assert->setSearch(self::SEARCH_TYPE, $search_value);
    $crawler = new Crawler($assert->getHaystack());
    $haystack = $crawler->filterXPath($search_value);
    $assert->setHaystack($haystack);
  }

}
