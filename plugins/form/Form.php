<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\SuiteEventInterface;

/**
 * Implements the Form plugin.
 */
final class Form implements \Symfony\Component\EventDispatcher\EventSubscriberInterface {

  /**
   * @var \AKlump\CheckPages\Parts\Runner
   */
  protected $runner;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Event::SUITE_LOADED => [
        function (SuiteEventInterface $event) {
          $should_apply = boolval(array_filter($event->getSuite()
            ->getTests(), function ($test) {
            return boolval($test->getConfig()['form'] ?? FALSE);
          }));

          if ($should_apply) {
            $obj = new self();

            return $obj->implementForm($event);
          }
        },
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function implementForm(SuiteEventInterface $event) {
    foreach ($event->getSuite()->getTests() as $test) {
      $form = $test->getConfig()['form'] ?? NULL;
      if (NULL === $form) {
        // This test does not implement the "form" plugin.
        continue;
      }

      $test_configs = [];

      //
      // The form discovery.
      //
      $new_test = $test->getConfig();
      unset($new_test['form']);
      $new_test['why'] = sprintf('Load and analyze the form (%s).', $form['dom']);
      $new_test['find'] = [];

      $new_test['find'][] = [
        'why' => 'Capture the form method.',
        'dom' => $form['dom'],
        'attribute' => 'method',
        'set' => 'formMethod',
      ];

      $new_test['find'][] = [
        'why' => 'Capture the form action.',
        'dom' => $form['dom'],
        'attribute' => 'action',
        'set' => 'formAction',
      ];

      if (empty($form['submit'])) {
        $form['submit'] = sprintf('%s [type="submit"]', trim($form['dom']));
      }

      if (!empty($form['submit'])) {
        $new_test['find'][] = [
          'why' => 'Capture the form submit element name.',
          'dom' => $form['submit'],
          'attribute' => 'name',
          'set' => 'formSubmitName',
        ];
        $new_test['find'][] = [
          'why' => 'Capture the form submit element value.',
          'dom' => $form['submit'],
          'attribute' => 'value',
          'set' => 'formSubmitValue',
        ];
      }

      foreach (($form['input'] ?? []) as $input) {
        if (is_string($input)) {
          $new_test['find'][] = [
            'why' => sprintf('Get %s', $input),
            'dom' => sprintf('%s [name="%s"]', trim($form['dom']), trim($input)),
            'attribute' => 'value',
            'matches' => '/.+/',
            'set' => $input,
          ];
        }
      }

      $test_configs[] = $new_test;


      //
      // The form submission.
      //
      $new_test = $test->getConfig();
      unset($new_test['find']);
      $new_test['url'] = '${formAction}';
      $new_test['request'] = [
        'method' => '${formMethod}',
        'headers' => [
          'content-type' => 'application/x-www-form-urlencoded',
        ],
        'body' => [
          '${formSubmitName}' => '${formSubmitValue}',
        ],
      ];
      foreach (($form['input'] ?? []) as $input) {

        if (is_string($input)) {
          $input = [
            'name' => $input,
            'value' => sprintf('${%s}', $input),
          ];
        }
        $new_test['request']['body'][$input['name']] = $input['value'];
      }

      $new_test += $test->getConfig();
      unset($new_test['form']);

      $test_configs[] = $new_test;

      $test->getSuite()->replaceTestWithMultiple($test, $test_configs);
    }

  }
}
