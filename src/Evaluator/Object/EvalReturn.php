<?php

namespace Monkey\Evaluator\Object;

class EvalReturn implements EvalObject
{
    public function __construct(
        public EvalObject $value,
    ) {
    }

    public function type(): EvalType
    {
        return EvalType::RETURN;
    }

    public function inspect(): string
    {
        return $this->value->inspect();
    }
}
