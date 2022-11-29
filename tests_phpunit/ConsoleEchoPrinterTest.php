<?php

use AKlump\CheckPages\Output\Message;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use AKlump\CheckPages\Output\ConsoleEchoPrinter;

/**
 * @group default
 * @covers \AKlump\CheckPages\Output\ConsoleEchoPrinter
 */
final class ConsoleEchoPrinterTest extends TestCase {

  public function testInvertFirstAllowsSecondEmptyLine() {
    ob_start();
    $this->getPrinter()
      ->deliver(new Message([
        'lorem ipsum',
        '',
      ]), \AKlump\CheckPages\Output\Flags::INVERT_FIRST_LINE);
    $output = ob_get_clean();
    $this->assertSame(2, substr_count($output, PHP_EOL));
  }

  public function testInvertFirstDoesNotAddALine() {
    ob_start();
    $this->getPrinter()
      ->deliver(new Message(['lorem ipsum']), \AKlump\CheckPages\Output\Flags::INVERT_FIRST_LINE);
    $output = ob_get_clean();
    $this->assertSame(1, substr_count($output, PHP_EOL));
  }

  private function getPrinter(): ConsoleEchoPrinter {
    $output = $this->createStub(OutputInterface::class);
    $output->method('writeln')
      ->willReturnCallback(function ($line) {
        echo $line . PHP_EOL;
      });

    return new ConsoleEchoPrinter($output);
  }
}
