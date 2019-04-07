<?php

declare(strict_types=1);

namespace Brick\VarExporter\Internal\Lexer;

class Lexer
{
    /**
     * @var Token[]
     */
    private $tokens = [];

    /**
     * The current index in the token array.
     *
     * @var int
     */
    private $currentIndex = 0;

    /**
     * Lexer constructor.
     *
     * @param string $code
     */
    public function __construct(string $code)
    {
        $tokens = token_get_all($code);

        $line = 1;
        $index = 0;

        foreach ($tokens as $token) {
            if (is_array($token)) {
                $token = new Token($index++, ...$token);
                $this->tokens[] = $token;
                $line = $token->line + $this->countLines($token->code) - 1;
            } else {
                $this->tokens[] = new Token($index++, $token, $token, $line);
            }
        }
    }

    /**
     * @param string $code
     *
     * @return int
     */
    private function countLines(string $code) : int
    {
        $code = str_replace(["\r\n", "\r"], "\n", $code);

        return substr_count($code, "\n") + 1;
    }

    /**
     * @param int $startIndex
     * @param int $endIndex
     *
     * @return \Generator
     */
    public function getTokenRange(int $startIndex, int $endIndex) : \Generator
    {
        for ($index = $startIndex; $index <= $endIndex; $index++) {
            yield $this->tokens[$index];
        }
    }

    /**
     * @param Token $token
     *
     * @return void
     */
    public function moveToToken(Token $token) : void
    {
        $this->currentIndex = $token->index;
    }

    /**
     * Moves to the next token matching any of the given types.
     *
     * @param int|string ...$types The token type(s) as an int, or as a char for single char tokens.
     *
     * @return Token
     *
     * @throws LexerException
     */
    public function moveToNext(...$types) : Token
    {
        for ($index = $this->currentIndex + 1; isset($this->tokens[$index]); $index++) {
            $token = $this->tokens[$index];

            if (in_array($token->type, $types, true)) {
                $this->currentIndex = $index;

                return $token;
            }
        }

        $names = array_map(function($type) {
            return is_int($type) ? token_name($type) : var_export($type, true);
        }, $types);

        $names = implode(' or ', $names);

        throw new LexerException('Unexpected EOF while looking for ' . $names . '.');
    }

    /**
     * Returns all the tokens located on the given line.
     *
     * @param int $line The line number.
     *
     * @return Token[]
     */
    public function getTokensOnLine(int $line) : array
    {
        $tokens = [];

        for ($index = 0; isset($this->tokens[$index]); $index++) {
            $token = $this->tokens[$index];

            if ($token->line < $line) {
                continue;
            }

            if ($token->line > $line) {
                break;
            }

            $tokens[] = $token;
        }

        return $tokens;
    }

    /**
     * Returns a single token of the given type on the given line.
     *
     * @param int $type The token type.
     * @param int $line The line number.
     *
     * @return Token
     *
     * @throws LexerException If there is not exactly one token of the given type on the given line.
     */
    public function getTokenOnLine(int $type, int $line) : Token
    {
        $tokens = $this->getTokensOnLine($line);

        $tokens = array_values(array_filter($tokens, function(Token $token) use ($type) {
            return $token->type === $type;
        }));

        $count = count($tokens);

        if ($count === 1) {
            return $tokens[0];
        }

        $name = token_name($type);

        throw new LexerException(sprintf('Expected exactly 1 %s token on line %d, %d found.', $name, $line, $count));
    }
}
