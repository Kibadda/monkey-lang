<?php

namespace Monkey\Evaluator\Object;

class EvalBoolean implements EvalObject
{
    public function __construct(
        public bool $value,
    ) {
    }

    public function inspect(): string
    {
        return $this->value ? 'true' : 'false';
    }

    public function type(): EvalType
    {
        return EvalType::BOOLEAN;
    }
}
