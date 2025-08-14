<?php

namespace AKlump\CheckPages\Service;

class SystemResourcesManager {

  public function getOpenFilesPercentage(): float {
    $limit = $this->getSystemLimit();
    if ($limit === 0) {
      return 0.0; // Or throw an exception, handle the error as appropriate
    }
    $open_files_count = $this->getOpenFiles();

    return ($open_files_count / $limit) * 100;
  }

  public function getSystemLimit(): int {
    static $data;
    if (NULL === $data) {
      $data = shell_exec('ulimit -n');
      if ($data === NULL) {
        // Handle the error, potentially throw an exception or set a default value.
        $data = 0; // Or throw an exception, handle the error as appropriate.
      }
      else {
        $data = (int) trim($data);
      }
    }

    return $data;
  }

  private function getOpenFiles(): int {
    $pid = getmypid();
    if (DIRECTORY_SEPARATOR == '/') { // Linux optimization.
      $open_files_count = intval(shell_exec("ls -l /proc/$pid/fd 2>/dev/null | wc -l"));
      if ($open_files_count !== 0) { // If successful on Linux return here.
        return $open_files_count;
      }
    }

    return intval(shell_exec("lsof -p $pid | wc -l"));
  }

}
