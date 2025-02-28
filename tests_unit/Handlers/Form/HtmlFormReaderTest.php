<?php

// TODO This file needs to move into the handler directory.
// TODO Move ./user_login_form.html as well.
namespace AKlump\CheckPages\Tests\Unit\Handlers\Form;

use AKlump\CheckPages\Handlers\Form\HtmlFormReader;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

require_once '/Users/aaronklump/Code/Packages/cli/check-pages/app/includes/handlers/form/src/HtmlFormReader.php';
require_once '/Users/aaronklump/Code/Packages/cli/check-pages/app/includes/handlers/form/src/KeyLabelNode.php';

/**
 * @covers \AKlump\CheckPages\Handlers\Form\HtmlFormReader
 * @uses   \AKlump\CheckPages\Handlers\Form\KeyLabelNode
 */
class HtmlFormReaderTest extends TestCase {

  public function testDisabledInputBehavior() {
    $html = '<!DOCTYPE html><html lang="en"><head> <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Form Example</title></head><body><form action="/submit" method="POST"> <label for="alpha">Disabled Input:</label> <input type="text" id="alpha" name="alpha" value="Apple" disabled/> <br><br> <label for="bravo">Editable Input:</label> <input type="text" id="bravo" name="bravo" value="Banana" required/> <br><br> <button type="submit">Submit</button></form></body></html>';
    $form = new HtmlFormReader($html, 'form');
    $this->assertSame([
      'bravo' => 'Banana',
    ], $form->getValues());
    $form = new HtmlFormReader($html, 'form', HtmlFormReader::OPTION_INCLUDE_DISABLED);
    $this->assertSame([
      'alpha' => 'Apple',
      'bravo' => 'Banana',
    ], $form->getValues());
  }

  public function testCantFindSubmitThrows() {
    $html = '<body><form class="form-c" action="/thank_you.php" method="post">
  <input type="text" name="first_name" value=""/>
  <input type="date" name="date" value=""/>
  <select name="shirt_size">
    <option value="sm">small</option>
    <option value="lg">large</option>
  </select>
    </form></body>';
    $form = new HtmlFormReader($html, '.form-c');
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Cannot find submit button with selector: input[type=submit]');
    $form->getSubmit('input[type=submit]');
  }

  public function testGetSubmit() {
    $html = '<body><form class="form-c" action="/thank_you.php" method="post">
  <input type="text" name="first_name" value=""/>
  <input type="date" name="date" value=""/>
  <select name="shirt_size">
    <option value="sm">small</option>
    <option value="lg">large</option>
  </select>
  <input type="submit" name="save" value="Save"/>
  <input type="submit" name="done" value="Save & Done"/>
</form></body>';
    $form = new HtmlFormReader($html, '.form-c');
    $save = $form->getSubmit('input[type=submit][name="save"]');
    $this->assertSame('save|Save', (string) $save);
    $save_and_done = $form->getSubmit('input[type=submit][name="done"]');
    $this->assertSame('done|Save & Done', (string) $save_and_done);
  }

  public function testSelectsFirstOptionByDefault() {
    $html = '<body><form class="form-c" action="/thank_you.php" method="post">
  <input type="text" name="first_name" value=""/>
  <input type="date" name="date" value=""/>
  <select name="shirt_size">
    <option value="sm">small</option>
    <option value="lg">large</option>
  </select>
  <button type="submit">Submit</button>
</form></body>';
    $form = new HtmlFormReader($html, '.form-c');
    $values = $form->getValues();
    $this->assertSame('sm|small', (string) $values['shirt_size']);
  }

  public function testSelectsSelectedOptionWhenPresent() {
    $html = '<body><form class="form-c" action="/thank_you.php" method="post">
  <input type="text" name="first_name" value=""/>
  <input type="date" name="date" value=""/>
  <select name="shirt_size">
    <option value="sm">small</option>
    <option value="lg" selected>large</option>
  </select>
  <button type="submit">Submit</button>
</form></body>';
    $form = new HtmlFormReader($html, '.form-c');
    $values = $form->getValues();
    $this->assertSame('lg|large', (string) $values['shirt_size']);
  }

  public function testNoOptionsThrows() {
    $html = '<body><form class="form-c" action="/thank_you.php" method="post">
  <input type="text" name="first_name" value=""/>
  <input type="date" name="date" value=""/>
  <select name="shirt_size"></select>
  <button type="submit">Submit</button>
</form></body>';
    $form = new HtmlFormReader($html, '.form-c');
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Select element has no options');
    $values = $form->getValues();

    $this->assertSame('lg|large', (string) $values['shirt_size']);
  }

  public function testMissingFormThrows() {
    $form = new HtmlFormReader('<html></html>', '.user-login-form');
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Cannot find form selecting with: .user-login-form');
    $form->getMethod();
  }

  public function testGetValues() {
    $expected = [
      'input' => '',
      'form_build_id' => 'form-ACKtQJmV1dnxuwfZaN7ZEaQpo8l9ptOE8OFgRfHaW0Q',
      'form_id' => 'user_login_form',
    ];
    $this->assertSame($expected, $this->reader->getValues());
  }

  public function testGetMethod() {
    $this->assertSame('POST', $this->reader->getMethod());
  }

  public function testGetAction() {
    $this->assertSame('/user/login', $this->reader->getAction());
  }

  protected function setUp(): void {
    parent::setUp();
    $html = file_get_contents(__DIR__ . '/user_login_form.html');
    $this->reader = new HtmlFormReader($html, '.user-login-form');
  }

  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();
    // TODO This is a hack while form/src/* is not autoloading.
    new \AKlump\CheckPages\Handlers\Form();
  }


}
