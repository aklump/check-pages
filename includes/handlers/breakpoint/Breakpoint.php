<?php

namespace AKlump\CheckPages\Handlers;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Output\Message;
use AKlump\Messaging\MessageType;

/**
 * Implements the Breakpoint handler.
 */
final class Breakpoint implements HandlerInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Event::TEST_CREATED => [
        function (TestEventInterface $event) {
          $test = $event->getTest();
          $debugging_enabled = $test->getRunner()
            ->getInput()
            ->getOption('break');
          if (!$debugging_enabled || !$test->has('breakpoint')) {
            return;
          }
          $test->setPassed();
          $description = $test->getDescription();
          if (!$description) {
            $description = 'Press any key';
          }
          $test->addMessage(new Message(
            [
              "🛑 $description",
            ],
            MessageType::TODO
          ));
          $test->echoMessages();

          // @link https://www.sitepoint.com/howd-they-do-it-phpsnake-detecting-keypresses/
          // @link https://stackoverflow.com/a/15322457/3177610
          system('stty cbreak -echo');
          $stdin = fopen('php://stdin', 'r');
          ord(fgetc($stdin));

          // This will give a little visual response to let the user know the
          // keypress was received.
          $test->addMessage(new Message([''], MessageType::TODO));
          $test->echoMessages();
        },
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getId(): string {
    return 'breakpoint';
  }

}
