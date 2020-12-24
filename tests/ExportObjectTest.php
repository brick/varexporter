<?php

declare(strict_types=1);

namespace Brick\VarExporter\Tests;

use Brick\VarExporter\Tests\Classes\ConstructorAndNoProperties;
use Brick\VarExporter\Tests\Classes\NoProperties;
use Brick\VarExporter\Tests\Classes\Hierarchy;
use Brick\VarExporter\Tests\Classes\PublicPropertiesWithConstructor;
use Brick\VarExporter\Tests\Classes\PrivateConstructor;
use Brick\VarExporter\Tests\Classes\PublicPropertiesOnly;
use Brick\VarExporter\Tests\Classes\SerializeMagicMethods;
use Brick\VarExporter\Tests\Classes\SerializeMagicMethodsWithConstructor;
use Brick\VarExporter\Tests\Classes\SetState;
use Brick\VarExporter\Tests\Classes\SetStateWithOverriddenPrivateProperties;
use Brick\VarExporter\VarExporter;

/**
 * Tests exporting various objects.
 */
class ExportObjectTest extends AbstractTestCase
{
    public function testExportStdClass()
    {
        $object = new \stdClass;
        $object->foo = 'Hello';
        $object->bar = 'bar';
        $object->baz = new \stdClass;
        $object->baz->foo = 'Hello';

        $expected = <<<'PHP'
(object) [
    'foo' => 'Hello',
    'bar' => 'bar',
    'baz' => (object) [
        'foo' => 'Hello'
    ]
]
PHP;

        $this->assertExportEquals($expected, $object);
    }

    public function testExportClassWithNoProperties()
    {
        $object = new NoProperties;

        $expected = 'new \Brick\VarExporter\Tests\Classes\NoProperties';

        $this->assertExportEquals($expected, $object);
    }

    public function testExportClassWithDynamicPropertiesOnly()
    {
        $object = new NoProperties;
        $object->x = 1.0;
        $object->dynamicProp = 'Hello';
        $object->{'$weird%Prop'} = 'World';
        $object->{'123'} = 'Numeric dynamic prop';

        $expected = <<<'PHP'
(static function() {
    $object = new \Brick\VarExporter\Tests\Classes\NoProperties;

    $object->x = 1.0;
    $object->dynamicProp = 'Hello';
    $object->{'$weird%Prop'} = 'World';
    $object->{'123'} = 'Numeric dynamic prop';

    return $object;
})()
PHP;

        $this->assertExportEquals($expected, $object);
    }

    public function testExportClassWithDynamicPropertiesOnly_SkipDynamicProperties()
    {
        $object = new NoProperties;
        $object->dynamicProp = 'Hello';
        $object->{'$weird%Prop'} = 'World';

        $expected = 'new \Brick\VarExporter\Tests\Classes\NoProperties';

        $this->assertExportEquals($expected, $object, VarExporter::SKIP_DYNAMIC_PROPERTIES);
    }

    public function testExportClassWithConstructorAndNoProperties()
    {
        $object = new ConstructorAndNoProperties();

        $expected = <<<'PHP'
(static function() {
    $class = new \ReflectionClass(\Brick\VarExporter\Tests\Classes\ConstructorAndNoProperties::class);
    $object = $class->newInstanceWithoutConstructor();

    return $object;
})()
PHP;

        $this->assertExportEquals($expected, $object, VarExporter::SKIP_DYNAMIC_PROPERTIES);
    }

    public function testExportClassWithPublicPropertiesOnly()
    {
        $object = new PublicPropertiesOnly;
        $object->foo = 'Hello';
        $object->bar = 'World';

        $expected = <<<'PHP'
(static function() {
    $object = new \Brick\VarExporter\Tests\Classes\PublicPropertiesOnly;

    $object->foo = 'Hello';
    $object->bar = 'World';

    return $object;
})()
PHP;

        $this->assertExportEquals($expected, $object);
    }

