<?php

namespace AKlump\CheckPages\Tests\Unit\Browser;

use AKlump\CheckPages\Browser\Session;
use AKlump\CheckPages\DataStructure\User;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\Browser\Session
 * @uses   \AKlump\CheckPages\DataStructure\User
 */
class SessionTest extends TestCase {

  public function testExpires() {
    $session = new Session();
    $session->setExpires(123);
    $this->assertSame(123, $session->getExpires());
  }

  public function testGetSetUser() {
    $user = new User('foo', '123pass');
    $session = new Session();
    $session->setUser($user);
    $this->assertSame($user, $session->getUser());
  }

  public function testEmptySessionCookie() {
    $session = new Session();
    $this->assertSame('', $session->getCookieHeader());
  }

  public function testGetCookieHeader() {
    $session = new Session();
    $session->setName('foo');
    $session->setValue('bar');
    $this->assertSame('foo=bar', $session->getCookieHeader());
  }

  public function testGetSetValue() {
    $session = new Session();
    $session->setValue('bar');
    $this->assertEquals('bar', $session->getValue());
  }

  public function testGetSetName() {
    $session = new Session();
    $session->setName('foo');
    $this->assertEquals('foo', $session->getName());
  }
}
