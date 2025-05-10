<?php

// TODO This file needs to move into the handler directory.
// TODO Move ./user_login_form.html as well.
namespace AKlump\CheckPages\Tests\Unit\Handlers\Form;

use AKlump\CheckPages\Handlers\Form\HtmlFormReader;
use AKlump\CheckPages\Handlers\Form\KeyLabelNode;
use AKlump\CheckPages\Tests\TestingTraits\TestWithFilesTrait;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers \AKlump\CheckPages\Handlers\Form\HtmlFormReader
 * @uses   \AKlump\CheckPages\Handlers\Form\KeyLabelNode
 */
class HtmlFormReaderTest extends TestCase {

  use TestWithFilesTrait;

  private $reader;


  public function testCheckBoxesAreReadAsFalseValues() {
    $html = '<form><div id="edit-roles" class="form-checkboxes"><div class="js-form-item form-item js-form-type-checkbox form-item-roles-authenticated js-form-item-roles-authenticated input-field"> <input data-drupal-selector="edit-roles-authenticated" type="checkbox" id="edit-roles-authenticated" name="roles[authenticated]" value="authenticated" class="form-checkbox"><label for="edit-roles-authenticated" class="option">Authenticated user</label></div><div class="js-form-item form-item js-form-type-checkbox form-item-roles-group-administrator js-form-item-roles-group-administrator input-field"> <input data-drupal-selector="edit-roles-group-administrator" type="checkbox" id="edit-roles-group-administrator" name="roles[group_administrator]" value="group_administrator" class="form-checkbox" checked="checked"><label for="edit-roles-group-administrator" class="option">Group administrator</label></div></div></form>';
    $form = new HtmlFormReader($html, 'form');
    $values = $form->getValues();
    $this->assertCount(2, $values);
    $this->assertSame('|Authenticated user', (string) $values['roles[authenticated]']);
    $this->assertSame('group_administrator|Group administrator', (string) $values['roles[group_administrator]']);
  }

  public function testCheckedAndUncheckedCheckboxesAreReadCorrectly() {
    $html = '<form><div class="js-form-item form-item js-form-type-checkbox form-item-field-additional-job-types-2 js-form-item-field-additional-job-types-2"><span class="input__container"> <input data-drupal-selector="edit-field-additional-job-types-2" type="checkbox" id="edit-field-additional-job-types-2" name="field_additional_job_types[2]" value="2" class="form-checkbox form-boolean form-boolean--type-checkbox" checked><label for="edit-field-additional-job-types-2" class="form-item__label option">Coatings - Plaza Membrane</label></span></div><div class="js-form-item form-item js-form-type-checkbox form-item-field-additional-job-types-3 js-form-item-field-additional-job-types-3"><span class="input__container"> <input data-drupal-selector="edit-field-additional-job-types-3" type="checkbox" id="edit-field-additional-job-types-3" name="field_additional_job_types[3]" value="3" class="form-checkbox form-boolean form-boolean--type-checkbox"><label for="edit-field-additional-job-types-3" class="form-item__label option">Coatings - Plaza Membrane</label></span></div></form>';
    $form = new HtmlFormReader($html, 'form');
    $values = $form->getValues();
    $this->assertCount(2, $values);
    $this->assertSame('2|Coatings - Plaza Membrane', (string) $values['field_additional_job_types[2]']);
    $this->assertSame('|Coatings - Plaza Membrane', (string) $values['field_additional_job_types[3]']);
  }

  public function testInputWithBracketedNameWorks() {
    $html = '<body><form action=""> <input type="text" name="foo[bar]" value="lorem"/> <input type="text" id="edit-field-expense-entities-choice-0" name="field_expense_entities[choice]" value="0"/> </form></body>';
    $form = new HtmlFormReader($html, 'form');
    $values = $form->getValues();
    $this->assertCount(2, $values);
    $this->assertSame('lorem', (string) $values['foo[bar]']);
    $this->assertSame('0', (string) $values['field_expense_entities[choice]']);
  }

  public function testInputWithoutNameIsIgnored() {
    $html = '<body><form class="form-c" action="/thank_you.php" method="post"><input class="chosen-search-input default" type="text" autocomplete="off" value="Choose some options" style="width: 151.211px;"></form></body>';
    $form = new HtmlFormReader($html, '.form-c');
    $values = $form->getValues();
    $this->assertCount(0, $values);
  }

  public function testSelectElementWithNoOptionChildrenIsIgnored() {
    $html = '<div class="block"><form action=""><select data-value="0" data-workreport-target="mileage" data-action="change-&gt;workReport#onMileageChange" data-drupal-selector="edit-field-foreman-period-0-subform-field-mileage-0-value" id="edit-field-foreman-period-0-subform-field-mileage-0-value" name="field_foreman_period[0][subform][field_mileage][0][value]" class="form-select form-element form-element--type-select"></select><select data-value="0" data-workreport-target="mileage" data-action="change-&gt;workReport#onMileageChange" data-drupal-selector="edit-field-worker-periods-0-subform-field-mileage-0-value" id="edit-field-worker-periods-0-subform-field-mileage-0-value" name="field_worker_periods[0][subform][field_mileage][0][value]" class="form-select form-element form-element--type-select"></select></form></div>';
    $form = new HtmlFormReader($html, 'form');
    $values = $form->getValues();
    $this->assertCount(0, $values);
  }

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

  public function testMultipleSubmitButtonsThrows() {
    $html = '<body><form class="form-c" action="/thank_you.php" method="post">
  <input type="text" name="first_name" value=""/>
  <input type="date" name="date" value=""/>
  <select name="shirt_size">
    <option value="sm">small</option>
    <option value="lg">large</option>
  </select>
  <input type="submit" value="Save" name="save"/>
  <input type="submit" value="Save and Edit" name="edit"/>
    </form></body>';
    $form = new HtmlFormReader($html, '.form-c');
    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('Multiple submit elements in .form-c, you must use form.submit in this test.');
    $form->getSubmit();
  }

