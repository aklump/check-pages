<?php

// TODO This file needs to move into the handler directory.
// TODO Move ./user_login_form.html as well.
namespace AKlump\CheckPages\Tests\Unit\Handlers\Form;

use AKlump\CheckPages\Handlers\Form\FormValuesManager;
use AKlump\CheckPages\Handlers\Form\KeyLabelNode;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\Handlers\Form\FormValuesManager
 * @uses   \AKlump\CheckPages\Handlers\Form\KeyLabelNode
 */
class FormValuesManagerTest extends TestCase {


  public function testQueryStringWorksWhenFormValuesAreKeyValueObjects() {
    $form_values_manager = new FormValuesManager();
    $form_values_manager->setFormValues(array(
      'field_expense_entities[choice]' => new KeyLabelNode('0', 'No'),
    ));
    $http_query = $form_values_manager->getHttpQueryString();
    $this->assertSame('field_expense_entities%5Bchoice%5D=0', $http_query);
  }

  public function testConfigNotIndexedThrows() {
    $config = ['form' => ['input' => ['key' => 'value']]];
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('form.input must be an indexed array');
    $form_values_manager = new FormValuesManager();
    $form_values_manager->setConfig($config);
  }

  public function testBadConfigNameThrows() {
    $config = ['form' => ['input' => [['key' => 'value']]]];
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('.name is missing');
    $form_values_manager = new FormValuesManager();
    $form_values_manager->setConfig($config);
  }

  public function testFooThrows() {
    $config = [
      'form' => [
        'input' => [
          [
            'name' => 'fav_animal',
            'value' => 'spaghetti',
          ],
        ],
      ],
    ];
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Config value form.input[0][fav_animal]=spaghetti is not a valid form key or case-insensitive label matching "d|Dog"');
    $form_values_manager = new FormValuesManager();
    $form_values_manager->setFormValues([
      'fav_animal' => new KeyLabelNode('d', 'Dog'),
    ]);
    $form_values_manager->setConfig($config);
    $form_values_manager->getHttpQueryString();
  }

  public function testValueOfZeroDoesNotThrow() {
    $config = [
      'form' => [
        'input' => [
          [
            'name' => 'foo',
            'value' => 0,
          ],
        ],
      ],
    ];
    $form_values_manager = new FormValuesManager();
    $form_values_manager->setConfig($config);
    $this->assertSame('foo=0', $form_values_manager->getHttpQueryString());
  }

  public function testBadConfigValueThrows() {
    $config = ['form' => ['input' => [['name' => 'value']]]];
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('form.input[0].value is required');
    $form_values_manager = new FormValuesManager();
    $form_values_manager->setConfig($config);
  }

  public static function dataFortestGetHttpQueryStringProvider(): array {
    $tests = [];

    $tests[] = [
      [],
      ['field_expense_entities[choice]' => new KeyLabelNode('1', 'Yes')],
      'field_expense_entities%5Bchoice%5D=1',
      FormValuesManager::OPTION_ALLOW_NON_FORM_KEYS,
    ];
    $tests[] = [
      [],
      ['field_expense_entities[choice]' => new KeyLabelNode('0', 'No')],
      'field_expense_entities%5Bchoice%5D=0',
      FormValuesManager::OPTION_ALLOW_NON_FORM_KEYS,
    ];
    $tests[] = [
      [['name' => 'color', 'value' => 'w']],
      ['color' => new KeyLabelNode('w', 'White')],
      'color=w',
      FormValuesManager::OPTION_ALLOW_NON_FORM_KEYS,
    ];
    $tests[] = [
      [['name' => 'color', 'value' => 'White']],
      ['color' => new KeyLabelNode('w', 'White')],
      'color=w',
      FormValuesManager::OPTION_ALLOW_NON_FORM_KEYS,
    ];

    $tests[] = [
      [
        ['name' => 'bonus', 'value' => 'value'],
        ['name' => 'name', 'value' => 'admin'],
      ],
      ['name' => '', 'form_id' => 'user_login_form'],
      'name=admin&form_id=user_login_form&bonus=value',
      FormValuesManager::OPTION_ALLOW_NON_FORM_KEYS,
    ];
    // Non form key is allowed
    $tests[] = [
      [
        ['name' => 'bonus', 'value' => 'value'],
        ['name' => 'name', 'value' => 'admin'],
      ],
      ['name' => '', 'form_id' => 'user_login_form'],
      'name=admin&form_id=user_login_form&bonus=value',
      FormValuesManager::OPTION_ALLOW_NON_FORM_KEYS,
    ];

    // Config value overwrites empty form value.
    // Non form key is removed
    $tests[] = [
      [
        ['name' => 'name', 'value' => 'admin'],
        ['name' => 'bonus', 'value' => 'value'],
      ],
      ['name' => '', 'form_id' => 'user_login_form'],
      'name=admin&form_id=user_login_form',
      FormValuesManager::OPTION_BLOCK_NON_FORM_KEYS,
    ];

    // Config value overwrites non-empty form value.
    $tests[] = [
      [
        ['name' => 'name', 'value' => 'bravo'],
        ['name' => 'bonus', 'value' => 'value'],
      ],
      ['name' => 'alpha', 'form_id' => 'user_login_form'],
      'name=bravo&form_id=user_login_form',
      FormValuesManager::OPTION_BLOCK_NON_FORM_KEYS,
    ];
    $tests[] = [
      [],
      [],
      '',
      FormValuesManager::OPTION_ALLOW_NON_FORM_KEYS,
    ];

    return $tests;
  }

  /**
   * @dataProvider dataFortestGetHttpQueryStringProvider
   */
  public function testGetHttpQueryString(array $cp_test_form_config, array $form_values, string $expected, int $options) {
    $form_values_manager = new FormValuesManager($options);
    $config = ['form' => ['input' => $cp_test_form_config]];
    $form_values_manager->setConfig($config);
    $form_values_manager->setFormValues($form_values);
    $http_query = $form_values_manager->getHttpQueryString();
    $this->assertSame($expected, $http_query);
  }


}
