<?php

// TODO This file needs to move into the handler directory.
// TODO Move ./user_login_form.html as well.
namespace AKlump\CheckPages\Tests\Unit\Handlers\Form;

use AKlump\CheckPages\Handlers\Form\HtmlFormReader;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

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

  public function testGetSubmitAsButton() {
    $html = '<div><form class="user-login-form" data-drupal-selector="user-login-form" action="/user/login" method="post" id="user-login-form" accept-charset="UTF-8" novalidate="novalidate"><div class="center-align">uber</div><input data-drupal-selector="edit-name" aria-describedby="edit-name--description" disabled="disabled" data-msg-required="Username field is required." type="hidden" name="name" value="uber"><div class="js-form-item form-item js-form-type-password form-item-pass js-form-item-pass input-field"> <input data-drupal-selector="edit-pass" aria-describedby="edit-pass--description" data-msg-maxlength="Password field has a maximum length of 128." data-msg-required="Password field is required." type="password" id="edit-pass" name="pass" size="60" maxlength="128" class="form-text required" required="required" aria-required="true"><label for="edit-pass" class="js-form-required form-required">Password</label><div id="edit-pass--description" class="description"><a class="tooltipped processed" data-position="bottom" data-delay="50" data-html="true" data-tooltip="Enter the password that accompanies your username." data-tooltip-id="a90d42b8-50ec-b0dd-0709-d27b5008b02a"><i class="material-icons" aria-hidden="true">help_outline</i> Info</a></div></div> <input autocomplete="off" data-drupal-selector="form-udobcdnztsg8hcceru3bzken3geosa66qotnpohqqmq" type="hidden" name="form_build_id" value="form-UDobCDNztSg8hcCEru3bZKen3GEoSA66QoTnpOhQQmQ"> <input data-drupal-selector="edit-user-login-form" type="hidden" name="form_id" value="user_login_form"><div data-drupal-selector="edit-actions" class="form-actions js-form-wrapper form-wrapper" id="edit-actions"><button data-drupal-selector="edit-submit" type="submit" id="edit-submit" name="op" value="Log in" class="button js-form-submit form-submit btn waves-effect waves-light" disabled="disabled"> Log in <div class="preloader-wrapper small"><div class="spinner-layer spinner-green-only"><div class="circle-clipper left"><div class="circle"></div></div><div class="gap-patch"><div class="circle"></div></div><div class="circle-clipper right"><div class="circle"></div></div></div></div></button></div><a href="/user/password" class="flex sub" data-drupal-selector="edit-reset-password" id="edit-reset-password">Reset password</a></form></div>';
    $form = new HtmlFormReader($html, '.user-login-form');
    $button = $form->getSubmit();
    $this->assertSame('op|Log in', (string) $button);
  }

  public function testGetSubmitAsInput() {
    $html = '<body><form class="form-c" action="/thank_you.php" method="post"> <input type="text" name="first_name" value=""/> <input type="date" name="date" value=""/><select name="shirt_size"><option value="sm">small</option><option value="lg">large</option></select> <input type="submit" name="save" value="Save"/> <input type="submit" name="done" value="Save & Done"/> </form></body>';
    $form = new HtmlFormReader($html, '.form-c');
    $save = $form->getSubmit();
    $this->assertSame('save|Save', (string) $save, 'Assert getSubmit() returns the first submit button');
    $save_and_done = $form->getSubmit('input[type=submit][name="done"]');
    $this->assertSame('done|Save & Done', (string) $save_and_done, 'Assert getSubmit() returns the explicitly selected submit button');
  }

  public function testSelectsFirstOptionByDefault() {
    $html = '<body><form class="form-c" action="/thank_you.php" method="post"> <input type="text" name="first_name" value=""/> <input type="date" name="date" value=""/><select name="shirt_size"><option value="sm">small</option><option value="lg">large</option></select><button type="submit">Submit</button></form></body>';
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
