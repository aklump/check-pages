<?php

namespace AKlump\CheckPages;

use AKlump\CheckPages\Event\TestRunstateEvent;
use AKlump\CheckPages\Parts\Test;
use AKlump\CheckPages\TAP\Directive;
use AKlump\CheckPages\TAP\Result;
use Symfony\Component\EventDispatcher\EventDispatcher;

final class TestRunState implements \JsonSerializable {

  private EventDispatcher $dispatcher;

  private Test $test;

  private Result $result;

  private Directive $directive;

  /**
   * @param \Symfony\Component\EventDispatcher\EventDispatcher $dispatcher
   */
  public function __construct(EventDispatcher $dispatcher) {
    $this->dispatcher = $dispatcher;
  }

  public function getDirective(): ?Directive {
    return $this->directive ?? NULL;
  }

  public function setDirective(Directive $directive) {
    $this->setWithDispatch(fn() => $this->directive = $directive);
  }

  private array $files = [];

  public function setResult(Result $result): void {
    $this->setWithDispatch(fn() => $this->result = $result);
  }

  public function getTest(): Test {
    return $this->test;
  }

  public function getResult(): ?Result {
    return $this->result ?? NULL;
  }

  public function getFiles(): array {
    return $this->files;
  }

  public function setTest(Test $test) {
    $this->setWithDispatch(function () use ($test) {
      $this->test = $test;
      // Setting on the property directly to avoid dispatching events
      $this->result = Result::NONE;
      $this->directive = Directive::NONE;
      $this->files = [];
    });
  }

  public function addFile(string $label, string $path): void {
    $this->setWithDispatch(fn() => $this->files[] = [
      'label' => $label,
      'path' => $path,
    ]);
  }

  public function jsonSerialize(): array {
    return array_filter([
      'result' => $this->getResult()?->value,
      'directive' => $this->getDirective()?->value,
      'files' => $this->files,
    ], fn($value) => $value !== NULL);
  }

  private function setWithDispatch(callable $callable) {
    $before = clone $this;
    $callable();
    if ($before != $this) {
      $this->dispatcher->dispatch(new TestRunstateEvent($this, $before), Event::TEST_RUNSTATE_CHANGED);
    }
  }
}
