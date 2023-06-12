<?php

namespace Monkey\Object;

class EvalHash implements EvalObject
{
    /**
     * @param array<string, EvalObject> $pairs
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

        foreach ($this->pairs as $index => $value) {
            list(, $key) = explode(':', $index);
            $pairs[] = "{$key}: {$value->inspect()}";
        }

        return '{' . implode(', ', $pairs) . '}';
    }
}
