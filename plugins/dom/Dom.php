<?php

namespace AKlump\CheckPages;

use AKlump\CheckPages\Plugin\Plugin;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Implements the Dom plugin.
 */
final class Dom extends Plugin {

  const SEARCH_TYPE = 'dom';

  /**
   * {@inheritdoc}
   */
  public function onBeforeAssert(\AKlump\CheckPages\Event\AssertEventInterface $event) {
    $assert = $event->getAssert();
    $search_value = $assert->{self::SEARCH_TYPE};
    $assert->setSearch(self::SEARCH_TYPE, $search_value);
    $crawler = new Crawler($assert->getHaystack());
    $haystack = $crawler->filter($search_value);
    $assert->setHaystack($haystack);
  }

}
