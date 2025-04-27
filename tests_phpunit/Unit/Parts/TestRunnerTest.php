<?php

namespace AKlump\CheckPages\Tests\Unit\Parts;

use AKlump\CheckPages\Parts\Suite;
use AKlump\CheckPages\Parts\TestRunner;
use AKlump\CheckPages\Tests\TestingTraits\TestWithDispatcherTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\Parts\TestRunner
 * @uses   \AKlump\CheckPages\Event\TestEvent
 * @uses   \AKlump\CheckPages\Files\LocalFilesProvider
 * @uses   \AKlump\CheckPages\Handlers\AddHandlerAutoloads
 * @uses   \AKlump\CheckPages\Handlers\CommandLineTestHandlerBase
 * @uses   \AKlump\CheckPages\Helpers\BuildContainer
 * @uses   \AKlump\CheckPages\Output\Feedback
 * @uses   \AKlump\CheckPages\Output\Message
 * @uses   \AKlump\CheckPages\Output\SaveResponseToFile
 * @uses   \AKlump\CheckPages\Output\VerboseDirective
 * @uses   \AKlump\CheckPages\Parts\Runner
 * @uses   \AKlump\CheckPages\Parts\Suite
 * @uses   \AKlump\CheckPages\Parts\Test
 * @uses   \AKlump\CheckPages\Plugin\HandlersManager
 * @uses   \AKlump\CheckPages\Service\DispatcherFactory
 * @uses   \AKlump\CheckPages\Service\InterpolationService
 * @uses   \AKlump\CheckPages\Service\Retest
 * @uses   \AKlump\CheckPages\Service\SecretsService
 * @uses   \AKlump\CheckPages\Service\SuiteIndexService
 * @uses   \AKlump\CheckPages\SuiteValidator
 * @uses   \AKlump\CheckPages\Traits\HasConfigTrait
 * @uses   \AKlump\CheckPages\Traits\HasRunnerTrait
 * @uses   \AKlump\CheckPages\Traits\PassFailTrait
 * @uses   \AKlump\CheckPages\Traits\SetTrait
 * @uses   \AKlump\CheckPages\Variables
 * @uses   \AKlump\CheckPages\Handlers\Form
 */
class TestRunnerTest extends TestCase {

  use TestWithDispatcherTrait;

  public function testWhyGetsInterpolated() {
    $runner = $this->getRunner();
    $suite = new Suite('test', $runner);
    $suite->addTestByConfig([
      'set' => 'title',
      'value' => 'lorem',
    ]);
    $suite->addTestByConfig([
      'why' => 'Test for ${title}',
    ]);
    foreach ($suite->getTests() as $test) {
      (new TestRunner($test))->start();
    }
    $this->assertNotEmpty($test);
    $this->assertSame('Test for lorem', $test->get('why'));
  }

}
