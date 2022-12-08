<?php

declare(strict_types=1);

namespace Brick\VarExporter\Tests\Classes;

class PublicReadonlyPropertiesWithConstructor
{
    public function __construct(
        public readonly string $foo,
        private readonly string $bar,
        public string $baz
    ) {
    }
}
