<?php

namespace AKlump\CheckPages\Handlers\Form;

use InvalidArgumentException;

class FormValuesManager {

  const OPTION_ALLOW_NON_FORM_KEYS = 0;

  const OPTION_BLOCK_NON_FORM_KEYS = 1;

  private array $config;

  private array $formValues;

  private int $options;

  /**
   * @param int $options
   *
   * @see self::OPTION_ALLOW_NON_FORM_KEYS
   */
  public function __construct(int $options = 0) {
    $this->options = $options;
  }

  public function setFormValues(array $form_values): void {
    $this->formValues = $form_values;
  }

  public function setConfig(array $config): void {
    if (!empty($config['form']['input'])) {
      if (!is_numeric(key($config['form']['input']))) {
        throw new InvalidArgumentException('form.input must be an indexed array');
      }
      foreach ($config['form']['input'] as $value) {
        if (!isset($value['name'])) {
          throw new InvalidArgumentException('form.input[*].name is missing');
        }
        $value_key = array_intersect_key($value, [
          'value' => 1,
          'option' => 1,
        ]);
        if (empty($value_key)) {
          throw new InvalidArgumentException('form.input[*].value || form.input[*].option is required');
        }
      }
    }
    $this->config = $config;
  }

  public function getHttpQueryString(): string {
    $values = [];
    $test_provided_values = $this->extractValuesFromConfig($this->config);
    if ($this->options & self::OPTION_BLOCK_NON_FORM_KEYS) {
      // Filter out keys from config that are not in the form.
      $test_provided_values = array_intersect_key($test_provided_values, $this->formValues);
    }
    $values += $test_provided_values;
    $values += $this->formValues;

    // Reorder $values based on the order of $this->formValues keys.
    $ordered_values = array_merge(array_flip(array_keys($this->formValues)), $values);

    return http_build_query($ordered_values);
  }

  private function extractValuesFromConfig(array $config): array {
    $values = [];
    if (empty($config['form']['input'])) {
      return [];
    }
    foreach ($config['form']['input'] as $data) {
      $name = $data['name'];
      $value = $data['value'] ?? $data['option'] ?? NULL;
      if (isset($this->formValues[$name])
        && $this->formValues[$name] instanceof KeyLabelNode) {
        $this->formValues[$name]->mutateToKey($value);
      }
      $values[$name] = $value;
    }

    return $values;
  }

}
