<?php

namespace AKlump\CheckPages\Messaging;

use Symfony\Component\Console\Output\ConsoleSectionOutput;

interface SectionAwareMessengerInterface {

  /**
   * Creates or returns an existing section by message type.
   *
   * @param string $messageType
   *
   * @return \Symfony\Component\Console\Output\ConsoleSectionOutput
   *
   * Create a new group of sections for output:
   * @code
   * $this->getMessenger()
   *   ->resetSections()
   *   ->section(MessageType::TEST_RUN_STATE_RESULT)
   *   ->section(MessageType::TEST_RUN_STATE_FILES);
   * @endcode
   */
  public function section(string $messageType): ConsoleSectionOutput;

  /**
   * Removes all output section tracking, but leaves content as is.
   *
   * This should be called if you want to start over with a new set of sections.
   *
   * @return void
   */
  public function resetSections(): void;
}
