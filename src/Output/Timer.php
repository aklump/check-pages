<?php

namespace AKlump\CheckPages\Output;

final class Timer {

  private $startTime;

  private $timezone;

  public function __construct(\DateTimeZone $timezone) {
    $this->timezone = $timezone;
  }

  public function start(): self {
    $this->startTime = microtime(TRUE);

    return $this;
  }

  public function getElapsed(): string {
    $elapsed = microtime(TRUE) - $this->startTime;
    if ($elapsed < 60) {
      return round($elapsed, 1) . ' seconds';
    }

    return round($elapsed / 60, 1) . ' minutes';
  }

  public function getCurrent(): string {
    return date_create('now', $this->timezone)->format('Y-n-j G:i');
  }
}
