<?php

namespace AKlump\CheckPages\EventSubscriber;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\SuiteEvent;
use AKlump\CheckPages\Files\LoadSerializedArray;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Load secrets from a "secrets" file and add them to the suite variables.
 *
 * It looks for a file called "secrets" with a supported extension (.yml, .yaml,
 * .json) in the same directory as the loaded config file. If found, it will
 * add the values to the suite variables, allowing them to be used in test
 * files. This is used to keep sensitive configuration out of source control.
 */
final class SecretsService implements EventSubscriberInterface {

  public const FILENAME = 'secrets';

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents() {
    return [
      Event::SUITE_STARTED => [
        function (SuiteEvent $event) {
          $service = new self();
          $secrets = $service->loadSecretsByEvent($event);
          if (empty($secrets)) {
            return;
          }
          $variables = $event->getSuite()->variables();
          foreach ($secrets as $key => $value) {
            $variables->setItem($key, $value);
          }
        },
      ],
    ];
  }

  /**
   * Deserialze the secrets file from a directory.
   *
   * @param string $directory The directory to search for the secrets file.
   *
   * @return array
   *
   * @throws \InvalidArgumentException When there is more than one secrets file
   * in the provided directory.
   */
  public function getSecrets(string $directory): array {
    $filepath = $directory . '/' . self::FILENAME . '.*';
    $filepaths = glob($filepath);
    if (empty($filepaths)) {
      return [];
    }
    if (count($filepaths) > 1) {
      throw new \InvalidArgumentException("Multiple secrets files found in $directory; only one is allowed.");
    }
    $filepath = reset($filepaths);

    return (new LoadSerializedArray())($filepath);
  }

  private function loadSecretsByEvent(SuiteEvent $event): array {
    $runner = $event->getSuite()->getRunner();
    $directory = dirname($runner->getLoadedConfigPath());

    return self::getSecrets($directory);
  }

}
