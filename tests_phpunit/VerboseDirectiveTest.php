<?php

use AKlump\CheckPages\Assert;
use AKlump\CheckPages\Output\VerboseDirective;
use PHPUnit\Framework\TestCase;

/**
 * @group default
 * @covers \AKlump\CheckPages\Output\VerboseDirective
 */
final class VerboseDirectiveTest extends TestCase {

  public function testGetTotalIntersection() {
    $directive = VerboseDirective::getTotalIntersection();
    $this->assertIsString($directive);
    $this->assertNotEmpty($directive);
  }

  /**
   * Provides data for testBadFormatThrows.
   */
  public function dataForTestBadFormatThrowsProvider() {
    $tests = [];
    $tests[] = ['AH'];
    $tests[] = ['AS'];
    $tests[] = ['AR'];
    $tests[] = ['ASRH'];
    $tests[] = ['ARH'];
    $tests[] = ['ASH'];

    return $tests;
  }

  /**
   * @dataProvider dataForTestBadFormatThrowsProvider
   */
  public function testBadFormatThrows(string $directive) {
    $this->expectException(InvalidArgumentException::class);
    new VerboseDirective($directive);
  }

  public function testToString() {
    $obj = new VerboseDirective('DVA');
    $this->assertSame('ADV', strval($obj));
  }

  /**
   * Provides data for testIntersectsWith.
   *
   * Be aware that this will be compared 0 => 1 AND 1 => 0 so you don't need to
   * provide those permutations.
   */
  public function dataForTestIntersectsWithProvider() {
    $tests = [];
    $tests[] = ['', '', TRUE];

    $tests[] = ['A', '', FALSE];
    $tests[] = ['A', 'H', TRUE];

    $tests[] = ['A', 'S', TRUE];

    $tests[] = ['A', 'R', TRUE];

    $tests[] = ['A', 'SH', TRUE];
    $tests[] = ['A', 'HS', TRUE];

    $tests[] = ['A', 'RH', TRUE];
    $tests[] = ['A', 'HR', TRUE];

    $tests[] = ['SH', 'SH', TRUE];
    $tests[] = ['HS', 'SH', TRUE];
    $tests[] = ['SH', '', FALSE];
    $tests[] = ['HS', '', FALSE];

    $tests[] = ['RH', 'RH', TRUE];
    $tests[] = ['HR', 'RH', TRUE];
    $tests[] = ['RH', '', FALSE];
    $tests[] = ['HR', '', FALSE];

    $tests[] = ['A', 'SRH', TRUE];
    $tests[] = ['A', 'HSR', TRUE];
    $tests[] = ['A', 'RHS', TRUE];
    $tests[] = ['', 'SRH', FALSE];
    $tests[] = ['', 'HSR', FALSE];
    $tests[] = ['', 'RHS', FALSE];

    $tests[] = ['', 'H', FALSE];
    $tests[] = ['', 'S', FALSE];
    $tests[] = ['', 'R', FALSE];

    $tests[] = ['H', 'S', FALSE];
    $tests[] = ['H', 'R', FALSE];

    return $tests;
  }

  /**
   * @dataProvider dataForTestIntersectsWithProvider
   */
  public function testIntersectsWith(string $a, string $b, bool $expected) {
    $a = new VerboseDirective($a);
    $b = new VerboseDirective($b);
    $this->assertSame($expected, $a->intersectsWith($b));
    $this->assertSame($expected, $b->intersectsWith($a));
  }

  /**
   * Provides data for testShowMethods.
   */
  public function dataForTestIsVerboseProvider() {
    $tests = [];
    $tests[] = [
      '',
      [
        'showVerbose' => FALSE,
        'showDebugging' => FALSE,
        'showResponseBody' => FALSE,
        'showResponseHeaders' => FALSE,
        'showSendBody' => FALSE,
        'showSendHeaders' => FALSE,
      ],
    ];
    $tests[] = [
      'D',
      [
        'showVerbose' => FALSE,
        'showDebugging' => TRUE,
        'showResponseBody' => FALSE,
        'showResponseHeaders' => FALSE,
        'showSendBody' => FALSE,
        'showSendHeaders' => FALSE,
      ],
    ];
    $tests[] = [
      'A',
      [
        'showVerbose' => FALSE,
        'showDebugging' => FALSE,
        'showResponseBody' => TRUE,
        'showResponseHeaders' => TRUE,
        'showSendBody' => TRUE,
        'showSendHeaders' => TRUE,
      ],
    ];
    $tests[] = [
      'S',
      [
        'showVerbose' => FALSE,
        'showDebugging' => FALSE,
        'showResponseBody' => FALSE,
        'showResponseHeaders' => FALSE,
        'showSendBody' => TRUE,
        'showSendHeaders' => FALSE,
      ],
    ];
    $tests[] = [
      'R',
      [
        'showVerbose' => FALSE,
        'showDebugging' => FALSE,
        'showResponseBody' => TRUE,
        'showResponseHeaders' => FALSE,
        'showSendBody' => FALSE,
        'showSendHeaders' => FALSE,
      ],
    ];
    $tests[] = [
      'H',
      [
        'showVerbose' => FALSE,
        'showDebugging' => FALSE,
        'showResponseBody' => FALSE,
        'showResponseHeaders' => TRUE,
        'showSendBody' => FALSE,
        'showSendHeaders' => TRUE,
      ],
    ];
    $tests[] = [
      'SH',
      [
        'showVerbose' => FALSE,
        'showDebugging' => FALSE,
        'showSendBody' => TRUE,
        'showResponseHeaders' => FALSE,
        'showResponseBody' => FALSE,
        'showSendHeaders' => TRUE,
      ],
    ];
    $tests[] = [
      'RH',
      [
        'showVerbose' => FALSE,
        'showDebugging' => FALSE,
        'showSendBody' => FALSE,
        'showResponseHeaders' => TRUE,
        'showResponseBody' => TRUE,
        'showSendHeaders' => FALSE,
      ],
    ];

    return $tests;
  }

  /**
   * @dataProvider dataForTestIsVerboseProvider
   */
  public function testShowMethods(string $directive, array $methods) {
    $d = new VerboseDirective($directive);
    foreach ($methods as $method => $expected) {
      $this->assertSame($expected, $d->$method());
    }
  }

}
