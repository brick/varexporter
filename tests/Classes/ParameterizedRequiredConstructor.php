<?php

declare(strict_types=1);

namespace Brick\VarExporter\Tests\Classes;

class ParameterizedRequiredConstructor extends PublicPropertiesOnly
{
    public function __construct(string $foo, int $bar = 0)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }
}
