<?php

namespace Monkey\Evaluator\Object;

class EvalString implements EvalObject
{
    // use Hashkey;

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

    public function hashKey(): string
    {
        return "{$this->type()->name}:{$this->inspect()}";
    }
}
