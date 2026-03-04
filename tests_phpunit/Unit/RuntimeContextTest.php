<?php

namespace AKlump\CheckPages\Tests\Unit;

use AKlump\CheckPages\RuntimeContext;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AKlump\CheckPages\RuntimeContext
 */
class RuntimeContextTest extends TestCase {

  protected function setUp(): void {
    parent::setUp();
    RuntimeContext::get()->clear();
  }

  public function testSingletonInstance() {
    $instance1 = RuntimeContext::get();
    $instance2 = RuntimeContext::get();
    $this->assertSame($instance1, $instance2);
  }

  public function testAddContextAndToString() {
    $context = RuntimeContext::get();

    // Create some dummy objects
    $obj1 = new class implements \Stringable {
      public function __toString() {
        return "Object 1";
      }
    };
    $obj2 = new class implements \Stringable {
      public function __toString() {
        return "Object 2";
      }
    };

    $context->add($obj1);
    $context->add($obj2);
    $context->add("A string context");

    $json = (string) $context;
    $decoded = json_decode($json, TRUE);

    $this->assertArrayHasKey(get_class($obj1), $decoded);
    $this->assertArrayHasKey(get_class($obj2), $decoded);
    $this->assertArrayHasKey('string', $decoded);

    $this->assertEquals(["Object 1"], $decoded[get_class($obj1)]);
    $this->assertEquals(["Object 2"], $decoded[get_class($obj2)]);
    $this->assertEquals(["A string context"], $decoded['string']);
  }

  public function testMultipleSameClassContexts() {
    $context = RuntimeContext::get();
    $obj1 = new class implements \Stringable {
      public function __toString() {
        return "Value 1";
      }
    };
    $obj2 = new class implements \Stringable {
      public function __toString() {
        return "Value 2";
      }
    };
    
    // Anonymous classes have different internal names if defined separately, 
    // but if we use the same class definition they would have same.
    // Let's use a named class for this test.
    $obj3 = new NamedDummyContext("One");
    $obj4 = new NamedDummyContext("Two");

    $context->add($obj3);
    $context->add($obj4);

    $decoded = json_decode((string) $context, TRUE);
    $class_name = NamedDummyContext::class;
    $this->assertCount(2, $decoded[$class_name]);
    $this->assertEquals(["One", "Two"], $decoded[$class_name]);
  }

  public function testAddWithCustomKey() {
    $context = RuntimeContext::get();
    $context->add("bar", "foo");
    $decoded = json_decode((string) $context, TRUE);
    $this->assertEquals(["bar"], $decoded["foo"]);
  }

  public function testAddObjectWithIdMethod() {
    $context = RuntimeContext::get();
    $obj = new class {
      public function id() {
        return "my-id";
      }
    };
    $context->add($obj);
    $decoded = json_decode((string) $context, TRUE);
    $this->assertEquals(["my-id"], $decoded[get_class($obj)]);
  }

  public function testAddArrayContext() {
    $context = RuntimeContext::get();
    $data = ["foo" => "bar"];
    $context->add($data, "my-data");
    $decoded = json_decode((string) $context, TRUE);
    $this->assertEquals([$data], $decoded["my-data"]);
  }

  public function testAddUnserializableFallbackToEmptyString() {
    $context = RuntimeContext::get();
    $obj = new \stdClass(); // stdClass has no __toString() or id()
    $context->add($obj, "fail-key");
    $decoded = json_decode((string) $context, TRUE);
    $this->assertEquals([""], $decoded["fail-key"]);
  }
}

class NamedDummyContext implements \Stringable {
  private $val;
  public function __construct($val) { $this->val = $val; }
  public function __toString() { return $this->val; }
}
