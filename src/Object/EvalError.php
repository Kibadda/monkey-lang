<?php

namespace Monkey\Object;

class EvalError implements EvalObject
{
    public function __construct(
        public string $message,
    ) {
    }

    public function type(): EvalType
    {
        return EvalType::ERROR;
    }

    public function inspect(): string
    {
        return "ERROR: {$this->message}";
    }
}
