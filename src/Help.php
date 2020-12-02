<?php

namespace AKlump\CheckPages;

class Help implements HelpInfoInterface {

  protected $code;

  protected $constant;

  protected $description;

  protected $examples = [];

  public function __construct(string $class_constant, string $code, string $description, array $examples) {
    $this->constant = $class_constant;
    $this->code = $code;
    $this->description = $description;
    $this->examples = $examples;
  }

  public function code(): string {
    return $this->code;
  }

  public function description(): string {
    return $this->description;
  }

  public function examples(): array {
    return $this->examples;
  }

}
