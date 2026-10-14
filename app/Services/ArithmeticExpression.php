<?php

namespace App\Services;

use InvalidArgumentException;

/** A bounded arithmetic grammar, never PHP/JavaScript evaluation. */
class ArithmeticExpression
{
    private array $tokens = [];
    private int $position = 0;
    private int $depth = 0;
    private array $variables = [];
    private bool $validateOnly = false;

    public function evaluate(string $expression, array $variables, bool $validateOnly = false): float
    {
        if (strlen($expression) > 500 || trim($expression) === '') {
            throw new InvalidArgumentException('Enter a formula of at most 500 characters.');
        }
        $this->tokens = [];
        $offset = 0;
        while ($offset < strlen($expression)) {
            if (! preg_match('/\G\s*(\d+(?:\.\d+)?|\.\d+|[a-z][a-z0-9_]*|[+*\/(),-])/', $expression, $match, 0, $offset)) {
                if (trim(substr($expression, $offset)) === '') break;
                throw new InvalidArgumentException('Only numbers, variable keys, + - * /, parentheses, min, max and round are allowed.');
            }
            $this->tokens[] = $match[1];
            $offset += strlen($match[0]);
        }
        if (count($this->tokens) > 200) throw new InvalidArgumentException('Formula is too complex.');
        $this->position = $this->depth = 0;
        $this->variables = $variables;
        $this->validateOnly = $validateOnly;
        $value = $this->sum();
        if ($this->position !== count($this->tokens)) throw new InvalidArgumentException('Unexpected formula token.');

        return $this->bounded($value);
    }

    private function sum(): float
    {
        $value = $this->product();
        while (in_array($this->peek(), ['+', '-'], true)) {
            $op = $this->tokens[$this->position++];
            $right = $this->product();
            $value = $this->bounded($op === '+' ? $value + $right : $value - $right);
        }
        return $value;
    }

    private function product(): float
    {
        $value = $this->primary();
        while (in_array($this->peek(), ['*', '/'], true)) {
            $op = $this->tokens[$this->position++];
            $right = $this->primary();
            if ($op === '/' && $right == 0) {
                if (! $this->validateOnly) throw new InvalidArgumentException('Division by zero. Check the project inputs.');
                $value = 0;
            } else {
                $value = $this->bounded($op === '*' ? $value * $right : $value / $right);
            }
        }
        return $value;
    }

    private function primary(): float
    {
        if (++$this->depth > 32) throw new InvalidArgumentException('Formula nesting is too deep.');
        $token = $this->tokens[$this->position++] ?? null;
        if ($token === '+' || $token === '-') {
            $value = $this->primary() * ($token === '-' ? -1 : 1);
        } elseif ($token === '(') {
            $value = $this->sum();
            $this->expect(')');
        } elseif ($token !== null && is_numeric($token)) {
            $value = (float) $token;
        } elseif ($token !== null && preg_match('/^[a-z][a-z0-9_]*$/', $token)) {
            if ($this->peek() === '(') {
                if (! in_array($token, ['min', 'max', 'round'], true)) throw new InvalidArgumentException('Unknown function: ' . $token);
                ++$this->position;
                $args = [$this->sum()];
                while ($this->peek() === ',') {
                    ++$this->position;
                    $args[] = $this->sum();
                    if (count($args) > 20) throw new InvalidArgumentException('Too many function arguments.');
                }
                $this->expect(')');
                if ($token === 'round') {
                    $precision = $args[1] ?? 2;
                    if (count($args) > 2 || $precision != (int) $precision || $precision < 0 || $precision > 6) {
                        throw new InvalidArgumentException('round(value, decimals) accepts 0 to 6 decimal places.');
                    }
                    $value = round($args[0], (int) $precision);
                } else {
                    $value = $token === 'min' ? min($args) : max($args);
                }
            } else {
                if (! array_key_exists($token, $this->variables)) throw new InvalidArgumentException('Unknown or later variable: ' . $token);
                $value = (float) $this->variables[$token];
            }
        } else {
            throw new InvalidArgumentException('Expected a number, variable or parentheses.');
        }
        --$this->depth;
        return $this->bounded($value);
    }

    private function peek(): ?string
    {
        return $this->tokens[$this->position] ?? null;
    }

    private function expect(string $token): void
    {
        if (($this->tokens[$this->position++] ?? null) !== $token) throw new InvalidArgumentException('Expected ' . $token . '.');
    }

    private function bounded(float $value): float
    {
        if (! $this->validateOnly && (! is_finite($value) || abs($value) > 1_000_000_000_000)) {
            throw new InvalidArgumentException('Calculation exceeds the supported numeric range.');
        }
        return $value;
    }
}
