<?php

declare(strict_types=1);
namespace Brick\VarExporter;

interface CodeInterface {

    /**
     * @return string[]
     */
    public function toLines(): array;

}
