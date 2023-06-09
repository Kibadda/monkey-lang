<?php

namespace Monkey\Object;

use Monkey\Ast\Expression\Identifier;
use Monkey\Ast\Statement\BlockStatement;

class EvalMacro implements EvalObject
{
    /**
     * @param Identifier[] $parameters
     */
    public function __construct(
        public array $parameters,
        public BlockStatement $body,
        public Environment $environment,
    ) {
    }

    public function type(): EvalType
    {
        return EvalType::MACRO;
    }

    public function inspect(): string
    {
        $parameters = [];

        foreach ($this->parameters as $parameter) {
            $parameters[] = $parameter->string();
        }

        return "macro(" . implode(', ', $parameters) . ") {\n\t{$this->body->string()}\n}";
    }
}
