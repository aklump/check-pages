<?php

namespace AKlump\CheckPages;

use AKlump\CheckPages\Event\SuiteEventInterface;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Exceptions\BadSyntaxException;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

    class SuiteValidator implements EventSubscriberInterface {

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents() {
    return [
      Event::TEST_VALIDATION => [
        function (TestEventInterface $event) {
          $test = $event->getTest();
          $config = $test->getConfig();
          if (isset($config['find']) && empty($config['url'])) {
            throw new BadSyntaxException('You may only include "find" with HTTP tests; you are missing "url" or "visit" in your test configuration.', $test);
          }
        },
      ],
      Event::SUITE_VALIDATION => [
        function (SuiteEventInterface $event) {
          $suite = $event->getSuite();
          try {

            // Convert to objects for our validator expects that, and not arrays.
            $config = json_decode(json_encode($suite->getConfig()));

            $validator = new Validator();
            //    try {
            $validator->validate($config, (object) [
              '$ref' => 'file://' . $suite->getRunner()
                  ->getRootDir() . '/' . 'schema.config.json',
            ], Constraint::CHECK_MODE_EXCEPTIONS);
            //    }
            //    catch (\Exception $exception) {
            //      // Add in the file context.
            //      $class = get_class($exception);
            //      throw new $class(sprintf('In configuration : %s', strtolower($exception->getMessage())), $exception->getCode(), $exception);
            //    }
          }
          catch (BadSyntaxException $exception) {
            throw $exception;
          }
          catch (\Exception $exception) {
            throw new BadSyntaxException($exception->getMessage(), $suite);
          }
        },
      ],
    ];
  }

}
