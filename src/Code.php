<?php

declare(strict_types=1);
namespace Brick\VarExporter;

final class Code implements CodeInterface {

    /**
     * @var string[]
     */
    private array $lines;

    /**
     * @param string[] $lines
     */
    private function __construct(array $lines) {
        $this->lines = $lines;
    }

    public static function create(string ...$lines): self {
        return new self($lines);
    }

    public function toLines(): array
    {
        return $this->lines;
    }

}
