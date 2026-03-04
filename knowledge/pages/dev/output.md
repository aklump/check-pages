<!--
id: dev__output
tags: ''
-->

# Developers Note: User Messages

When working on this project, do no call PHP's `echo` directly. **You should not instantiate a printer instance (`ConsoleEchoPrinter, DevNullPrinter, LoggerPrinter, MultiPrinter`) unless absolutely necessary!**

Follow the code as shown below for user feedback. Favor the options at the top.

The instance of `\AKlump\Messaging\MessengerInterface` will determine how the messages are formatted. As you write code you should simply focus on the message text, severity, and verbosity.

## Writing a User Message

Given a message instance...
```php
$message = new \AKlump\CheckPages\Output\Message\Message(['Foo was found'], \AKlump\Messaging\MessageType::INFO);
```

### When `$test` is available

```php
// Queue your message to be printed with a possible delay.
$test->addMessage($message, Verbosity::VERBOSE));
```

Note that the message will not be printed immediately (by default), rather it will be queued up to be printed by the runner instance.  **If you need to see the output appear immediately then also call `$test->echoMessages()`.**  This will print any previously queued messages, followed by your message. Don't use this command unless necessary as it can be slightly less efficient.

```php
// Print your message immediately.
$test->addMessage($message, Verbosity::VERBOSE);
$test->echoMessages();
```

### When `$test` is not available

```php
$runner->echo($message)
```

### When `$runner` is not available

```php
$printer->deliver($message);
```

```php
// INFO MESSAGE
$printer->deliver(new Message(
  [
    'This is a two-line",
    "info message.',
  ],
));

// NOTICE MESSAGE
$printer->deliver(new Message(
  [
    'This is an notice.',
    '',
  ],
  \AKlump\Messaging\MessageType::SUCCESS,
));

// ERROR MESSAGE
$printer->deliver(new Message(
  [
    'This is an error message with an extra line break.',
    '',
  ],
  \AKlump\Messaging\MessageType::ERROR,
));


// VERBOSE, INFO
$printer->deliver(new Message(
  [
    'This is a debug message that will show with (A)ny verbosity.',
  ],
  \AKlump\Messaging\MessageType::DEBUG,
  new \AKlump\CheckPages\Output\VerboseDirective('D')
));

// or it's shorthand...
$printer->deliver(new \AKlump\CheckPages\Output\Message\DebugMessage(
  [
     'Demo debug message',
     '',
    'This is a debug message that will show with (A)ny verbosity.',
  ],
));
```

You will need an instance of `\AKlump\Messaging\MessengerInterface`, e.g.,

```php
$printer = new \AKlump\CheckPages\Output\Messenger\ConsoleEchoPrinter($runner->getOutput());
```

