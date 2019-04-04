<?php

declare(strict_types=1);

namespace Brick\VarExporter\Tests\Classes;

class SetState extends PublicAndPrivateProperties
{
    public static function __set_state()
    {
    }
}
