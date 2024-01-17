<?php

namespace AKlump\CheckPages\Helpers;

use AKlump\CheckPages\Assert;
use Symfony\Component\DomCrawler\Crawler;

class CrawlerToArray {

  public function __invoke(Assert $assert, Crawler $crawler, &$pass): array {
    list($assert_type, $assert_value) = $assert->getAssertion();

    if (!$crawler->getNode(0)) {
      // It's possible that we're doing a count === 0, in which case this will
      // not actually be a fail, and will be reversed during the count phase.
      $pass = FALSE;
      $this->reason = sprintf('"%s" does not exist in the DOM.', $assert_value);

      return [];
    }

    switch ($assert_type) {
      case Assert::ASSERT_CONTAINS:
      case Assert::ASSERT_NOT_CONTAINS:
      case Assert::ASSERT_MATCHES:
      case Assert::ASSERT_NOT_MATCHES:
      case Assert::ASSERT_SETTER:
      case Assert::ASSERT_EQUALS:
      case Assert::ASSERT_NOT_EQUALS:
        list($modifier_type, $modifier_value) = $assert->getModifer();
        $crawler = $crawler->each(function ($node) use ($modifier_type, $modifier_value) {
          if ($modifier_type === Assert::MODIFIER_ATTRIBUTE) {
            $value = $node->attr($modifier_value);
          }
          else {
            $value = $node->html();
          }

          return is_string($value) ? trim($value) : $value;
        });
        break;

      case Assert::ASSERT_TEXT:
      case Assert::ASSERT_NOT_TEXT:
      default:
        $crawler = $crawler->each(function ($node) {
          return trim($node->text());
        });
        break;
    }

    return $crawler;
  }

}
