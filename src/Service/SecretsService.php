<?php

namespace AKlump\CheckPages\Service;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\RunnerEventInterface;
use AKlump\CheckPages\Event\SuiteEvent;
use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Variables;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Looks for a file called "secrets.yml" in the same directory as the loaded
 * config file, and if so, it will load using it's contents to interpolate
 * against the config file.  It will also add to the suites variables so you can
 * interpolate those values in your test files.  This is used to keep config
 * secrets out of source control.
 */
final class SecretsService implements EventSubscriberInterface {

  const BASENAME = 'secrets.yml';

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents() {
    return [
      Event::SUITE_STARTED => [
        function (SuiteEvent $event) {
          $secrets = self::getSecrets($event->getSuite()->getRunner());
          if (empty($secrets)) {
            return;
          }
          $suite = $event->getSuite();
          foreach ($secrets as $key => $value) {
            $suite->variables()->setItem($key, $value);
          }
        },
      ],
      Event::RUNNER_CREATED => [
        function (RunnerEventInterface $event) {
          $secrets = self::getSecrets($event->getRunner());
          if (empty($secrets)) {
            return;
          }
          $vars = new Variables();
          foreach ($secrets as $key => $value) {
            $vars->setItem($key, $value);
          }
          $config = $event->getRunner()->getConfig();
          // TODO I think this should be just $config.
          $vars->interpolate($config['extras']['query_auth']['devices']);
          $event->getRunner()->setConfig($config);
        },
      ],
    ];
  }

  private static function getSecrets(Runner $runner): array {
    $secrets_path = dirname($runner->getLoadedConfigPath()) . '/' . self::BASENAME;
    if (!file_exists($secrets_path) || !file_get_contents($secrets_path)) {
      return [];
    }
    $secrets = Yaml::parseFile($secrets_path);
    if (!is_array($secrets)) {
      throw new \RuntimeException(sprintf('%s must return an array.', self::BASENAME));
    }

    return $secrets;
  }

}
