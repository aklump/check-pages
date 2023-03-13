<?php

namespace AKlump\CheckPages\Browser;

interface HeadlessBrowserInterface {

  /**
   * Make a request for a computed styles value to be retrieved.
   *
   * @param string $expression
   *   The javascript expression to evaluate.
   *
   * @return \AKlump\CheckPages\HeadlessBrowserInterface
   *   Self for chaining.
   */
  public function addJavascriptEval(string $expression): HeadlessBrowserInterface;

  public function getEvaluatedJavascript(): array;

  /**
   * Make a request for a computed styles value to be retrieved.
   *
   * @param string $dom_query_selector
   *   The selector pointing to the desired element whose style is desired.
   * @param string $style_name
   *   The CSS style name whose value is to be retrieved.
   *
   * @return \AKlump\CheckPages\Browser\HeadlessBrowserInterface Self for chaining.
   *   Self for chaining.
   *
   * @see \AKlump\CheckPages\Plugin\Dom
   */
  public function addStyleRequest(string $dom_query_selector, string $style_name): HeadlessBrowserInterface;

  /**
   * Get the computed styles that were requested.
   *
   * @return array
   *   Keyed by the query selectors, the values are the computed styles.
   *
   * @see \AKlump\CheckPages\HeadlessBrowserInterface::addStyleRequest()
   */
  public function getComputedStyles(): array;

  /**
   * Set one or both dimensions of the headless browser for the request.
   *
   * @param int|NULL $width
   * @param int|NULL $height
   *
   * @return \AKlump\CheckPages\Browser\HeadlessBrowserInterface
   *   Self for chaining.
   */
  public function setViewport(int $width = NULL, int $height = NULL);

}
