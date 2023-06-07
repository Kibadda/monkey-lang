<?php

namespace Monkey\Compiler;

use ArrayAccess;
use Monkey\Code\Code;

class Instructions implements ArrayAccess
{
    /**
     * @param self $instructionsArray
     */
    public static function merge(...$instructionsArray): self
    {
        $elements = [];
        foreach ($instructionsArray as $instructions) {
            $elements = array_merge($elements, $instructions->elements);
        }

        return new self($elements);
    }

    /**
     * @param int[] $elements
     */
    public function __construct(
        public array $elements,
    ) {
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->elements);
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->elements[$offset]);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->elements[] = $value;
        } else {
            $this->elements[$offset] = $value;
        }
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->offsetExists($offset) ? $this->elements[$offset] : null;
    }

    public function slice($offset): Instructions
    {
        return new Instructions(array_slice($this->elements, $offset));
    }

    public function count(): int
    {
        return count($this->elements);
    }

    public function string(): string
    {
        $string = '';

        $i = 0;
        while ($i < $this->count()) {
            $definition = Code::tryFrom($this[$i])->definition();

            list($operands, $read) = Code::readOperands($definition, $this->slice($i + 1));

            $string .= sprintf("%04d %s\n", $i, match (true) {
                count($definition->operandWidths) != count($operands) => 'ERROR: operand len ' . count($operands) . ' does not match defined ' . count($definition->operandWidths),
                default => match (count($operands)) {
                    1 => "{$definition->name} {$operands[0]}",
                    default => "ERROR: unhandled operandCount for {$definition->name}",
                },
            });

            $i += 1 + $read;
        }

        return $string;
    }
}
