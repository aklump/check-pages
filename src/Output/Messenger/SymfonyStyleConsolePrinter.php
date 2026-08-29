<?php

namespace AKlump\CheckPages\Output\Messenger;

use AKlump\CheckPages\Messaging\SectionAwareMessengerInterface;
use AKlump\CheckPages\Output\VerboseDirective;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\CheckPages\View\DefaultTheme;
use AKlump\CheckPages\Messaging\MessageInterface;
use AKlump\CheckPages\Messaging\MessageType;
use AKlump\CheckPages\Messaging\MessengerInterface;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Prints test feedback to the CLI console using SymfonyStyle.
 */
class SymfonyStyleConsolePrinter implements MessengerInterface, SectionAwareMessengerInterface {

  const MAX_LINE_LENGTH = 80;

  private MessengerInterface $messenger;

  /**
   * @var \AKlump\CheckPages\Output\VerboseDirective
   */
  private VerboseDirective $directive;

  /**
   * @var \Symfony\Component\Console\Output\OutputInterface
   */
  private OutputInterface $output;

  /**
   * @var \Symfony\Component\Console\Style\SymfonyStyle
   */
  private SymfonyStyle $io;

  private array $outputSections = [];

  public function __construct(OutputInterface $output) {
    $this->output = $output;
    if ($output instanceof SymfonyStyle) {
      $this->io = $output;
    }
    else {
      try {
        $this->io = new SymfonyStyle(new ArrayInput([]), $output);
      }
      catch (\Error $e) {
        // Fallback for non-cloneable output objects (e.g. mocks in tests).
        $this->io = new SymfonyStyle(new ArrayInput([]), new \Symfony\Component\Console\Output\NullOutput());
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * @see \AKlump\CheckPages\Output\Flags
   */
  public function writeln(MessageInterface $message): void {

    // Check if the message verbosity matches the CLI verbosity.
    if ($this->output->getVerbosity() < $message->getVerbosity()) {
      return;
    }

    $message_type = $message->getMessageType();
    $this->themeMessage($message, $message_type);

    //    if ($flags & Flags::BANNER) {
    //      MessageProcessor::indent($message, 3);
    //      MessageProcessor::fullWidth($message);
    //      MessageProcessor::topMargin($message);
    //      MessageProcessor::bottomMargin($message);
    //      $lines = explode(PHP_EOL, $message->__toString());
    //      $tag = $this->getTag($message_type, TRUE);
    //    }
    //    elseif ($flags & Flags::INVERT_FIRST_LINE) {
    //      if (!empty($lines)) {
    //        $first_line = array_shift($lines);
    //
    //        // This gives a little right padding to our bg color.
    //        if (substr($first_line, -1) !== ' ') {
    //          $first_line .= ' ';
    //        }
    //        $first_line_tag = $this->getTag($message_type, TRUE);
    //        $this->io->writeln($this->wrapTag($first_line_tag, $first_line));
    //      }
    //    }
    if ('' !== strval($message)) {
      switch ($message_type) {
        case MessageType::SUITE_RUN_STATE_RESULT:
        case MessageType::TEST_RUN_STATE_RESULT:
        case MessageType::TEST_RUN_STATE_FILES:
          $this->section($message_type)
            ->overwrite($message);
          break;
        case MessageType::INFO:
          $this->io->info($message);
          break;
        case MessageType::TITLE:
          $this->io->title($message);
          break;
        case MessageType::WARNING:
          $this->io->warning($message);
          break;
        default:
          $tag = $tag ?? $this->getTag($message_type);
          $lines = explode(PHP_EOL, $message->__toString());
          foreach ($lines as $line) {
            $this->io->writeln($this->wrapTag($tag, $line));
          }
          break;
      }
    }
  }

  public function getVerbosity(): int {
    // Convert to the old style of verbosity (pre symfony)
    $outputVerbosity = $this->output->getVerbosity();

    return match ($outputVerbosity) {
      OutputInterface::VERBOSITY_DEBUG => Verbosity::DEBUG,
      OutputInterface::VERBOSITY_VERY_VERBOSE => Verbosity::VERY_VERBOSE,
      OutputInterface::VERBOSITY_VERBOSE => Verbosity::VERBOSE,
      default => Verbosity::NORMAL,
    };
  }

  private function getTagOptions(bool $invert = FALSE): array {
    $green = '#090';
    $red = '#AB3A1B';
    $white = '#fff';
    if ($invert) {
      return [
        MessageType::EMERGENCY => "fg=$red;bg=white",
        MessageType::ERROR => "fg=$white;bg=$red",
        MessageType::SUCCESS => "fg=$white;bg=$green",
        MessageType::INFO => "fg=$white;bg=blue",
        MessageType::DEBUG => "fg=black;bg=gray",
        MessageType::CODE => "fg=black;bg=gray",
        MessageType::TEST_SOURCE => "fg=black;bg=gray",
        MessageType::TODO => "fg=$white;bg=yellow",
        "skipped" => "fg=$white;bg=yellow",
      ];
    }

    return [
      MessageType::EMERGENCY => "fg=$white;bg=$red",
      MessageType::ERROR => "fg=$red",
      MessageType::SUCCESS => "fg=$green",
      MessageType::INFO => "fg=blue",
      MessageType::DIVIDER => "fg=blue;bg=blue",
      MessageType::DEBUG => "fg=#999",
      MessageType::CODE => "fg=#0f0;bg=#222",
      MessageType::TEST_SOURCE => "fg=#2C221E;bg=#F5F2EB",
      MessageType::TODO => "fg=yellow",
      MessageType::PASSED_ASSERTION => "fg=$green",
      MessageType::FAILED_ASSERTION => "fg=$red",
      "skipped" => "fg=yellow",
    ];
  }

  private function getTag(string $message_type, bool $invert = FALSE): string {
    $tags = $this->getTagOptions($invert);

    return $tags[$message_type] ?? $tags[MessageType::INFO];
  }

  private function wrapTag(string $tag, string $line): string {
    if ($line === '') {
      return '';
    }
    if ($tag === '') {
      return OutputFormatter::escape($line);
    }

    return sprintf('<%s>%s</>', $tag, OutputFormatter::escape($line));
  }

  private function themeMessage(MessageInterface $message, string $messageType): void {
    // TODO DI DefaultTheme eventually?
    $theme = new DefaultTheme();
    match ($messageType) {
      MessageType::BANNER => $theme->banner($message),
      MessageType::CODE => $theme->code($message),
      MessageType::DEBUG => $theme->debug($message),
      MessageType::DIVIDER => $theme->divider($message),
      MessageType::EMERGENCY => $theme->emergency($message),
      MessageType::ERROR => $theme->error($message),
      MessageType::FAILED_ASSERTION => $theme->failedAssertion($message),
      MessageType::INFO => $theme->info($message),
      MessageType::PASSED_ASSERTION => $theme->passedAssertion($message),
      MessageType::SKIPPED => $theme->skipped($message),
      MessageType::SUCCESS => $theme->success($message),
      MessageType::TEST_SOURCE => $theme->testSource($message),
      MessageType::TODO => $theme->todo($message),
      default => NULL,
    };
  }

  public function section(string $messageType): ConsoleSectionOutput {
    if (!method_exists($this->output, 'section')) {
      throw new \RuntimeException(sprintf(
        '%s requires an output object with a section() method; %s was given.',
        __METHOD__,
        get_debug_type($this->output)
      ));
    }

    $this->outputSections[$messageType] ??= $this->output->section();

    return $this->outputSections[$messageType];
  }

  public function resetSections(): void {
    $this->outputSections = [];
  }

}
