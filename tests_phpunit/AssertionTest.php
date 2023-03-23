<?php

use AKlump\CheckPages\Plugin\Dom;
use AKlump\CheckPages\Variables;
use PHPUnit\Framework\TestCase;
use AKlump\CheckPages\Service\Assertion;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @group default
 * @covers \AKlump\CheckPages\Assert
 */
final class AssertionTest extends TestCase {

  public function testSetHaystackWithDifferentTypes() {
    $obj = new Assertion(['dom' => 'em', 'is' => 'dinner']);
    $obj->setHaystack('<p>It is surely time for <em>dinner</em>!</p>')->run();
    $this->assertTrue($obj->hasPassed());

    $obj = new Assertion(['dom' => 'em', 'is' => 'dinner']);
    $obj->setHaystack(['<p>It is surely time for <em>dinner</em>!</p>'])->run();
    $this->assertTrue($obj->hasPassed());

    $obj = new Assertion(['dom' => 'em', 'is' => 'dinner']);
    $crawler = new Crawler('<html><p>It is surely time for <em>dinner</em>!</p></html>');
    $obj->setHaystack($crawler);
    $obj->run();
    $this->assertTrue($obj->hasPassed());
  }

  /**
   * Provides data for testSetGetNeedle.
   */
  public function dataForTestSetGetNeedleProvider() {
    $tests = [];
    $tests[] = [
      [
        'xpath' => '(//*[contains(@class, "foo")])[2]',
        'is' => 're'
      ],
      ['<div class="foo">do</div><div class="foo">re</div><div class="foo">mi</div>'],
    ];
    $tests[] = [
      [
        'dom' => 'p',
        'count' => '>1',
      ],
      ['<p>lorem</p><p>ipsum</p>'],
    ];

    $tests[] = [
      [
        'value' => 'bob',
        'matches' => '/^b.+b$/',
      ],
      ['lorem ipsum'],
    ];
    $tests[] = [
      [
        'dom' => 'h1',
        'count' => 1,
      ],
      ['<h1>Robots Are Taking Over</h1>'],
    ];
    $tests[] = [
      [
        'dom' => 'h1',
        'is' => 'Robots Are Taking Over',
      ],
      ['<h1>Robots Are Taking Over</h1>'],
    ];
    $tests[] = [
      [
        'text' => 'A Dark Night',
      ],
      ['<h1>A Dark Night</h1>'],
    ];
    $tests[] = [
      [
        'not matches' => '/^dawn.+/',
      ],
      ['forestation'],
    ];
    $tests[] = [
      [
        'matches' => '/forest.+$/',
      ],
      ['forestation'],
    ];
    $tests[] = [
      [
        'is not' => 'bar',
      ],
      ['foo'],
    ];
    $tests[] = [
      [
        'is' => 'bar',
      ],
      ['bar'],
    ];
    $tests[] = [
      [
        'contains' => 'bar',
      ],
      ['foo bar baz'],
    ];
    $tests[] = [
      [
        'contains' => 'bar',
      ],
      ['foo', 'bar', 'baz'],
    ];
    $tests[] = [
      [
        'not contains' => 'bar',
      ],
      ['foo'],
    ];

    return $tests;
  }

  /**
   * @dataProvider dataForTestSetGetNeedleProvider
   */
  public function testSetGetNeedle(array $config, array $haystack) {
    $this->assertTrue(Assertion::create($config)->runAgainst($haystack));
  }

  public function testPassingDomListenerClassWorksAsExpected() {
    $obj = new Assertion([
      'dom' => 'h1',
      'is' => 'title',
    ], []);
    $obj->setHaystack(['<h1>title</h1>'])->run();
    $this->assertTrue($obj->hasFailed());

    $obj = new Assertion([
      'dom' => 'h1',
      'is' => 'title',
    ], [Dom::class]);
    $obj->setHaystack(['<h1>title</h1>'])->run();
    $this->assertTrue($obj->hasPassed());
  }

  public function testThrowsOnRunBeforeHaystack() {
    $this->expectException(RuntimeException::class);
    $obj = new Assertion([]);
    $obj->run();
  }

  public function testSecondRunThrows() {
    $this->expectException(RuntimeException::class);
    $obj = new Assertion([]);
    $obj->setHaystack([])->run()->run();
  }

}
