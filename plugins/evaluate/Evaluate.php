<?php

namespace AKlump\CheckPages;

use Psr\Http\Message\ResponseInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Implements the Evaluate plugin.
 */
final class Evaluate extends Plugin {

  const SEARCH_TYPE = 'evaluate';

  /**
   * Captures the test config to share across methods.
   *
   * @var array
   */
  private $config;

  /**
   * {@inheritdoc}
   */
  public function onAssertToString(string $stringified, Assert $assert): string {
    return sprintf('( %s )', $assert->getNeedle());
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeDriver(array &$config) {
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function onBeforeAssert(Assert $assert, ResponseInterface $response) {
    $assert->setSearch(static::SEARCH_TYPE);
    $original_eval = $this->config['find'][$assert->getId()]['eval'] ?? NULL;
    $assert->setHaystack([$original_eval]);
    $expression = $assert->eval;
    $assert->setNeedle($expression);

    // Remove "px" to allow math.
    $expression = preg_replace('/(\d+)px/i', '$1', $expression);

    $eval = new ExpressionLanguage();
    $result = $eval->evaluate($expression);
    if ($result) {
      $reason = "%s === true";
    }
    else {
      $reason = "%s !== true";
    }
    $assert->setResult($result, sprintf($reason, sprintf('( %s )', $assert->getHaystack()[0])));
  }

}
