<?php

namespace Monkey\Evaluator\Object;

class EvalBoolean implements EvalObject
{
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

    public function hashKey(): string
    {
        return "{$this->type()->name}:{$this->inspect()}";
    }
}
