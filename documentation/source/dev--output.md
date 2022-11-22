# Developers Note: User Messages

When working on this project, never `echo` directly. Follow the code as shown below for user feedback. The instance of `\AKlump\Messaging\MessengerInterface` will determine how the messages are printed, you just worry about the message, level, and verbosity as you write code.

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
$printer->deliver(new \AKlump\CheckPages\Output\DebugMessage(
  [
     'Demo debug message',
     '',
    'This is a debug message that will show with (A)ny verbosity.',
  ],
));
```

You will need an instance of `\AKlump\Messaging\MessengerInterface`, e.g.,

```php
$printer = new \AKlump\CheckPages\Output\ConsoleEchoPrinter($runner->getOutput());
```
