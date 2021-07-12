<?php


namespace AKlump\CheckPages\Parts;


interface PartInterface {

  public function id(): string;

  public function getConfig(): array;

}
