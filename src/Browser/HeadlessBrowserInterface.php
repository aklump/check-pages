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
  public function addJavaScriptEval(string $expression): HeadlessBrowserInterface;

  /**
   * Immediately executes the provided JavaScript code within the browser context and returns the result.
   *
   * @param mixed $javaScriptCode The JavaScript code to be evaluated. This can be a string or other types as supported
   *                              by the underlying browser's evaluate method.
   *
   * @return string The result of the JavaScript code execution as a string.
   *
   * @throws \AKlump\CheckPages\Exceptions\JavaScriptException If an error occurs during the execution of the JavaScript code.
   */
  public function evaluateJavaScript(string $javaScriptCode): string;

  /**
   * Make a request for a computed styles value to be retrieved.
   *
   * @param string $domQuerySelector
   *   The selector pointing to the desired element whose style is desired.
   * @param string $styleName
   *   The CSS style name whose value is to be retrieved.
   *
   * @return \AKlump\CheckPages\Browser\HeadlessBrowserInterface Self for chaining.
   *   Self for chaining.
   *
   * @see \AKlump\CheckPages\Handlers\Dom;
   */
  public function addStyleRequest(string $domQuerySelector, string $styleName): HeadlessBrowserInterface;

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
   * @param float|NULL $pixelRatio
   *
   * @return \AKlump\CheckPages\Browser\HeadlessBrowserInterface
   *   Self for chaining.
   */
  public function setViewport(int $width = NULL, int $height = NULL, float $pixelRatio = NULL): HeadlessBrowserInterface;

}
