<?php

declare(strict_types=1);

namespace Brick\VarExporter\Tests;

use Brick\VarExporter\Tests\Classes\NoProperties;
use Brick\VarExporter\Tests\Classes\Hierarchy;
use Brick\VarExporter\Tests\Classes\PublicPropertiesWithConstructor;
use Brick\VarExporter\Tests\Classes\PrivateConstructor;
use Brick\VarExporter\Tests\Classes\PublicAndPrivateProperties;
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
        $object->dynamicProp = 'Hello';
        $object->{'$weird%Prop'} = 'World';

        $expected = <<<'PHP'
(static function() {
    $object = new \Brick\VarExporter\Tests\Classes\NoProperties;
    $object->dynamicProp = 'Hello';
    $object->{'$weird%Prop'} = 'World';

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

        $expected = <<<'PHP'
(static function() {
    $object = new \Brick\VarExporter\Tests\Classes\NoProperties;

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

        $expected = <<<'PHP'
\Brick\VarExporter\Tests\Classes\SetState::__set_state([
    'baz' => 'defaultValue',
    'foo' => 'Hello',
    'bar' => 'World',
    'dynamic' => 'Dynamic property'
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

        $this->assertExportEquals($expected, $object, VarExporter::ALLOW_REFLECTION);
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

        $this->assertExportEquals($expected, $object, VarExporter::ALLOW_REFLECTION);
    }

    public function testExportClassWithConstructorThrowExceptionByDefault()
    {
        $object = new PublicPropertiesWithConstructor();

        $this->assertExportThrows('Class "Brick\VarExporter\Tests\Classes\PublicPropertiesWithConstructor" cannot be exported without resorting to reflection.', $object);
    }

    public function testExportClassWithPrivatePropertiesThrowExceptionByDefault()
    {
        $object = new PublicAndPrivateProperties;

        $this->assertExportThrows('Class "Brick\VarExporter\Tests\Classes\PublicAndPrivateProperties" cannot be exported without resorting to reflection.', $object);
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

    public function testExportClassHierarchyUsingReflection()
    {
        $object = Hierarchy\C::create();
        $object->dynamicProperty = 'A property declared dynamically';

        $expected = <<<'PHP'
(static function() {
    $class = new \ReflectionClass(\Brick\VarExporter\Tests\Classes\Hierarchy\C::class);
    $object = $class->newInstanceWithoutConstructor();

    $property = $class->getProperty('privateInC');
    $property->setAccessible(true);
    $property->setValue($object, 'private in C');

    $property = $class->getProperty('protectedInC');
    $property->setAccessible(true);
    $property->setValue($object, 'protected in C');

    $object->publicInC = 'public in C';

    $property = $class->getProperty('privateOverridden');
    $property->setAccessible(true);
    $property->setValue($object, 'in C');

    $property = $class->getProperty('protectedInB');
    $property->setAccessible(true);
    $property->setValue($object, 'protected in B');

    $object->publicInB = 'public in B';

    $property = $class->getProperty('protectedInA');
    $property->setAccessible(true);
    $property->setValue($object, 'protected in A');

    $object->publicInA = 'public in A';

    $object->dynamicProperty = 'A property declared dynamically';

    $property = new \ReflectionProperty(\Brick\VarExporter\Tests\Classes\Hierarchy\B::class, 'privateInB');
    $property->setAccessible(true);
    $property->setValue($object, 'private in B');

    $property = new \ReflectionProperty(\Brick\VarExporter\Tests\Classes\Hierarchy\B::class, 'privateOverridden');
    $property->setAccessible(true);
    $property->setValue($object, 'in B');

    $property = new \ReflectionProperty(\Brick\VarExporter\Tests\Classes\Hierarchy\A::class, 'privateInA');
    $property->setAccessible(true);
    $property->setValue($object, 'private in A');

    $property = new \ReflectionProperty(\Brick\VarExporter\Tests\Classes\Hierarchy\A::class, 'privateOverridden');
    $property->setAccessible(true);
    $property->setValue($object, 'in A');

    return $object;
})()
PHP;

        $this->assertExportEquals($expected, $object, VarExporter::ALLOW_REFLECTION);
    }

    public function testExportClassHierarchyUsingReflection_SkipDynamicProperties()
    {
        $object = Hierarchy\C::create();
        $object->dynamicProperty = 'A property declared dynamically';

        $expected = <<<'PHP'
(static function() {
    $class = new \ReflectionClass(\Brick\VarExporter\Tests\Classes\Hierarchy\C::class);
    $object = $class->newInstanceWithoutConstructor();

    $property = $class->getProperty('privateInC');
    $property->setAccessible(true);
    $property->setValue($object, 'private in C');

    $property = $class->getProperty('protectedInC');
    $property->setAccessible(true);
    $property->setValue($object, 'protected in C');

    $object->publicInC = 'public in C';

    $property = $class->getProperty('privateOverridden');
    $property->setAccessible(true);
    $property->setValue($object, 'in C');

    $property = $class->getProperty('protectedInB');
    $property->setAccessible(true);
    $property->setValue($object, 'protected in B');

    $object->publicInB = 'public in B';

    $property = $class->getProperty('protectedInA');
    $property->setAccessible(true);
    $property->setValue($object, 'protected in A');

    $object->publicInA = 'public in A';

    $property = new \ReflectionProperty(\Brick\VarExporter\Tests\Classes\Hierarchy\B::class, 'privateInB');
    $property->setAccessible(true);
    $property->setValue($object, 'private in B');

    $property = new \ReflectionProperty(\Brick\VarExporter\Tests\Classes\Hierarchy\B::class, 'privateOverridden');
    $property->setAccessible(true);
    $property->setValue($object, 'in B');

    $property = new \ReflectionProperty(\Brick\VarExporter\Tests\Classes\Hierarchy\A::class, 'privateInA');
    $property->setAccessible(true);
    $property->setValue($object, 'private in A');

    $property = new \ReflectionProperty(\Brick\VarExporter\Tests\Classes\Hierarchy\A::class, 'privateOverridden');
    $property->setAccessible(true);
    $property->setValue($object, 'in A');

    return $object;
})()
PHP;

        $this->assertExportEquals($expected, $object, VarExporter::ALLOW_REFLECTION | VarExporter::SKIP_DYNAMIC_PROPERTIES);
    }
}
