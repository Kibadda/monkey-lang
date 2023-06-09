<?php

namespace Monkey\Object;

class EvalHash implements EvalObject
{
    /**
     * @param array<string, Expression[]> $pairs
     */
    public function __construct(
        public array $pairs,
    ) {
    }

    public function type(): EvalType
    {
        return EvalType::HASH;
    }

    public function inspect(): string
    {
        $pairs = [];

        foreach ($this->pairs as $index => $pair) {
            list(, $key) = explode(':', $index);
            $pairs[] = "{$key}: {$pair[1]->inspect()}";
        }

        return '{' . implode(', ', $pairs) . '}';
    }
}
