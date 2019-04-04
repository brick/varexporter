<?php

declare(strict_types=1);

namespace Brick\VarExporter\Tests\Classes;

class PublicAndPrivateProperties extends PublicPropertiesOnly
{
    private $baz = 'defaultValue';
}
