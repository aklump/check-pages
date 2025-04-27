<?php

namespace AKlump\CheckPages\Tests\Unit\Files;

use AKlump\CheckPages\Files\LoadUsers;
use AKlump\CheckPages\Helpers\FilterUsersByName;
use AKlump\CheckPages\Tests\Unit\TestWithFilesTrait;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\Files\LoadUsers
 * @uses   \AKlump\CheckPages\DataStructure\User
 */
class LoadUsersTest extends TestCase {

  use TestWithFilesTrait;

  public function testSkipDuplicateNameKeys() {
    $users_data = [];
    $users_data[] = [
      'name' => 'alpha',
      'username' => 'bravo',
      'user' => 'charlie',
    ];
    $users_file = $this->getTestFileFilepath('load_users_duplicate_name.json');
    file_put_contents($users_file, json_encode($users_data));
    $loaded = (new LoadUsers())($users_file);
    $this->assertCount(1, $loaded);
    $this->assertSame('bravo', $loaded[0]->getAccountName());
    $this->assertSame('alpha', $loaded[0]->getProperty('name'));
    $this->assertSame('charlie', $loaded[0]->getProperty('user'));
    $this->deleteTestFile($users_file);
  }

  public function testLoadUsersFileWithEmptyData() {
    $users_file = $this->getTestFileFilepath('load_users_empty.json');
    $this->assertSame([], (new LoadUsers())($users_file));
  }

  public static function dataFortestInvokeWithFilesProvider(): array {
    $tests = [];
    $tests[] = [
      'load_users.json',
    ];
    $tests[] = [
      'load_users.yml',
    ];
    $tests[] = [
      'load_users.yaml',
    ];

    return $tests;
  }

  /**
   * @dataProvider dataFortestInvokeWithFilesProvider
   */
  public function testInvokeWithFiles(string $users_file) {
    $users_file = $this->getTestFileFilepath($users_file);
    $loaded = (new LoadUsers())($users_file);
    $this->assertCount(4, $loaded);

    $this->assertSame('alpha', $loaded[0]->getAccountName());
    $this->assertSame('avocado', $loaded[0]->getPassword());
    $this->assertSame(50, $loaded[0]->getProperty('age'));

    $this->assertSame('bravo', $loaded[1]->getAccountName());
    $this->assertSame('banana', $loaded[1]->getPassword());
    $this->assertSame('Outback', $loaded[1]->getProperty('car'));

    $this->assertSame('charlie', $loaded[2]->getAccountName());
    $this->assertSame('cherry', $loaded[2]->getPassword());
    $this->assertSame('jogging', $loaded[2]->getProperty('hobby'));

    $this->assertSame('', $loaded[3]->getAccountName());
    $this->assertSame('', $loaded[3]->getPassword());
    $this->assertSame('ipsum', $loaded[3]->getProperty('lorem'));

  }

  public function testInvokeNonExistentFileThrowsException() {
    $bogus_file = $this->getTestFileFilepath('bogus.json');
    $this->assertFileDoesNotExist($bogus_file);
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('/bogus.json" does not exist.');
    $loaded = (new LoadUsers())($bogus_file);
    $this->assertSame([], $loaded);
  }

  public function testInvokeEmptyFilenameThrowsException() {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Users file "" does not exist.');
    $loaded = (new LoadUsers())('');
    $this->assertSame([], $loaded);
  }

}
