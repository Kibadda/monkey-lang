<?php

namespace Monkey\Object;

class EvalBoolean implements EvalObject, HashKey
{
    use HasHashKey;

    public function __construct(
        public bool $value,
    ) {
    }

    public function type(): EvalType
    {
        return EvalType::BOOLEAN;
    }

    public function inspect(): string
    {
        return $this->value ? 'true' : 'false';
    }
}
