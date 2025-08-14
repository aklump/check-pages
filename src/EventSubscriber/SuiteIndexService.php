<?php

namespace AKlump\CheckPages\EventSubscriber;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\RunnerEventInterface;
use AKlump\CheckPages\Files\FilesProviderInterface;
use AKlump\CheckPages\Parts\Runner;
use AKlump\CheckPages\Parts\Suite;
use AKlump\CheckPages\Traits\HasRunnerTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Yaml\Yaml;

final class SuiteIndexService implements EventSubscriberInterface {

  use HasRunnerTrait {
    HasRunnerTrait::setRunner as traitSetRunner;
  }

  private $index = [];

  private $filepath;

  public function setRunner(Runner $runner): self {
    $this->filepath = $runner->getLogFiles()
                        ->tryResolveFile('all_suites.yml', [], FilesProviderInterface::RESOLVE_NON_EXISTENT_PATHS)[0];

    return $this->traitSetRunner($runner);
  }

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents() {
    return [
      Event::RUNNER_STARTED => [
        function (RunnerEventInterface $event) {
          $suite = $event->getRunner()->getSuite();
          $service = new self();
          $service
            ->setRunner($suite->getRunner())
            ->read();
          $service
            ->add($suite)
            ->write();
        },
      ],
    ];
  }

  private function add(Suite $suite): SuiteIndexService {
    $this->index[] = (string) $suite;

    return $this;
  }

  private function read(): array {
    if (!file_exists($this->filepath)) {
      $this->index = [];
    }
    else {
      $this->index = Yaml::parseFile($this->filepath)['all_suites'] ?? [];
    }

    return $this->index;
  }

  private function write(): SuiteIndexService {
    $output = array_values(array_unique($this->index));
    sort($output);
    $output = ['all_suites' => $output];
    $output = Yaml::dump($output);
    file_put_contents($this->filepath, $output);

    return $this;
  }

}