  public static function dataFortestGetAllowedValuesProvider(): array {
    $tests = [];
    $tests[] = [
      '<body><form class="form-c" action="/thank_you.php" method="post">
  <input type="text" name="first_name" value=""/>
  <input type="date" name="date" value=""/>
  <select name="shirt_size">
    <option value="sm" disabled="disabled">Small</option>
    <option value="lg">Large</option>
  </select>
  <select name="hair_color">
    <option value="b">Blonde</option>
    <option value="g">Grey</option>
  </select>
  <select disabled name="state">
    <option value="WA">WA</option>
    <option value="FL">FL</option>
  </select>
  <div><input type="checkbox" id="edit-roles-authenticated" name="roles[authenticated]" value="authenticated" disabled="disabled"><label for="edit-roles-authenticated">Authenticated user</label></div>
  <div><input type="checkbox" id="edit-roles-group-administrator" name="roles[group_administrator]" value="group_administrator"><label for="edit-roles-group-administrator">Group administrator</label></div>
    </form></body>',
    ];

    return $tests;
  }

  /**
   * @dataProvider dataFortestGetAllowedValuesProvider
   */
  public function testGetAllowedValuesNotDisabled(string $html) {
    $form = new HtmlFormReader($html, '.form-c');
    $expected_allowed_values = [
      'shirt_size' => [
        new KeyLabelNode('lg', 'Large'),
      ],
      'hair_color' => [
        new KeyLabelNode('b', 'Blonde'),
        new KeyLabelNode('g', 'Grey'),
      ],
      'roles[group_administrator]' => [
        new KeyLabelNode('group_administrator', 'Group administrator'),
      ],
    ];
    $this->assertEquals($expected_allowed_values, $form->getAllowedValues());
  }

  /**
   * @dataProvider dataFortestGetAllowedValuesProvider
   */
  public function testGetAllowedValuesWithDisabled(string $html) {
    // Now do it with disabled.
    $form = new HtmlFormReader($html, '.form-c', HtmlFormReader::OPTION_INCLUDE_DISABLED);
    $expected_allowed_values = [
      'shirt_size' => [
        new KeyLabelNode('sm', 'Small'),
        new KeyLabelNode('lg', 'Large'),
      ],
      'hair_color' => [
        new KeyLabelNode('b', 'Blonde'),
        new KeyLabelNode('g', 'Grey'),
      ],
      'state' => [
        new KeyLabelNode('WA', 'WA'),
        new KeyLabelNode('FL', 'FL'),
      ],
      'roles[authenticated]' => [
        new KeyLabelNode('authenticated', 'Authenticated user'),
      ],
      'roles[group_administrator]' => [
        new KeyLabelNode('group_administrator', 'Group administrator'),
      ],
    ];
    $this->assertEquals($expected_allowed_values, $form->getAllowedValues());
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
    $save = $form->getSubmit('[name="save"]');
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

  public function testDrupalNestedFieldNone() {
    $html = '<form action="" class="form-c"><span class="input__container"><select data-value="0" data-workreport-target="mileage" data-action="change->workReport#onMileageChange" data-drupal-selector="edit-field-foreman-period-0-subform-field-mileage-0-value" id="edit-field-foreman-period-0-subform-field-mileage-0-value" name="field_foreman_period[0][subform][field_mileage][0][value]" class="form-select form-element form-element--type-select"><option value="_none">- None -</option></select></span></form>';
    $form = new HtmlFormReader($html, '.form-c');
    $values = $form->getValues();
    $this->assertSame('_none|- None -', (string) $values['field_foreman_period[0][subform][field_mileage][0][value]']);
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

  public function testTwoRadiosBothUncheckedReturnNoValuesForGetValues() {
    $html = '<form><fieldset id="edit-field-expense-entities-choice--wrapper" required="required" aria-required="true" aria-invalid="true"><legend><span>Do you have any expenses to report?</span></legend><div><div id="edit-field-expense-entities-choice"><div><span> <input type="radio" id="edit-field-expense-entities-choice-0" name="field_expense_entities[choice]" value="0" aria-invalid="true"><label for="edit-field-expense-entities-choice-0">No</label></span></div><div><span> <input type="radio" id="edit-field-expense-entities-choice-1" name="field_expense_entities[choice]" value="1" aria-invalid="true"><label for="edit-field-expense-entities-choice-1">Yes</label></span></div></div></div></fieldset></form>';
    $form = new HtmlFormReader($html, 'form');

    $values = $form->getValues();
    $this->assertCount(0, $values);

    $expected_allowed_values = [
      'field_expense_entities[choice]' => [
        new KeyLabelNode('0', 'No'),
        new KeyLabelNode('1', 'Yes'),
      ],
    ];
    $this->assertEquals($expected_allowed_values, $form->getAllowedValues());
  }

  public function testRadiosInputElementWorksAsExpected() {
    $html = '<body><form class="radios-test-form"><input id="segment-am" type="radio" name="segment" value="am"><label for="segment-am">Morning</label> <input id="segment-pm" type="radio" name="segment" value="pm" checked><label for="segment-pm">Evening</label></form></body>';
    $form = new HtmlFormReader($html, '.radios-test-form');

    $values = $form->getValues();
    $this->assertSame('pm|Evening', (string) $values['segment']);

    $expected_allowed_values = [
      'segment' => [
        new KeyLabelNode('am', 'Morning'),
        new KeyLabelNode('pm', 'Evening'),
      ],
    ];
    $this->assertEquals($expected_allowed_values, $form->getAllowedValues());
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

}
