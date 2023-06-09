<?php

namespace Monkey\Object;

trait HasHashKey
{
    public function hashKey(): string
    {
        return "{$this->type()->name}:{$this->inspect()}";
    }
}