    public function testExportObjectWithPublicAndDynamicProperties()
    {
        $object = new PublicPropertiesOnly;
        $object->foo = 'Hello';
        $object->bar = 'World';
        $object->dynamic = 'Dynamic';

        $expected = <<<'PHP'
(static function() {
    $object = new \Brick\VarExporter\Tests\Classes\PublicPropertiesOnly;

    $object->foo = 'Hello';
    $object->bar = 'World';
    $object->dynamic = 'Dynamic';

    return $object;
})()
PHP;

        $this->assertExportEquals($expected, $object);
    }

    public function testExportObjectWithPublicAndDynamicProperties_SkipDynamicProperties()
    {
        $object = new PublicPropertiesOnly;
        $object->foo = 'Hello';
        $object->bar = 'World';
        $object->dynamic = 'Dynamic';

        $expected = <<<'PHP'
(static function() {
    $object = new \Brick\VarExporter\Tests\Classes\PublicPropertiesOnly;

    $object->foo = 'Hello';
    $object->bar = 'World';

    return $object;
})()
PHP;

        $this->assertExportEquals($expected, $object, VarExporter::SKIP_DYNAMIC_PROPERTIES);
    }

    public function testExportClassWithSetState()
    {
        $object = new SetState;
        $object->foo = 'Hello';
        $object->bar = 'World';

        $expected = <<<'PHP'
\Brick\VarExporter\Tests\Classes\SetState::__set_state([
    'baz' => 'defaultValue',
    'foo' => 'Hello',
    'bar' => 'World'
])
PHP;

        $this->assertExportEquals($expected, $object);
    }

    public function testExportClassWithSetStateAndUnsetProperties()
    {
        $object = new SetState;
        $object->foo = null;

        unset($object->bar);

        $expected = <<<'PHP'
\Brick\VarExporter\Tests\Classes\SetState::__set_state([
    'baz' => 'defaultValue',
    'foo' => null
])
PHP;

        $this->assertExportEquals($expected, $object);
    }

    public function testExportClassWithSetStateAndDynamicProperties()
    {
        $object = new SetState;
        $object->foo = 'Hello';
        $object->bar = 'World';
        $object->dynamic = 'Dynamic property';
        $object->{'123'} = 'Numeric dynamic property';

        $expected = <<<'PHP'
\Brick\VarExporter\Tests\Classes\SetState::__set_state([
    'baz' => 'defaultValue',
    'foo' => 'Hello',
    'bar' => 'World',
    'dynamic' => 'Dynamic property',
    123 => 'Numeric dynamic property'
])
PHP;

        $this->assertExportEquals($expected, $object);
    }

    public function testExportClassWithSetStateAndDynamicProperties_SkipDynamicProperties()
    {
        $object = new SetState;
        $object->foo = 'Hello';
        $object->bar = 'World';
        $object->dynamic = 'Dynamic property';

        $expected = <<<'PHP'
\Brick\VarExporter\Tests\Classes\SetState::__set_state([
    'baz' => 'defaultValue',
    'foo' => 'Hello',
    'bar' => 'World'
])
PHP;

        $this->assertExportEquals($expected, $object, VarExporter::SKIP_DYNAMIC_PROPERTIES);
    }

    public function testExportClassWithSetStateAndOverriddenPrivateProperties()
    {
        $object = new SetStateWithOverriddenPrivateProperties;

        $expectedMessage = 'Class "Brick\VarExporter\Tests\Classes\SetStateWithOverriddenPrivateProperties" has overridden private property "baz". This is not supported for exporting objects with __set_state().';

        $this->assertExportThrows($expectedMessage, $object);
    }

    public function testExportClassWithPrivateConstructor()
    {
        $object = PrivateConstructor::create();
        $object->foo = 'Foo';
        $object->bar = 'Bar';

        $expected = <<<'PHP'
(static function() {
    $class = new \ReflectionClass(\Brick\VarExporter\Tests\Classes\PrivateConstructor::class);
    $object = $class->newInstanceWithoutConstructor();

    $object->foo = 'Foo';
    $object->bar = 'Bar';

    return $object;
})()
PHP;

        $this->assertExportEquals($expected, $object);
    }

