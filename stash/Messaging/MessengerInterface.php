<?php

namespace AKlump\Messaging;

interface MessengerInterface {

  /**
   * Add a callback to process a message before deliver.
   *
   * @param callable $callback
   *   Will receive two arguments 1) the lines of the message, which may have
   *   already been changed by an earlier processor and 2) the original $message
   *   object. The callback MUST return an array which becomes the new set of
   *   lines to be passed to the next processor, or delivered.
   *
   * @return $this
   *   Fluid interface.
   */
  public function addProcessor(callable $callback): self;

  /**
   * Deliver the message after any processing that exists.
   *
   * @param \AKlump\Messaging\MessageInterface $message
   * @param int|NULL $flags
   *
   * @return mixed
   */
  public function deliver(MessageInterface $message, int $flags = NULL);
}
