<?php

declare(strict_types=1);

namespace Brick\VarExporter\Tests\Classes;

/**
 * __serialize() and __unserialize() magic methods, and constructor.
 */
class SerializeMagicMethodsWithConstructor extends SerializeMagicMethods
{
    public function __construct(string $foo, int $bar)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }
}
