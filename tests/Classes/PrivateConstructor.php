<?php

declare(strict_types=1);

namespace Brick\VarExporter\Tests\Classes;

class PrivateConstructor extends PublicPropertiesOnly
{
    private function __construct()
    {
    }

    public static function create()
    {
        return new self();
    }
}
