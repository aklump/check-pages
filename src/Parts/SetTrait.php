<?php

namespace AKlump\CheckPages\Parts;

use AKlump\CheckPages\Output\FeedbackInterface;
use AKlump\CheckPages\Variables;
use AKlump\LoftLib\Bash\Color;
use Symfony\Component\Console\Output\OutputInterface;

trait SetTrait {

  /**
   * Handles the setting of a key/value pair.
   *
   * @param \AKlump\CheckPages\Variables $vars
   * @param \AKlump\CheckPages\Output\FeedbackInterface $feedback
   * @param string $key
   * @param $value
   *
   * @return void
   */
  protected function setKeyValuePair(Variables $vars, FeedbackInterface $feedback, string $key, $value) {
    $vars->setItem($key, $value);
    $message = '├── ${%s} set to "%s"';
    if (!is_scalar($value)) {
      $message = '├── ${%s} set.';
    }
    $feedback->writeln(Color::wrap('green', sprintf($message, $key, $value)), OutputInterface::VERBOSITY_VERY_VERBOSE);
  }

}
