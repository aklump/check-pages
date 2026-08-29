<?php

namespace AKlump\CheckPages\TAP;

/**
 * An interface to support TAP test interaction.
 *
 * @url https://testanything.org/tap-specification.html
 */
interface TestInterface {

  public function getDescription(): string;

  public function getDirective(): Directive;

  public function getNumber(): int;

  public function getResult(): Result;

  public function setDescription(string $description): TestInterface;

  public function setDirective(Directive $directive): TestInterface;

  public function setNumber(int $number): TestInterface;

  public function setResult(Result $result): TestInterface;
}
