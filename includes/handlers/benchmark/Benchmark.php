<?php

namespace AKlump\CheckPages\Handlers;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\DriverEventInterface;
use AKlump\CheckPages\Output\Message;
use AKlump\CheckPages\Output\Verbosity;
use AKlump\Messaging\MessageType;

class Benchmark implements HandlerInterface {

  public static function getId(): string {
    return 'benchmark';
  }

  public static function getSubscribedEvents() {
    return [
      Event::REQUEST_STARTED => [
        function (DriverEventInterface $event) {
          $start = microtime(TRUE);
          $config = $event->getTest()->getConfig();
          $config['extra'] = ['benchmark' => $start];
          $event->getTest()->setConfig($config);
        },
      ],
      Event::REQUEST_FINISHED => [
        function (DriverEventInterface $event) {
          $test = $event->getTest();
          $config = $test->getConfig();
          $start = $config['extra']['benchmark'] ?? NULL;
          $stop = microtime(TRUE);
          $elapsed_seconds = $stop - $start;
          if ($elapsed_seconds < 1) {
            $formated_time = sprintf('%.3f', $elapsed_seconds * 1000) . ' ms';
          }
          else {
            $formated_time = sprintf('%.3f', $elapsed_seconds) . ' s';
          }

          $default_ask = 'Benchmark: %s seconds';
          $ask = $config["benchmark"]["ask"] ?? $default_ask;
          $test->getSuite()->interpolate($ask);

          $test->addMessage(new Message([
            NULL,
            '🤔 ' . $ask,
            '👉 ' . $formated_time,
            NULL,
          ], MessageType::INFO, Verbosity::NORMAL));
        },
      ],
    ];
  }


}
