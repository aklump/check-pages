<?php

namespace AKlump\CheckPages\EventSubscriber;

use AKlump\CheckPages\Event;
use AKlump\CheckPages\Event\DriverEventInterface;
use AKlump\CheckPages\Event\HttpMessageEvent;
use AKlump\CheckPages\Messaging\MessageType;
use AKlump\CheckPages\Output\HttpMessageLogger;
use AKlump\CheckPages\Output\Icons;
use AKlump\CheckPages\Output\Message\Divider;
use AKlump\CheckPages\Output\Message\Message;
use AKlump\CheckPages\Output\Message\YamlMessage;
use AKlump\CheckPages\Output\Verbosity;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides request/response user feedback.
 */
final class RequestFeedback implements EventSubscriberInterface {

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents(): array {
    $instance = new self();

    return [
      Event::REQUEST_STARTED => [$instance, 'onRequestStarted'],
      Event::REQUEST_PREPARED => [$instance, 'onRequestPrepared'],
      Event::REQUEST_FINISHED => [$instance, 'onRequestFinished'],
      Event::RESPONSE_RECEIVED => [$instance, 'onResponseReceived'],
    ];
  }

  public function onRequestStarted(DriverEventInterface $event): void {
    $test = $event->getTest();
    if ($test->has('why')) {
      //      $test->addMessage(new Message([
      //        // This is top margin to create visual space between tests in
      //        // order to get it's own prefix, it needs to be it's own message.
      //        '',
      //      ], MessageType::INFO, Verbosity::VERBOSE));
      $test->addMessage(new Message([
        Icons::SPYGLASS . $test->get('why'),
      ], MessageType::INFO, Verbosity::VERBOSE));
    }
  }

  public function onRequestPrepared(DriverEventInterface $event): void {
    $test = $event->getTest();
    $message = $test->getConfig();
    if (empty($message['find'])) {
      unset($message['find']);
    }
    else {
      $message['find'] = '';
    }
    $test->addMessage(new YamlMessage($message, 0, function ($yaml) {
      // To make the output more readable, we need to remove the printed ''
      // since find is really an array, whose elements are yet to be printed,
      // and which will be printed further down.
      return str_replace("find: ''", 'find:', $yaml);
    }));


    $logger = new HttpMessageLogger($test->getRunner()->getInput(), $test);
    $logger($event->getDriver(), MessageType::INFO);
  }

  public function onRequestFinished(DriverEventInterface $event): void {
    $test = $event->getTest();
    $message_type = MessageType::INFO;
    if ($test->hasFailed()) {
      $message_type = MessageType::ERROR;
    }
    $logger = new HttpMessageLogger($test->getRunner()->getInput(), $test);
    $response = $event->getDriver()->getResponse();
    $logger($response, $message_type);
  }

  public function onResponseReceived(HttpMessageEvent $event): void {
    $body = $event->getBody();
    $test = $event->getTest();
    $test->addMessage(new Divider());
    //    $test->echoMessages();
  }

}
