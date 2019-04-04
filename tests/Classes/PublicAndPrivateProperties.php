<?php

declare(strict_types=1);

namespace Brick\VarExporter\Tests\Classes;

class PublicAndPrivateProperties extends PublicPropertiesOnly
{
    private $baz = 'defaultValue';

    protected function setBaz($baz) : void
    {
        $this->baz = $baz;
    }
}
