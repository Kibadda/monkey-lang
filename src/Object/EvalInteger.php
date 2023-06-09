<?php

namespace Monkey\Object;

class EvalInteger implements EvalObject, HashKey
{
    use HasHashKey;

    public function __construct(
        public int $value,
    ) {
    }

    public function type(): EvalType
    {
        return EvalType::INTEGER;
    }

    public function inspect(): string
    {
        return "{$this->value}";
    }
}
