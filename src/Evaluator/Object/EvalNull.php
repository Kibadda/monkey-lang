<?php

namespace Monkey\Evaluator\Object;

class EvalNull implements EvalObject
{
    public function inspect(): string
    {
        return 'null';
    }

    public function type(): EvalType
    {
        return EvalType::NULL;
    }
}
