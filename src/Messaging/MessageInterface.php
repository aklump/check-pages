<?php

namespace AKlump\CheckPages\Messaging;

interface MessageInterface {

  /**
   * Get the message broken into lines.
   *
   * @return array
   *   Each element is presumed to be a single line.
   */
  public function getMessage(): array;

  public function setMessage(array $message): self;

  public function setIcon(string $icon): self;

  public function getIcon(): string;

  /**
   * Get the message type.
   *
   * @return string
   *
   * @see \AKlump\CheckPages\Messaging\MessageType
   */
  public function getMessageType(): string;

  public function setMessageType(string $type): self;

  /**
   * @return string
   *   The stringified version of the message which includes the icon if present.
   */
  public function __toString(): string;

  /**
   * @return int
   *
   * @see \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_NORMAL
   * @see \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_VERBOSE
   * @see \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_VERY_VERBOSE
   * @see \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_DEBUG
   */
  public function getVerbosity(): int;

}
