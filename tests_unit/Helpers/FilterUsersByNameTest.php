<?php

namespace AKlump\CheckPages\Tests\Unit\Helpers;

use AKlump\CheckPages\Helpers\FilterUsersByName;
use PHPUnit\Framework\TestCase;
use AKlump\CheckPages\DataStructure\User;

/**
 * @covers \AKlump\CheckPages\Helpers\FilterUsersByName
 * @uses   \AKlump\CheckPages\DataStructure\User
 */
class FilterUsersByNameTest extends TestCase {

  public function testInvoke() {
    $users = [];
    $users[] = new User('alpha', 'apple');
    $users[] = new User('bravo', 'banana');
    $user = (new FilterUsersByName($users))('alpha');
    $this->assertSame($user, $users[0]);


  }
}
