<?php

namespace Monkey\Object;

class EvalClosure implements EvalObject
{
    /**
     * @param EvalObject[] $free
     */
    public function __construct(
        public EvalCompiledFunction $function,
        public array $free,
    ) {
    }

    public function type(): EvalType
    {
        return EvalType::CLOSURE;
    }

    public function inspect(): string
    {
        return "Closure[{$this->type()->name}]";
    }
}
