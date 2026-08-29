<?php

namespace AKlump\CheckPages\Tests\Unit\Output;

use AKlump\CheckPages\Output\Flags;
use AKlump\CheckPages\Output\Message\Message;
use AKlump\CheckPages\Output\Messenger\SymfonyStyleConsolePrinter;
use AKlump\CheckPages\Output\VerboseDirective;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\CheckPages\Messaging\MessageInterface;
use AKlump\CheckPages\Messaging\MessageType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @group default
 * @covers \AKlump\CheckPages\Output\Messenger\SymfonyStyleConsolePrinter
 * @uses   \AKlump\CheckPages\Output\Message\Message
 * @uses   \AKlump\CheckPages\Output\VerboseDirective
 * @uses   \AKlump\CheckPages\Helpers\GetRealStringLength
 */
final class SymfonyStyleConsolePrinterTest extends TestCase {

  public function testInvertFirstAllowsSecondEmptyLine() {
    $output = new BufferedOutput();
    $printer = new SymfonyStyleConsolePrinter($output);
    $printer->writeln(new Message([
      'lorem ipsum',
      '',
    ]), Flags::INVERT_FIRST_LINE);
    $text = $output->fetch();
    $this->assertSame(2, substr_count($text, PHP_EOL));
  }

  public function testInvertFirstDoesNotAddALine() {
    $output = new BufferedOutput();
    $printer = new SymfonyStyleConsolePrinter($output);
    $printer->writeln(new Message(['lorem ipsum']), Flags::INVERT_FIRST_LINE);
    $text = $output->fetch();
    $this->assertSame(1, substr_count($text, PHP_EOL));
  }

  public function testGetVerbosity() {
    $output = new BufferedOutput();
    $printer = new SymfonyStyleConsolePrinter($output);
    $this->assertSame(Verbosity::NORMAL, $printer->getVerbosity());

    $output = new BufferedOutput();
    $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
    $printer = new SymfonyStyleConsolePrinter($output);
    $this->assertSame(Verbosity::VERBOSE, $printer->getVerbosity());

    $output = new BufferedOutput();
    $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
    $printer = new SymfonyStyleConsolePrinter($output);
    $this->assertSame(Verbosity::VERY_VERBOSE, $printer->getVerbosity());

    $output = new BufferedOutput();
    $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
    $printer = new SymfonyStyleConsolePrinter($output);
    $this->assertSame(Verbosity::DEBUG, $printer->getVerbosity());
  }

  public function testInstantiateWithSymfonyStyle() {
    $output = new BufferedOutput();
    $io = new SymfonyStyle(new ArrayInput([]), $output);
    $printer = new SymfonyStyleConsolePrinter($io);
    $printer->writeln(new Message(['Hello World']));
    $text = $output->fetch();
    $this->assertStringContainsString('Hello World', $text);
  }

  public function testBannerDelivery() {
    $output = new BufferedOutput();
    $printer = new SymfonyStyleConsolePrinter($output);
    $printer->writeln(new Message(['BANNER TEXT'], MessageType::BANNER));
    $text = $output->fetch();
    $this->assertStringContainsString('BANNER TEXT', $text);
    $this->assertSame(3, substr_count($text, PHP_EOL));
  }

  public function testDeliverWithInvertFlag() {
    $output = new BufferedOutput();
    $printer = new SymfonyStyleConsolePrinter($output);
    $printer->writeln(new Message(['Inverted text'], MessageType::SUCCESS), Flags::INVERT);
    $text = $output->fetch();
    $this->assertStringContainsString('Inverted text', $text);
  }

  public function testEmergencyMessageAlteration() {
    $output = new BufferedOutput();
    $printer = new SymfonyStyleConsolePrinter($output);
    $printer->writeln(new Message(['CRITICAL ERROR'], MessageType::EMERGENCY));
    $text = $output->fetch();
    $this->assertStringContainsString('CRITICAL ERROR', $text);
  }

  public function testVerbosityFiltering() {
    $output = new BufferedOutput();
    $printer = new SymfonyStyleConsolePrinter($output);
    $message = new Message(['Debug info'], MessageType::DEBUG, Verbosity::VERY_VERBOSE);
    $printer->writeln($message);
    $text = $output->fetch();
    $this->assertEmpty($text);

    $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
    $printerVerbose = new SymfonyStyleConsolePrinter($output);
    $printerVerbose->writeln($message);
    $text = $output->fetch();
    $this->assertStringContainsString('Debug info', $text);
  }
}