    public function testExportClassWithPublicPropertiesAndConstructor()
    {
        $object = new PublicPropertiesWithConstructor();

        $expected = <<<'PHP'
(static function() {
    $class = new \ReflectionClass(\Brick\VarExporter\Tests\Classes\PublicPropertiesWithConstructor::class);
    $object = $class->newInstanceWithoutConstructor();

    $object->foo = 'DefaultFoo';
    $object->bar = 0;

    return $object;
})()
PHP;

        $this->assertExportEquals($expected, $object);
    }

    public function testExportClassWithSerializeMagicMethods()
    {
        $object = new SerializeMagicMethods;

        $object->foo = 'Foo';
        $object->bar = [1, 2];

        $expected = <<<'PHP'
(static function() {
    $object = new \Brick\VarExporter\Tests\Classes\SerializeMagicMethods;

    $object->__unserialize([
        'foo' => 'Foo',
        'bar' => [
            1,
            2
        ]
    ]);

    return $object;
})()
PHP;

        $this->assertExportEquals($expected, $object);
    }

    public function testExportClassWithSerializeMagicMethodsAndConstructor()
    {
        $object = new SerializeMagicMethodsWithConstructor('Test', 1234);

        $expected = <<<'PHP'
(static function() {
    $class = new \ReflectionClass(\Brick\VarExporter\Tests\Classes\SerializeMagicMethodsWithConstructor::class);
    $object = $class->newInstanceWithoutConstructor();

    $object->__unserialize([
        'foo' => 'Test',
        'bar' => 1234
    ]);

    return $object;
})()
PHP;

        $this->assertExportEquals($expected, $object);
    }

    public function testExportClassWithSerializeMagicMethodsAndConstructor_AddTypeHints()
    {
        $object = new SerializeMagicMethodsWithConstructor('Test', 1234);

        $expected = <<<'PHP'
(static function() {
    $class = new \ReflectionClass(\Brick\VarExporter\Tests\Classes\SerializeMagicMethodsWithConstructor::class);

    /** @var \Brick\VarExporter\Tests\Classes\SerializeMagicMethodsWithConstructor $object */
    $object = $class->newInstanceWithoutConstructor();

    $object->__unserialize([
        'foo' => 'Test',
        'bar' => 1234
    ]);

    return $object;
})()
PHP;

        $this->assertExportEquals($expected, $object, VarExporter::ADD_TYPE_HINTS);
    }

    public function testExportClassHierarchy()
    {
        $object = Hierarchy\C::create();
        $object->dynamicProperty = 'A property declared dynamically';

        $expected = <<<'PHP'
(static function() {
    $class = new \ReflectionClass(\Brick\VarExporter\Tests\Classes\Hierarchy\C::class);
    $object = $class->newInstanceWithoutConstructor();

    $object->publicInC = 'public in C';
    $object->publicInB = 'public in B';
    $object->publicInA = 'public in A';
    $object->dynamicProperty = 'A property declared dynamically';

    (function() {
        $this->privateInC = 'private in C';
        $this->protectedInC = 'protected in C';
        $this->privateOverridden = 'in C';
        $this->protectedInB = 'protected in B';
        $this->protectedInA = 'protected in A';
    })->bindTo($object, \Brick\VarExporter\Tests\Classes\Hierarchy\C::class)();

    (function() {
        $this->privateInB = 'private in B';
        $this->privateOverridden = 'in B';
    })->bindTo($object, \Brick\VarExporter\Tests\Classes\Hierarchy\B::class)();

    (function() {
        $this->privateInA = 'private in A';
        $this->privateOverridden = 'in A';
    })->bindTo($object, \Brick\VarExporter\Tests\Classes\Hierarchy\A::class)();

    return $object;
})()
PHP;

        $this->assertExportEquals($expected, $object);
    }

