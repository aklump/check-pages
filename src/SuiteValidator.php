<?php

namespace AKlump\CheckPages;

use AKlump\CheckPages\Event\SuiteEventInterface;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Exceptions\BadSyntaxException;
use AKlump\CheckPages\Exceptions\DeprecatedSyntaxException;
use Exception;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SuiteValidator implements EventSubscriberInterface {

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents() {
    return [
      Event::TEST_CREATED => [
        function (TestEventInterface $event) {
          $test = $event->getTest();
          $config = $test->getConfig();
          if (isset($config['find']) && empty($config['url'])) {
            throw new BadSyntaxException('You may only include "find" with HTTP tests; you are missing "url" or "visit" in your test configuration.', $test);
          }
        },
      ],
      Event::SUITE_CREATED => [
        function (SuiteEventInterface $event) {
          $suite = $event->getSuite();
          try {

            // Convert to objects for our validator expects that, and not arrays.
            $config = json_decode(json_encode($suite->getRunner()
              ->getConfig()));
            static::handleDeprecatedConfigSyntax($config);
            $validator = new Validator();
            $path_to_schema = $suite->getRunner()
                                ->getFiles()
                                ->tryResolveFile('schema.config.json')[0];
            $validator->validate($config, (object) [
              '$ref' => 'file://' . $path_to_schema,
            ], Constraint::CHECK_MODE_EXCEPTIONS);
          }
          catch (BadSyntaxException $exception) {
            throw $exception;
          }
          catch (Exception $exception) {
            throw new BadSyntaxException($exception->getMessage(), $suite);
          }
        },
      ],
    ];
  }

  private static function handleDeprecatedConfigSyntax($config) {
    if (isset($config->request_timeout)) {
      throw new DeprecatedSyntaxException('request_timeout', 'request.timeout');
    }
  }

}
