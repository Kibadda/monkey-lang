<?php

namespace Monkey\Object;

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
