<?php

declare(strict_types=1);

namespace Brick\VarExporter\Tests\Classes\Hierarchy;

abstract class A
{
    private $privateInA = 'private in A';

    protected $protectedInA = 'protected in A';

    public $publicInA = 'public in A';

    private $privateOverridden = 'in A';

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new static();
    }
}
