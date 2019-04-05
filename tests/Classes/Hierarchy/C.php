<?php

declare(strict_types=1);

namespace Brick\VarExporter\Tests\Classes\Hierarchy;

class C extends B
{
    private $privateInC = 'private in C';
    protected $protectedInC = 'protected in C';
    public $publicInC = 'public in C';

    // parent has a property with same name
    private $privateOverridden = 'in C';
}
