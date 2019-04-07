<?php

declare(strict_types=1);

namespace Brick\VarExporter\Internal\Lexer;

final class Token
{
    /**
     * The token index (position in the token stream), 0-based.
     *
     * @var int
     */
    public $index;

    /**
     * The token type, as an int for T_* tokens, or as a char for single char tokens.
     *
     * @var int|string
     */
    public $type;

    /**
     * The string content.
     *
     * @var string
     */
    public $code;

    /**
     * The line number.
     *
     * @var int
     */
    public $line;

    /**
     * Token constructor.
     *
     * @param int        $index
     * @param int|string $type
     * @param string     $code
     * @param int        $line
     */
    public function __construct(int $index, $type, string $code, int $line)
    {
        $this->index = $index;
        $this->type  = $type;
        $this->code  = $code;
        $this->line  = $line;
    }
}
