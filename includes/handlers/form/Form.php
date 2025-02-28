<?php

namespace AKlump\CheckPages\Handlers;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\SuiteEventInterface;
use AKlump\CheckPages\Exceptions\TestFailedException;
use AKlump\CheckPages\Handlers\Form\HtmlFormReader;
use AKlump\CheckPages\Handlers\Form\FormValuesManager;
use Exception;

/**
 * Implements the Form handler.
 */
final class Form implements HandlerInterface {

  const DEFAULT_SUBMIT_SELECTOR = '[type="submit"]';

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Event::SUITE_STARTED => [

        /**
         * Look for "form" implementations and modify the suite.
         *
         * For all tests that use the form plugin, we will inject a test just
         * after, that will use the request plugin to submit to the form.
         */
        function (SuiteEventInterface $event) {
          foreach ($event->getSuite()->getTests() as $test) {
            $config = $test->getConfig();
            if ($config['form'] ?? NULL) {
              if (empty($config['url'])) {
                throw new TestFailedException($config, new Exception('Test is missing an URL'));
              }

              $test_configs = [];

              // It's important to copy the original, for reasons such as the
              // original may have extra configuration we can't know about, for
              // example the user plugin and authentication.
              $second_config = $test->getConfig();

              // The find will be pushed to the secondary test.
              unset($config['find']);
              $config['why'] = sprintf('Load and analyze form (%s)', $config['form']['dom']);
              $test_configs[] = $config;

              // The form should not appear in this test, only the first.
              unset($second_config['form']);
              $second_config['url'] = '${formAction}';
              $second_config['request'] = [
                'method' => '${formMethod}',
                'headers' => [
                  'content-type' => 'application/x-www-form-urlencoded',
                ],
                'body' => '${formBody}',
              ];
              $test_configs[] = $second_config;

              $event->getSuite()->replaceTestWithMultiple($test, $test_configs);
            }
          }
        },
      ],
      Event::REQUEST_FINISHED => [

        /**
         * Analyze the form and import action, method, values, etc.
         */
        function (Event\DriverEventInterface $event) {
          $test = $event->getTest();
          $config = $test->getConfig();
          if (empty($config['form'])) {
            return;
          }

          if (isset($config['form']['input']) && is_array($config['form']['input'])) {
            $importer = new Importer($test->getRunner()->getFiles());
            $importer->resolveImports($config['form']['input']);
          }

          if (isset($config['form']['input']) && is_array($config['form']['input'])) {
            $test->interpolate($config['form']['input']);
          }

          $variables = $test->getSuite()->variables();
          $body = strval($event->getDriver()->getResponse()->getBody());
          try {
            $reader = new HtmlFormReader($body, $config['form']['dom']);

            $action = $reader->getAction();
            // The form action may or may not be the same URL.
            $action = $action ?: $config['url'];
            $variables->setItem('formAction', $action);

            $method = $reader->getMethod();
            $variables->setItem('formMethod', $method ?: 'post');

            $form_values = $reader->getValues();

            // Add the correct submit button to the request.
            $submit = $reader->getSubmit($config['form']['submit'] ?? self::DEFAULT_SUBMIT_SELECTOR);
            $form_values[$submit->getKey()] = $submit->getLabel();

            // Handle the merging of form vars and config vars.
            $form_values_manager = new FormValuesManager();
            $form_values_manager->setConfig($config);
            $form_values_manager->setFormValues($form_values);

            // Set the request body with form values.
            $http_query = $form_values_manager->getHttpQueryString();
            $variables->setItem('formBody', $http_query);
          }
          catch (Exception $e) {
            throw new TestFailedException($config, $e);
          }
        },
      ],
    ];
  }

  public static function getId(): string {
    return 'form';
  }

}

