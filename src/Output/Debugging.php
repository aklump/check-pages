<?php

namespace AKlump\CheckPages\Output;

use AKlump\LoftLib\Bash\Color;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class Debugging {

  const COLOR = 'light gray';

  public function __construct(OutputInterface $output) {
    $this->output = $output;
    $this->enabled = $output->isDebug();
  }

  public function echoYaml(array $data, $level = 0, callable $callback = null) {
    if ($this->enabled) {
      for ($i = 0; $i <= $level; ++$i) {
        $data = [$data];
      }
      $yaml = explode(PHP_EOL, Yaml::dump($data, 4, 2));
      for ($i = 0; $i < $level; ++$i) {
        array_shift($yaml);
      }
      $yaml = implode(PHP_EOL, $yaml);
      if(is_callable($callback)) {
        $yaml = $callback($yaml);
      }

      echo Color::wrap(self::COLOR, $yaml);
    }
  }

  public function lineBreak() {
    if ($this->enabled) {
      echo PHP_EOL;
    }
  }
}
