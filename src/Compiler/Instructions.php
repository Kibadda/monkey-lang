<?php

namespace Monkey\Compiler;

use ArrayAccess;
use Iterator;
use Monkey\Code\Code;

class Instructions implements ArrayAccess, Iterator
{
    /**
     * @param int[] $elements
     */
    public function __construct(
        public array $elements,
        private int $position = 0,
    ) {
        if ($this->count() > 0 && $elements[0] instanceof self) {
            /** @var self[] $elements */
            $this->elements = [];
            foreach ($elements as $element) {
                $this->merge($element);
            }
        }
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

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function current(): int
    {
        return $this->elements[$this->position];
    }

    public function key(): int
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        return array_key_exists($this->position, $this->elements);
    }

    public function merge(self $instructions): void
    {
        foreach ($instructions as $element) {
            $this->elements[] = $element;
        }
    }

    public function slice(int $offset, ?int $length = null): Instructions
    {
        return new self(array_slice($this->elements, $offset, $length));
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
            $code = Code::tryFrom($this[$i]);
            $definition = $code->definition();

            list($operands, $read) = $code->readOperands($this->slice($i + 1));

            $string .= sprintf("%04d %s\n", $i, match (true) {
                count($definition->operandWidths) != count($operands) => 'ERROR: operand len ' . count($operands) . ' does not match defined ' . count($definition->operandWidths),
                default => match (count($operands)) {
                    0 => $definition->name,
                    1 => "{$definition->name} {$operands[0]}",
                    default => "ERROR: unhandled operandCount for {$definition->name}",
                },
            });

            $i += 1 + $read;
        }

        return $string;
    }
}