    public function testExportClassHierarchy_AddTypeHints_SkipDynamicProperties()
    {
        $object = Hierarchy\C::create();
        $object->dynamicProperty = 'A property declared dynamically';

        $expected = <<<'PHP'
(static function() {
    $class = new \ReflectionClass(\Brick\VarExporter\Tests\Classes\Hierarchy\C::class);

    /** @var \Brick\VarExporter\Tests\Classes\Hierarchy\C $object */
    $object = $class->newInstanceWithoutConstructor();

    $object->publicInC = 'public in C';
    $object->publicInB = 'public in B';
    $object->publicInA = 'public in A';

    (function() {
        /** @var \Brick\VarExporter\Tests\Classes\Hierarchy\C $this */
        $this->privateInC = 'private in C';
        $this->protectedInC = 'protected in C';
        $this->privateOverridden = 'in C';
        $this->protectedInB = 'protected in B';
        $this->protectedInA = 'protected in A';
    })->bindTo($object, \Brick\VarExporter\Tests\Classes\Hierarchy\C::class)();

    (function() {
        /** @var \Brick\VarExporter\Tests\Classes\Hierarchy\B $this */
        $this->privateInB = 'private in B';
        $this->privateOverridden = 'in B';
    })->bindTo($object, \Brick\VarExporter\Tests\Classes\Hierarchy\B::class)();

    (function() {
        /** @var \Brick\VarExporter\Tests\Classes\Hierarchy\A $this */
        $this->privateInA = 'private in A';
        $this->privateOverridden = 'in A';
    })->bindTo($object, \Brick\VarExporter\Tests\Classes\Hierarchy\A::class)();

    return $object;
})()
PHP;

        $this->assertExportEquals($expected, $object, VarExporter::ADD_TYPE_HINTS | VarExporter::SKIP_DYNAMIC_PROPERTIES);
    }

    public function testExportClassHierarchyWithUnsetProperties()
    {
        $object = Hierarchy\C::create();

        $object->publicInA = null;
        unset($object->publicInB);

        (function() {
            /** @var Hierarchy\C $this */
            unset($this->privateInC);
            unset($this->protectedInB);
        })->bindTo($object, Hierarchy\C::class)();

        (function() {
            /** @var Hierarchy\A $this */
            unset($this->privateOverridden);
        })->bindTo($object, Hierarchy\A::class)();

        $expected = <<<'PHP'
(static function() {
    $class = new \ReflectionClass(\Brick\VarExporter\Tests\Classes\Hierarchy\C::class);
    $object = $class->newInstanceWithoutConstructor();

    $object->publicInC = 'public in C';
    $object->publicInA = null;
    unset($object->publicInB);

    (function() {
        $this->protectedInC = 'protected in C';
        $this->privateOverridden = 'in C';
        $this->protectedInA = 'protected in A';
        unset($this->privateInC);
        unset($this->protectedInB);
    })->bindTo($object, \Brick\VarExporter\Tests\Classes\Hierarchy\C::class)();

    (function() {
        $this->privateInB = 'private in B';
        $this->privateOverridden = 'in B';
    })->bindTo($object, \Brick\VarExporter\Tests\Classes\Hierarchy\B::class)();

    (function() {
        $this->privateInA = 'private in A';
        unset($this->privateOverridden);
    })->bindTo($object, \Brick\VarExporter\Tests\Classes\Hierarchy\A::class)();

    return $object;
})()
PHP;

        $this->assertExportEquals($expected, $object);
    }

    public function testExportObjectWithRestrictiveOptions()
    {
        $object = new PublicPropertiesOnly();

        $expectedMessage =
            'Class "Brick\VarExporter\Tests\Classes\PublicPropertiesOnly" cannot be exported ' .
            'using the current options.';

        $this->assertExportThrows($expectedMessage, $object, VarExporter::NOT_ANY_OBJECT);
    }
}
