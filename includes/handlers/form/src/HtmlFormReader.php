<?php

namespace AKlump\CheckPages\Handlers\Form;

use DOMElement;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Extract the form keys and values from an HTML form.
 *
 * @see \AKlump\CheckPages\Handlers\Form\FormValuesManager
 */
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
      if (!empty($name)) {
        $response[$name] = $value;
      }
    }

    return $response;
  }

  /**
   * @return \AKlump\CheckPages\Handlers\Form\KeyLabelNode[]
   */
  public function getAllowedValues(): array {
    $skip_element = function (DOMElement $el) {
      return !$this->options & self::OPTION_INCLUDE_DISABLED && $el->hasAttribute('disabled');
    };
    $form = $this->getForm();
    $response = [];

    foreach ($form->filter('input[type=radio]') as $el) {
      /** @var \DOMElement $el */
      if ($skip_element($el)) {
        continue;
      }
      $name = $el->getAttribute('name');
      $id = $el->getAttribute('id');
      $label = $form->filter('label[for=' . $id . ']');
      $label = $label ? $label->text() : $el->getAttribute('value');
      $response[$name][] = new KeyLabelNode(
        $el->getAttribute('value'),
        $label,
      );
    }

    foreach ($form->filter('input[type=checkbox]') as $el) {
      /** @var \DOMElement $el */
      if ($skip_element($el)) {
        continue;
      }
      $name = $el->getAttribute('name');
      $id = $el->getAttribute('id');
      $label = $form->filter('label[for=' . $id . ']');
      $label = $label ? $label->text() : $el->getAttribute('value');
      $response[$name][] = new KeyLabelNode(
        $el->getAttribute('value'),
        $label,
      );
    }

    foreach ($form->filter('select') as $el) {
      /** @var \DOMElement $el */
      if ($skip_element($el)) {
        continue;
      }
      $allowed_values = [];

      $name = $el->getAttribute('name');
      foreach ($el->getElementsByTagName('option') as $option) {
        /** @var \DOMElement $option */
        if ($skip_element($option)) {
          continue;
        }
        $allowed_values[] = new KeyLabelNode(
          $option->getAttribute('value'),
          $option->textContent,
        );
      }
      $response[$name] = $allowed_values;
    }

    return $response;
  }

  /**
   * Get the form's submit button.
   *
   * @param string $submit_selector Leave this empty and the first submit will
   * be selected.
   *
   * @return \AKlump\CheckPages\Handlers\Form\KeyLabelNode  The first|specified submit button.
   *
   * @throws \InvalidArgumentException If no submit can be found.
   */
  public function getSubmit(string $submit_selector = ''): KeyLabelNode {
    /** @var \DOMElement $submit */
    $submit_selector = $submit_selector ?: 'input[type=submit],button[type=submit]';
    /** @var \Symfony\Component\DomCrawler\Crawler $submit */
    $submit = $this->getForm()->filter($submit_selector);
    if ($submit->count() > 1) {
      throw new RuntimeException(sprintf('Multiple submit elements in %s, you must use form.submit in this test.', $this->formSelector));
    }
    $submit = $this->getForm()->filter($submit_selector)->getNode(0);
    if (!$submit instanceof DOMElement) {
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
    elseif ($el->getAttribute('type') === 'radio') {
      return $this->getValueFromRadioElement($el);
    }
    elseif ($el->getAttribute('type') === 'checkbox') {
      return $this->getValueFromCheckboxElement($el);
    }

    return $el->getAttribute('value');
  }

  /**
   * @param \DOMElement $el
   *
   * @return \AKlump\CheckPages\Handlers\Form\KeyLabelNode
   */
  private function getValueFromSelectElement(DOMElement $el): ?KeyLabelNode {
    $option = $this->getSelectedOrFirstOption($el);
    if (!$option instanceof DOMElement) {
      return NULL;
    }

    return new KeyLabelNode(
      $option->getAttribute('value'),
      $option->textContent,
    );
  }

  /**
   * @param \DOMElement $el
   *
   * @return \AKlump\CheckPages\Handlers\Form\KeyLabelNode
   */
  private function getValueFromRadioElement(DOMElement $el): ?KeyLabelNode {
    $input = $this->getCheckedOrFirstInput($el);
    $id = $input->getAttribute('id');
    $label = $this->getForm()->filter('label[for=' . $id . ']');

    return new KeyLabelNode(
      $input->getAttribute('value'),
      $label->text(),
    );
  }

  private function getValueFromCheckboxElement(DOMElement $el): ?KeyLabelNode {
    $checked = strtolower($el->getAttribute('checked'));
    $id = $el->getAttribute('id');
    $label = $this->getForm()->filter('label[for=' . $id . ']');

    return new KeyLabelNode(
      $checked ? $el->getAttribute('value') : '', $label->text());
  }

  /**
   * @param \DOMElement $select_el
   *
   * @return \DOMElement|null
   */
  private function getSelectedOrFirstOption(DOMElement $select_el): ?DOMElement {
    $crawler = new Crawler($select_el);
    $selected = $crawler->filter('option[selected]');
    if ($selected->count() > 0) {
      return $selected->getNode(0);
    }

    $first_option = $crawler->filter('option');
    if ($first_option->count() > 0) {
      return $first_option->getNode(0);
    }

    return NULL;
  }

  private function getCheckedOrFirstInput(DOMElement $el): ?DOMElement {
    $name = $el->getAttribute('name');
    $form = $this->getForm();
    $input = $form
      ->filter(sprintf('input[name="%s"][checked=checked]', $name));
    if ($input->count() === 0) {
      $input = $form->filter(sprintf('input[name="%s"]', $name));
    }
    if ($input->count() > 0) {
      return $input->getNode(0);
    }

    return NULL;
  }

}
