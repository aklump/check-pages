<?php

namespace AKlump\CheckPages;

use AKlump\CheckPages\Event\AssertEventInterface;
use AKlump\CheckPages\Plugin\Plugin;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Implements the Xpath plugin.
 */
final class Xpath extends Plugin {

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
