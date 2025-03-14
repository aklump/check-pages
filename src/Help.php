<?php

namespace AKlump\CheckPages;

class Help implements HelpInfoInterface {

  protected string $code;

  protected string $description;

  protected array $examples;

  public function __construct(string $code, string $description, array $examples) {
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
