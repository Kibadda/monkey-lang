<?php

namespace Monkey\Evaluator\Object;

class EvalString implements EvalObject, HashKey
{
    use HasHashKey;

    public function __construct(
        public string $value,
    ) {
    }

    public function type(): EvalType
    {
        return EvalType::STRING;
    }

    public function inspect(): string
    {
        return $this->value;
    }
}
