<?php

namespace AKlump\CheckPages\Plugin;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\TestEventInterface;
use AKlump\CheckPages\Output\Message;
use AKlump\Messaging\MessageType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Implements the Breakpoint plugin.
 */
final class Breakpoint implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Event::TEST_CREATED => [
        function (TestEventInterface $event) {
          $test = $event->getTest();
          $config = $test->getConfig();
          $output = $test->getRunner()->getOutput();
          $is_breakpoint = array_key_exists('breakpoint', $config);
          if ($is_breakpoint) {
            $test->setPassed();
          }
          $should_apply = $is_breakpoint && $output->isDebug();
          if (!$should_apply) {
            return;
          }

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


}
