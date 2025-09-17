<?php

namespace AKlump\CheckPages\Command;

use AKlump\CheckPages\EventSubscriber\SecretsService;
use AKlump\CheckPages\Output\Icons;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends Command {

  const DEFAULT_DIR = 'tests_check_pages';

  protected int $hiccups;

  private string $dir;

  private string $scaffoldDir;

  protected static $defaultName = 'init';

  public function __construct(string $scaffold_dir) {
    parent::__construct();
    $this->scaffoldDir = $scaffold_dir;
  }

  protected function configure() {
    $this
      ->setDescription('Initialize the current directory with testing scaffold.')
      ->addOption('dir', NULL, InputOption::VALUE_REQUIRED, 'Name of the created tests directory. Defaults to ' . self::DEFAULT_DIR);
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    // TODO Convert the output to use \AKlump\CheckPages\Output\Message\Message?
    try {
      $this->dir = $input->getOption('dir') ?? self::DEFAULT_DIR;
      $this->tryCheckEnvironment();
      $this->tryCopyScaffold();
    }
    catch (\Exception $exception) {
      $output->writeln('<error>' . $exception->getMessage() . '</error>');

      return Command::FAILURE;
    }

    $this->hiccups = 0;
    $this->handleBinDir($output);
    $output->writeln('<info>' . sprintf(Icons::COMPLETE . 'Test directory "%s" creating with test scaffolding', $this->dir) . '</info>');
    if ($this->hiccups > 0) {
      $output->writeln('<info>' . sprintf(Icons::OOPS . 'However, things did not go exactly as planned; see above.', $this->hiccups) . '</info>');
      $output->writeln('');
    }

    return Command::SUCCESS;
  }

  private function tryCopyScaffold() {
    if (!mkdir($this->dir, 0755, TRUE)) {
      throw new \RuntimeException(sprintf('Could not create %s', $this->dir));
    }

    try {
      $command = sprintf('rsync -aq "%s/" "%s/"', $this->scaffoldDir, $this->dir);
      system($command, $result);
      if (0 !== $result) {
        throw new \RuntimeException();
      }

      $stream = fopen("$this->dir/.gitignore", 'a');
      $secrets = SecretsService::BASENAME;
      if (FALSE === fwrite($stream, "$secrets\nusers*\nlogfiles/\n")) {
        throw new \RuntimeException();
      }
      fclose($stream);
    }
    catch (\Exception $exception) {
      throw new \RuntimeException(sprintf('Failed to copy all files into %s', $this->dir));
    }
  }

  private function tryCheckEnvironment() {
    if (file_exists($this->dir)) {
      throw new \RuntimeException(sprintf('%s already exists.  Please choose a non-existent directory name.', $this->dir));
    }
  }

  private function handleBinDir(OutputInterface $output) {
    if (!file_exists("$this->dir/../bin/")) {
      return;
    }
    $source_binaries = glob("$this->dir/bin/*");
    foreach ($source_binaries as $source) {
      $basename = basename($source);
      $destination = "$this->dir/../bin/$basename";
      if (file_exists($destination)) {
        $message = sprintf('Failed to move "%s"', $source);
        $output->writeln(Icons::INCOMPLETE . '<error>' . $message . '</error>');
        $message = sprintf('"%s" already exists.', realpath($destination));
        $output->writeln(Icons::HINT . $message);
        $output->writeln('');
        ++$this->hiccups;
      }
      else {
        rename($source, $destination);
        $output->writeln(Icons::COMPLETE . '<info>' . sprintf('"%s" created.', realpath($destination)) . '</info>');
      }
    }

    $source_bin_dir = "$this->dir/bin";
    if (!rmdir($source_bin_dir)) {
      $message = sprintf('Failed to delete "%s"', $source_bin_dir);
      $output->writeln(Icons::INCOMPLETE . '<error>' . $message . '</error>');
    }
  }

}
