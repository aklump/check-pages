<?php

namespace AKlump\CheckPages\Tests\Unit\Files;

use AKlump\CheckPages\CheckPages;
use AKlump\CheckPages\Files\FilesProviderInterface;
use AKlump\CheckPages\Files\LoadConfig;
use AKlump\CheckPages\Files\LocalFilesProvider;
use AKlump\CheckPages\Files\NotInResolvableDirectoryException;
use AKlump\CheckPages\Files\NotWriteableException;
use AKlump\CheckPages\Files\PathIsNotDirectoryException;
use AKlump\CheckPages\Files\ResolveRecursive;
use AKlump\CheckPages\Tests\TestingTraits\TestWithFilesTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;

/**
 * @covers \AKlump\CheckPages\Files\ResolveRecursive
 */
final class ResolveRecursiveTest extends TestCase {

  use TestWithFilesTrait;

  public function testInvokeResolvesExistingRelativePaths() {
    $base_path = $this->getTestFileFilepath('app/config/', TRUE);
    $alpha_path = $this->getTestFileFilepath('app/alpha.yml', TRUE);
    $charlie_path = $this->getTestFileFilepath('app/config/charlie.pdf', TRUE);
    $data = [
      'double_dot' => '../alpha.yml',
      'not_exists' => '../bravo.yml',
      'single_dot' => './charlie.pdf',
    ];
    $new_data = (new ResolveRecursive())($data, $base_path);
    $this->assertSame($alpha_path, $new_data['double_dot'], 'The new data should be resolved.');
    $this->assertSame('../bravo.yml', $new_data['not_exists'], 'The new data should not be resolved because it does not exist.');;
    $this->assertSame($charlie_path, $new_data['single_dot'], 'The new data should be resolved.');

    $this->assertSame('../alpha.yml', $data['double_dot'], 'The original data should not be modified.');
    $this->assertSame('../bravo.yml', $data['not_exists']);
    $this->assertSame('./charlie.pdf', $data['single_dot']);
    $this->deleteTestFile('app/');
  }
}
