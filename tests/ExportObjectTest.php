<?php

declare(strict_types=1);

namespace Brick\VarExporter\Tests;

use Brick\VarExporter\Tests\Classes\ParameterizedOptionalConstructor;
use Brick\VarExporter\Tests\Classes\ParameterizedRequiredConstructor;
use Brick\VarExporter\Tests\Classes\PrivateConstructor;
use Brick\VarExporter\Tests\Classes\PublicAndPrivateProperties;
use Brick\VarExporter\Tests\Classes\PublicPropertiesOnly;
use Brick\VarExporter\Tests\Classes\SetState;
use Brick\VarExporter\Tests\Classes\SetStateWithOverriddenPrivateProperties;

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

    public function testExportClassWithPublicAndPrivateProperties()
    {
        $object = new PublicAndPrivateProperties;

        $expectedMessage = 'Class "Brick\VarExporter\Tests\Classes\PublicAndPrivateProperties" has non-public properties, and must implement __set_state().';

        $this->assertExportThrows($expectedMessage, $object);
    }

    public function testExportClassWithSetState()
    {
        $object = new SetState;
        $object->foo = 'Hello';
        $object->bar = 'World';

        $expected = <<<'PHP'
\Brick\VarExporter\Tests\Classes\SetState::__set_state([
    'foo' => 'Hello',
    'bar' => 'World',
    'baz' => 'defaultValue'
])
PHP;

        $this->assertExportEquals($expected, $object);
    }

    public function testExportClassWithSetStateAndOverriddenPrivateProperties()
    {
        $object = new SetStateWithOverriddenPrivateProperties;

        $expectedMessage = 'Class "Brick\VarExporter\Tests\Classes\SetStateWithOverriddenPrivateProperties" has overridden private properties. This is not supported for exporting objects with __set_state().';

        $this->assertExportThrows($expectedMessage, $object);
    }

    public function testExportClassWithPrivateConstructor()
    {
        $object = PrivateConstructor::create();
        $object->foo = 'Foo';
        $object->bar = 'Bar';

        $expected = <<<'PHP'
(static function() {
    $object = (new ReflectionClass(\Brick\VarExporter\Tests\Classes\PrivateConstructor::class))->newInstanceWithoutConstructor();
    $object->foo = 'Foo';
    $object->bar = 'Bar';

    return $object;
})()
PHP;

        $this->assertExportEquals($expected, $object);
    }

    public function testExportClassWithParameterizedRequiredConstructor()
    {
        $object = new ParameterizedRequiredConstructor('FOO', 123);

        $expected = <<<'PHP'
(static function() {
    $object = (new ReflectionClass(\Brick\VarExporter\Tests\Classes\ParameterizedRequiredConstructor::class))->newInstanceWithoutConstructor();
    $object->foo = 'FOO';
    $object->bar = 123;

    return $object;
})()
PHP;

        $this->assertExportEquals($expected, $object);
    }

    public function testExportClassWithParameterizedOptionalConstructor()
    {
        $object = new ParameterizedOptionalConstructor();

        $expected = <<<'PHP'
(static function() {
    $object = new \Brick\VarExporter\Tests\Classes\ParameterizedOptionalConstructor;
    $object->foo = 'DefaultFoo';
    $object->bar = 0;

    return $object;
})()
PHP;

        $this->assertExportEquals($expected, $object);
    }
}
