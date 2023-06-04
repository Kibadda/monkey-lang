<?php

namespace Monkey\Evaluator\Object;

interface EvalObject
{
    public function type(): EvalType;
    public function inspect(): string;
}
