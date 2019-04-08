<?php

declare(strict_types=1);

namespace Brick\VarExporter\Tests\Classes;

/**
 * No properties, and no constructor.
 */
class NoProperties
{
    // Static property should not be returned in the output
    public static $staticProp = [];
}
