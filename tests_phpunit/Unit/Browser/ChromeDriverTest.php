<?php

namespace AKlump\CheckPages\Tests\Unit\Browser;

use AKlump\CheckPages\Browser\ChromeDriver;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\Browser\ChromeDriver
 */
class ChromeDriverTest extends TestCase {

  public function testChromeDriverReturnsCorrectRedirectAndStatusCodes() {
    $driver = new ChromeDriver();
    $driver->setUrl('http://localhost:8000/moved.php');
    $response = $driver->request();

    $redirect_code = $response->getRedirectCode();
    $this->assertSame(301, $redirect_code);

    $final_status = $response->getResponse()->getStatusCode();
    $this->assertSame(200, $final_status);
  }
}
