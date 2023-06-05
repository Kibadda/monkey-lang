<?php

namespace Monkey\Evaluator\Object;

use Monkey\Ast\Node;

class EvalQuote implements EvalObject
{
    public function __construct(
        public Node $node,
    ) {
    }

    public function type(): EvalType
    {
        return EvalType::QUOTE;
    }

    public function inspect(): string
    {
        return "QUOTE({$this->node->string()})";
    }
}
