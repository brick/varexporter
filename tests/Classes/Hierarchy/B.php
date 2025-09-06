<?php

declare(strict_types=1);

namespace Brick\VarExporter\Tests\Classes\Hierarchy;

class B extends A
{
    private $privateInB = 'private in B';

    protected $protectedInB = 'protected in B';

    public $publicInB = 'public in B';

    // parent has a property with same name
    private $privateOverridden = 'in B';
}
