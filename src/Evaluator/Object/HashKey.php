<?php

namespace Monkey\Evaluator\Object;

trait Hashkey
{
    public function hashKey(): string
    {
        return "{$this->type()->name}:{$this->inspect()}";
    }
}
