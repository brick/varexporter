<?php

declare(strict_types=1);

namespace Brick\VarExporter\Tests\Classes;

class ParameterizedOptionalConstructor extends PublicPropertiesOnly
{
    public function __construct(string $foo = 'DefaultFoo', int $bar = 0)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }
}
