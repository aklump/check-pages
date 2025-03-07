<?php

namespace AKlump\CheckPages\Tests\Unit\DataStructure;

use AKlump\CheckPages\DataStructure\User;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\DataStructure\User
 */
class UserTest extends TestCase {

  public function testJsonSerialize() {
    $user = new User();
    $user->setAccountName('alpha');
    $user->setPassword('foobar');
    $user->setProperty('haircolor', 'blond');
    $user->setEmail('a@website.com');
    $user->setId(123);
    $this->assertSame([
      'uid' => 123,
      'name' => 'alpha',
      'pass' => 'foobar',
      'mail' => 'a@website.com',
      '_props' => ['haircolor' => 'blond'],
    ], $user->jsonSerialize());
  }

  public function testEmptyUser() {
    $user = new User();
    $this->assertSame('', $user->getAccountName());
    $this->assertSame('', $user->getPassword());
  }

  public function testConstructor() {
    $user = new User('bravo', 'bpass2');
    $this->assertSame('bravo', $user->getAccountName());
    $this->assertSame('bpass2', $user->getPassword());
  }

  public function testProperty() {
    $user = new User();
    $user->setProperty('haircolor', 'blond');
    $this->assertSame('blond', $user->getProperty('haircolor'));
  }

  public function testId() {
    $user = new User();
    $user->setId(123);
    $this->assertSame(123, $user->id());
  }

  public function testEmail() {
    $user = new User();
    $user->setEmail('a@website.com');
    $this->assertSame('a@website.com', $user->getEmail());
  }

  public function testAccountName() {
    $user = new User();
    $user->setAccountName('alpha');
    $this->assertSame('alpha', $user->getAccountName());
  }
}
