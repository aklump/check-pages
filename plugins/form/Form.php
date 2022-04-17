<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\SuiteEventInterface;
use AKlump\CheckPages\Exceptions\TestFailedException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Implements the Form plugin.
 */
final class Form implements EventSubscriberInterface {

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

        /**
         * Look for "form" implementations and modify the suite.
         *
         * For all tests that use the form plugin, we will inject a test just
         * after, that will use the request plugin to submit to the form.
         */
        function (SuiteEventInterface $event) {
          foreach ($event->getSuite()->getTests() as $test) {
            if ($test->getConfig()['form'] ?? NULL) {
              $test_configs = [];

              // It's important to copy the original, for reasons such as the
              // original may have extra configuration we can't know about, for
              // example the user plugin and authentication.
              $first = $test->getConfig();
              $second = $test->getConfig();

              // The find will be pushed to the secondary test.
              unset($first['find']);
              $first['why'] = sprintf('Load and analyze form (%s)', $first['form']['dom']);
              $test_configs[] = $first;

              // The form should not appear in this test, only the first.
              unset($second['form']);
              $second['url'] = '${formAction}';
              $second['request'] = [
                'method' => '${formMethod}',
                'headers' => [
                  'content-type' => 'application/x-www-form-urlencoded',
                ],
                'body' => '${formBody}',
              ];
              $test_configs[] = $second;

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

          $body = strval($event->getDriver()->getResponse()->getBody());
          $crawler = new Crawler($body);
          $form = $crawler->filter($config['form']['dom']);
          if (!$form->count()) {
            throw new TestFailedException($config, new \Exception(sprintf('Cannot find form using DOM selector: %s', $config['form']['dom'])));
          }
          $variables = $test->getSuite()->variables();

          // The form action may or may not be the same URL.
          $action = $form->getNode(0)->getAttribute('action');
          $action = $action ?: $config['url'];
          $variables->setItem('formAction', $action);

          // The form method.
          $method = $form->getNode(0)->getAttribute('method');
          $variables->setItem('formMethod', $method ?: 'post');

          // Load the inputs that come from the test config, first.
          $inputs = [];
          foreach (($config['form']['input'] ?? []) as $input) {
            $inputs[$input['name']] = $input['value'];
          }

          $add_node_input = function ($node) use (&$inputs) {
            if ($node) {
              $name = $node->getAttribute('name');
              if ($name) {
                $inputs += [
                  $name => $node->getAttribute('value') ?? '',
                ];
              }
            }
          };

          // Then add any non-existent values, pulling from the form inputs.
          $submit_selector = $config['form']['submit'] ?? '[type="submit"]';
          $submit = $form->filter($submit_selector)->getNode(0);
          $add_node_input($submit);

          foreach ($form->filter('input') as $input) {
            if ('submit' !== $input->getAttribute('type')) {
              $add_node_input($input);
            }
          }
          $variables->setItem('formBody', http_build_query($inputs));
        },
      ],
    ];
  }

}
