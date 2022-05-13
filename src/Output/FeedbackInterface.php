<?php

namespace AKlump\CheckPages\Output;

/**
 * A leaner interface for user feedback.
 *
 * @see \Symfony\Component\Console\Output\OutputInterface
 */
interface FeedbackInterface {

  /**
   * Writes a message to the output and adds a newline at the end.
   *
   * @param string|iterable $messages The message as an iterable of strings or a single string
   * @param int $options A bitmask of options (one of the OUTPUT or VERBOSITY constants), 0 is considered the same as self::OUTPUT_NORMAL | self::VERBOSITY_NORMAL
   */
  public function writeln($messages, int $options = 0);

  /**
   * Writes a message to the output.
   *
   * @param string|iterable $messages The message as an iterable of strings or a single string
   * @param bool $newline Whether to add a newline
   * @param int $options A bitmask of options (one of the OUTPUT or VERBOSITY constants), 0 is considered the same as self::OUTPUT_NORMAL | self::VERBOSITY_NORMAL
   */
  public function write($messages, bool $newline = FALSE, int $options = 0);
}
