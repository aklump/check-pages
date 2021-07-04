<?php

namespace AKlump\CheckPages;

use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Implements the Xpath plugin.
 */
final class Xpath implements TestPluginInterface {

  const SEARCH_TYPE = 'xpath';

  /**
   * {@inheritdoc}
   */
  public function onBeforeDriver(array &$config) {
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeRequest(&$driver) {
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeAssert(Assert $assert, ResponseInterface $response) {
    $search_value = $assert->{self::SEARCH_TYPE};
    $assert->setSearch(self::SEARCH_TYPE, $search_value);
    $crawler = new Crawler($assert->getHaystack());
    $haystack = $crawler->filterXPath($search_value);
    $assert->setHaystack($haystack);
  }

  /**
   * {@inheritdoc}
   */
  public function onAssertToString(string $stringified, Assert $assert): string {
    return $stringified;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(array &$config) {
  }

}
