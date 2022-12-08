<?php

declare(strict_types=1);

namespace Brick\VarExporter\Tests;

use Brick\VarExporter\Tests\Classes\ConstructorAndNoProperties;
use Brick\VarExporter\Tests\Classes\Enum;
use Brick\VarExporter\Tests\Classes\NoProperties;
use Brick\VarExporter\Tests\Classes\Hierarchy;
use Brick\VarExporter\Tests\Classes\PublicPropertiesWithConstructor;
use Brick\VarExporter\Tests\Classes\PrivateConstructor;
use Brick\VarExporter\Tests\Classes\PublicPropertiesOnly;
use Brick\VarExporter\Tests\Classes\PublicReadonlyPropertiesWithConstructor;
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
    public function testExportStdClass(): void
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

    public function testExportClassWithNoProperties(): void
    {
        $object = new NoProperties;

        $expected = 'new \Brick\VarExporter\Tests\Classes\NoProperties';

        $this->assertExportEquals($expected, $object);
    }

    public function testExportClassWithDynamicPropertiesOnly(): void
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

    public function testExportClassWithDynamicPropertiesOnly_SkipDynamicProperties(): void
    {
        $object = new NoProperties;
        $object->dynamicProp = 'Hello';
        $object->{'$weird%Prop'} = 'World';

        $expected = 'new \Brick\VarExporter\Tests\Classes\NoProperties';

        $this->assertExportEquals($expected, $object, VarExporter::SKIP_DYNAMIC_PROPERTIES);
    }

    public function testExportClassWithConstructorAndNoProperties(): void
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

    public function testExportClassWithPublicPropertiesOnly(): void
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

    public function testExportObjectWithPublicAndDynamicProperties(): void
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

    public function testExportObjectWithPublicAndDynamicProperties_SkipDynamicProperties(): void
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

    public function testExportClassWithSetState(): void
    {
        $object = new SetState;
        $object->foo = 'Hello';
        $object->bar = 'World';

        if (PHP_VERSION_ID >=80100) {
            $expected = <<<'PHP'
\Brick\VarExporter\Tests\Classes\SetState::__set_state([
    'foo' => 'Hello',
    'bar' => 'World',
    'baz' => 'defaultValue'
])
PHP;
        } else {
            $expected = <<<'PHP'
\Brick\VarExporter\Tests\Classes\SetState::__set_state([
    'baz' => 'defaultValue',
    'foo' => 'Hello',
    'bar' => 'World'
])
PHP;
        }

        $this->assertExportEquals($expected, $object);
    }

    public function testExportClassWithSetStateAndUnsetProperties(): void
    {
        $object = new SetState;
        $object->foo = null;

        unset($object->bar);

        if (PHP_VERSION_ID >= 80100) {
            $expected = <<<'PHP'
\Brick\VarExporter\Tests\Classes\SetState::__set_state([
    'foo' => null,
    'baz' => 'defaultValue'
])
PHP;
        } else {
            $expected = <<<'PHP'
\Brick\VarExporter\Tests\Classes\SetState::__set_state([
    'baz' => 'defaultValue',
    'foo' => null
])
PHP;
        }

        $this->assertExportEquals($expected, $object);
    }

    public function testExportClassWithSetStateAndDynamicProperties(): void
    {
        $object = new SetState;
        $object->foo = 'Hello';
        $object->bar = 'World';
        $object->dynamic = 'Dynamic property';
        $object->{'123'} = 'Numeric dynamic property';

        if (PHP_VERSION_ID >= 80100) {
            $expected = <<<'PHP'
\Brick\VarExporter\Tests\Classes\SetState::__set_state([
    'foo' => 'Hello',
    'bar' => 'World',
    'baz' => 'defaultValue',
    'dynamic' => 'Dynamic property',
    123 => 'Numeric dynamic property'
])
PHP;
        } else {
            $expected = <<<'PHP'
\Brick\VarExporter\Tests\Classes\SetState::__set_state([
    'baz' => 'defaultValue',
    'foo' => 'Hello',
    'bar' => 'World',
    'dynamic' => 'Dynamic property',
    123 => 'Numeric dynamic property'
])
PHP;
        }

        $this->assertExportEquals($expected, $object);
    }

    public function testExportClassWithSetStateAndDynamicProperties_SkipDynamicProperties(): void
    {
        $object = new SetState;
        $object->foo = 'Hello';
        $object->bar = 'World';
        $object->dynamic = 'Dynamic property';

        if (PHP_VERSION_ID >= 80100) {
            $expected = <<<'PHP'
\Brick\VarExporter\Tests\Classes\SetState::__set_state([
    'foo' => 'Hello',
    'bar' => 'World',
    'baz' => 'defaultValue'
])
PHP;
        } else {
            $expected = <<<'PHP'
\Brick\VarExporter\Tests\Classes\SetState::__set_state([
    'baz' => 'defaultValue',
    'foo' => 'Hello',
    'bar' => 'World'
])
PHP;
        }

        $this->assertExportEquals($expected, $object, VarExporter::SKIP_DYNAMIC_PROPERTIES);
    }

    public function testExportClassWithSetStateAndOverriddenPrivateProperties(): void
    {
        $object = new SetStateWithOverriddenPrivateProperties;

        $expectedMessage = 'Class "Brick\VarExporter\Tests\Classes\SetStateWithOverriddenPrivateProperties" has overridden private property "baz". This is not supported for exporting objects with __set_state().';

        $this->assertExportThrows($expectedMessage, $object);
    }

    public function testExportClassWithPrivateConstructor(): void
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

    public function testExportClassWithPublicPropertiesAndConstructor(): void
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

    public function testExportClassWithReadonlyPublicPropertiesAndConstructor(): void
    {
        if (PHP_VERSION_ID <= 80100) {
            $this->markTestSkipped('readonly properties are not available below PHP 8.1');
        }
        $object = new PublicReadonlyPropertiesWithConstructor('public readonly', 'private readonly', 'public');

        $expected = <<<'PHP'
(static function() {
    $class = new \ReflectionClass(\Brick\VarExporter\Tests\Classes\PublicReadonlyPropertiesWithConstructor::class);
    $object = $class->newInstanceWithoutConstructor();

    $object->baz = 'public';

    (function() {
        $this->foo = 'public readonly';
        $this->bar = 'private readonly';
    })->bindTo($object, \Brick\VarExporter\Tests\Classes\PublicReadonlyPropertiesWithConstructor::class)();

    return $object;
})()
PHP;

        $this->assertExportEquals($expected, $object);
    }

    public function testExportClassWithSerializeMagicMethods(): void
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

    public function testExportClassWithSerializeMagicMethodsAndConstructor(): void
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

    public function testExportClassWithSerializeMagicMethodsAndConstructor_AddTypeHints(): void
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

    public function testExportClassHierarchy(): void
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

    public function testExportClassHierarchy_AddTypeHints_SkipDynamicProperties(): void
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

    public function testExportClassHierarchyWithUnsetProperties(): void
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

    public function testExportObjectWithRestrictiveOptions(): void
    {
        $object = new PublicPropertiesOnly();

        $expectedMessage =
            'Class "Brick\VarExporter\Tests\Classes\PublicPropertiesOnly" cannot be exported ' .
            'using the current options.';

        $this->assertExportThrows($expectedMessage, $object, VarExporter::NOT_ANY_OBJECT);
    }

    /**
     * @requires PHP 8.1
     */
    public function testExportEnum(): void
    {
        $object = Enum::TEST;

        $expected = <<<'PHP'
Brick\VarExporter\Tests\Classes\Enum::TEST
PHP;

        $this->assertExportEquals($expected, $object);
    }
}
