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
    if (DIRECTORY_SEPARATOR === '/' && is_dir("/proc/$pid/fd")) {
      $open_files_count = (int) shell_exec("ls -1 /proc/$pid/fd 2>/dev/null | wc -l");
      if ($open_files_count > 0) {
        return $open_files_count;
      }
    }

    return (int) shell_exec("lsof -w -p $pid 2>/dev/null | tail -n +2 | wc -l");
  }

}
