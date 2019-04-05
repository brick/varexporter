<?php

declare(strict_types=1);

namespace Brick\VarExporter\Tests\Classes;

/**
 * A class with __serialize() and __unserialize() magic methods.
 */
class SerializeMagicMethods
{
    private $foo;
    private $bar;

    public function __construct(string $foo, int $bar)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }

    public function __serialize() : array
    {
        return [
            'foo' => $this->foo,
            'bar' => $this->bar
        ];
    }

    public function __unserialize(array $array) : void
    {
        $this->foo = $array['foo'];
        $this->bar = $array['bar'];
    }
}
