# Plugin Scaffold

## New Version Compatible with the Legacy Plugin

1. Add to _composer.json_.

    ```json
    {
        "psr-4": {
            "AKlump\\CheckPages\\Plugin\\": [
                "plugins/foo/"
            ]
        }
    }
    ```
1. Use this as starting point.

    ```php
    <?php
    
    namespace AKlump\CheckPages\Plugin;
    
    /**
     * Implements the Json Schema plugin.
     */
    final class Foo implements \Symfony\Component\EventDispatcher\EventSubscriberInterface {
    
      /**
       * @var \AKlump\CheckPages\Parts\Runner
       */
      protected $runner;
    
      /**
       * {@inheritdoc}
       */
      public static function getSubscribedEvents() {
        return [
          \AKlump\CheckPages\Event::ASSERT_CREATED => [
            function (\AKlump\CheckPages\Event\AssertEventInterface $event) { 
   
              // TODO Some logic to see if you should really instantiate.
              $should_apply = boolval($event->getAssert()->baz);
   
              if ($should_apply) {
                $obj = new self();
    
                return $obj->prepareAssertion($event);
              }
            },
          ],
        ];
      }
    
      /**
       * {@inheritdoc}
       */
      public function eventHandler(AssertEventInterface $event) {
        $this->runner = $event->getTest()->getRunner();
        $event->getAssert()->setToStringOverride([$this, 'onAssertToString']);
    
        // TODO Handle the event
      }
    
      /**
       * {@inheritdoc}
       */
      public function onAssertToString(string $stringified, \AKlump\CheckPages\Assert $assert): string {
    
        // TODO Modify $stringified as necessary.
    
        return $stringified;
      }
    
    }
    
    ```
