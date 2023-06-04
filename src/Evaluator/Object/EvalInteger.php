<?php

namespace Monkey\Evaluator\Object;

class EvalInteger implements EvalObject
{
    public function __construct(
        public int $value,
    ) {
    }

    public function inspect(): string
    {
        return "{$this->value}";
    }

    public function type(): EvalType
    {
        return EvalType::INTEGER;
    }
}
