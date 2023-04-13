<?php

namespace AKlump\CheckPages\Service;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\RunnerEventInterface;
use AKlump\CheckPages\Variables;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Looks for a file called "secrets.yml" in the same directory as the loaded
 * config file, and if so, it will load using it's contents to interpolate
 * against the config file.  This is used to keep config secrets out of source
 * control.
 */
final class ConfigSecretsService implements EventSubscriberInterface {

  const BASENAME = 'secrets.yml';

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents() {
    return [
      Event::RUNNER_CREATED => [
        function (RunnerEventInterface $event) {
          $secrets_path = dirname($event->getRunner()
              ->getLoadedConfigPath()) . '/' . self::BASENAME;
          if (!file_exists($secrets_path)) {
            return;
          }
          $vars = new Variables();
          $values = Yaml::parseFile($secrets_path);
          foreach ($values as $key => $value) {
            $vars->setItem($key, $value);
          }
          $config = $event->getRunner()->getConfig();
          $vars->interpolate($config['extras']['query_auth']['devices']);
          $event->getRunner()->setConfig($config);
        },
      ],
    ];
  }

}
