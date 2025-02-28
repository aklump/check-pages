<?php

namespace AKlump\CheckPages\Handlers\Form;

use DOMElement;
use InvalidArgumentException;
use Symfony\Component\DomCrawler\Crawler;

final class HtmlFormReader {

  const OPTION_INCLUDE_DISABLED = 1;

  private string $formSelector;

  private string $html;

  private Crawler $form;

  private int $options;

  public function getForm(): Crawler {
    if (!isset($this->form)) {
      $crawler = new Crawler($this->html);
      $form = $crawler->filter($this->formSelector);
      if (!$form->count()) {
        throw new InvalidArgumentException(sprintf('Cannot find form selecting with: %s', $this->formSelector));
      }
      $this->form = $form;
    }

    return $this->form;
  }

  public function __construct(string $html, string $form_selector, int $options = 0) {
    $this->html = $html;
    $this->formSelector = $form_selector;
    $this->options = $options;
  }


  public function getAction(): string {
    return $this->getForm()->getNode(0)->getAttribute('action');
  }

  public function getMethod(): string {
    $method = $this->getForm()->getNode(0)->getAttribute('method');

    return strtoupper($method);
  }

  /**
   * @return array Key/value array of values that exist in the form
   */
  public function getValues(): array {
    $form = $this->getForm();
    $response = [];
    foreach ($form->filter('input,select') as $el) {
      /** @var \DOMElement $el */
      if (!$this->options & self::OPTION_INCLUDE_DISABLED
        && $el->hasAttribute('disabled')) {
        continue;
      }
      $name = $el->getAttribute('name');
      $value = $this->getElementValue($el);
      $response[$name] = $value;
    }

    return $response;
  }

  public function getSubmit(string $submit_selector): KeyLabelNode {
    /** @var \DOMElement $submit */
    $submit = $this->getForm()->filter($submit_selector)->getNode(0);
    if (!$submit instanceof DOMElement
      || !in_array($submit->tagName, [
        'input',
        'button',
      ])) {
      throw new InvalidArgumentException(sprintf('Cannot find submit button with selector: %s', $submit_selector));
    }

    return new KeyLabelNode(
      $submit->getAttribute('name'),
      $submit->getAttribute('value'),
    );
  }

  private function getElementValue(DOMElement $el) {
    if ($el->tagName === 'select') {
      return $this->getValueFromSelectElement($el);
    }

    return $el->getAttribute('value');
  }

  private function getValueFromSelectElement(DOMElement $el) {
    $option = $this->getSelectedOrFirstOption($el);
    if (!$option instanceof DOMElement) {
      throw new InvalidArgumentException('Select element has no options');
    }

    return new KeyLabelNode(
      $option->getAttribute('value'),
      $option->textContent,
    );
  }

  /**
   * @param \DOMElement $select_el
   *
   * @return \DOMNode|null
   */
  private function getSelectedOrFirstOption(DOMElement $select_el) {
    $crawler = new Crawler($select_el);
    $selected = $crawler->filter('option[selected]');
    if ($selected->count() > 0) {
      return $selected->getNode(0);
    }

    $firstOption = $crawler->filter('option');
    if ($firstOption->count() > 0) {
      return $firstOption->getNode(0);
    }

    return NULL;

  }

}
