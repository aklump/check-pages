<?php

namespace AKlump\CheckPages\Tests\Unit\Output\Message;

use AKlump\CheckPages\Exceptions\StopRunnerException;
use AKlump\CheckPages\Output\Message\ExceptionMessage;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\Messaging\MessageType;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\Output\Message\ExceptionMessage
 * @uses \AKlump\CheckPages\Output\Message\Message
 * @uses \AKlump\CheckPages\Output\VerboseDirective
 * @uses \AKlump\Messaging\MessageBase
 * @uses \AKlump\CheckPages\Exceptions\StopRunnerException
 */
class ExceptionMessageTest extends TestCase {

  public function testMessageExtraction() {
    $e = new Exception('Main message');
    $msg = new ExceptionMessage($e, Verbosity::NORMAL, 'Fallback');
    $this->assertSame(['Main message'], $msg->getMessage());
  }

  public function testDeepMessageExtraction() {
    $e1 = new Exception('Deep message');
    $e2 = new Exception('', 0, $e1);
    $e3 = new Exception('', 0, $e2);

    $msg = new ExceptionMessage($e3, Verbosity::NORMAL, 'Fallback');
    $this->assertSame(['Deep message'], $msg->getMessage());
  }

  public function testSearchDepthLimit() {
    // SEARCH_DEPTH is 4.
    // e1 (message)
    // e2 (prev e1)
    // e3 (prev e2)
    // e4 (prev e3)
    // e5 (prev e4)
    // e6 (prev e5) - this is depth 5 from e6.

    $e1 = new Exception('Found me');
    $e2 = new Exception('', 0, $e1);
    $e3 = new Exception('', 0, $e2);
    $e4 = new Exception('', 0, $e3);
    $e5 = new Exception('', 0, $e4);
    $e6 = new Exception('', 0, $e5);

    // From e5, e1 is at depth 4.
    $msg = new ExceptionMessage($e5, Verbosity::NORMAL, 'Fallback');
    $this->assertSame(['Found me'], $msg->getMessage());

    // From e6, e1 is at depth 5. Should hit limit and use fallback.
    $msg = new ExceptionMessage($e6, Verbosity::NORMAL, 'Fallback');
    $this->assertSame(['Fallback'], $msg->getMessage());
  }

  public function testStopRunnerException() {
    $e = new StopRunnerException('Stop now');
    $msg = new ExceptionMessage($e, Verbosity::NORMAL, 'Fallback');
    $this->assertSame(MessageType::EMERGENCY, $msg->getMessageType());
    $this->assertFalse($msg->getVerboseDirective()->showVerbose());
  }

  public function testNormalException() {
    $e = new Exception('Error');
    $msg = new ExceptionMessage($e, Verbosity::NORMAL, 'Fallback');
    $this->assertSame(MessageType::ERROR, $msg->getMessageType());
    $this->assertFalse($msg->getVerboseDirective()->showVerbose());
  }

  public function testFallbackWhenNoMessageFound() {
    $e = new Exception('');
    $msg = new ExceptionMessage($e, Verbosity::NORMAL, 'Fallback');
    $this->assertSame(['Fallback'], $msg->getMessage());
  }
}
