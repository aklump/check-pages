<?php

namespace AKlump\CheckPages\Handlers\Form;

use AKlump\CheckPages\Exceptions\DeprecatedSyntaxException;
use InvalidArgumentException;

class FormValuesManager {

  const OPTION_ALLOW_NON_FORM_KEYS = 0;

  const OPTION_BLOCK_NON_FORM_KEYS = 1;

  private array $config = [];

  private array $formValues = [];

  private int $options;

  private array $allowedValues;

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

  public function setAllowedValues(array $allowed_values): void {
    $this->allowedValues = $allowed_values;
  }

  public function setConfig(array $config): void {
    if (!empty($config['form']['input'])) {
      if (!is_numeric(key($config['form']['input']))) {
        throw new InvalidArgumentException('form.input must be an indexed array');
      }
      foreach ($config['form']['input'] as $index => $value) {
        if (empty($value['name'])) {
          throw new InvalidArgumentException("form.input[$index].name is missing");
        }
        if (array_key_exists('option', $value)) {
          throw new DeprecatedSyntaxException("form.input[$index].option", "form.input[$index].value");
        }
        if (!array_key_exists('value', $value)) {
          throw new InvalidArgumentException("form.input[$index].value is required");
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
    foreach ($config['form']['input'] as $index => $data) {
      $name = $data['name'];
      $value = $data['value'];

      // Make sure the value is allowed.
      if (isset($this->allowedValues[$name])) {
        $is_allowed = FALSE;
        foreach ($this->allowedValues[$name] as $allowed_value) {
          $matched_value = $this->matchConfigValueToFormValue($value, $allowed_value);
          if (NULL !== $matched_value) {
            $is_allowed = TRUE;
            $value = $matched_value;
            break;
          }
        }
        if (!$is_allowed) {
          throw new InvalidArgumentException("form.input[$index].value is one of allowed values: %s", implode(', ', $this->allowedValues[$name]));
        }
      }
      elseif (isset($this->formValues[$name])
        && $this->formValues[$name] instanceof KeyLabelNode) {
        $matched_value = $this->matchConfigValueToFormValue($value, $this->formValues[$name]);
        if (NULL === $matched_value) {
          throw new InvalidArgumentException(sprintf('Config value form.input[%d][%s]=%s is not a valid form key or case-insensitive label matching "%s"', $index, $name, $value, $this->formValues[$name]));
        }
        $value = $matched_value;
      }
      $values[$name] = $value;
    }

    return $values;
  }


  private function matchConfigValueToFormValue($value, KeyLabelNode $form_input) {
    $key = $form_input->getKey();
    if ($value == $key) {
      return $key;
    }
    if (strcasecmp($form_input->getLabel(), $value) === 0) {
      return $key;
    }
  }

}
