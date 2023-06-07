<?php

namespace Monkey\Compiler;

use Monkey\Ast\Expression\InfixExpression;
use Monkey\Ast\Expression\IntegerLiteral;
use Monkey\Ast\Node;
use Monkey\Ast\Program;
use Monkey\Ast\Statement\ExpressionStatement;
use Monkey\Evaluator\Object\EvalObject;

class Compiler
{
    /**
     * @param EvalObject[] $constants
     */
    public function __construct(
        public Instructions $instructions = new Instructions([]),
        public array $constants = [],
    ) {
    }

    public function compile(Node $node)
    {
        match (true) {
            $node instanceof Program => call_user_func(function () use ($node) {
                foreach ($node->statements as $statement) {
                    $error = $this->compile($statement);
                    if ($error != null) {
                        return $error;
                    }
                }
            }),
            $node instanceof ExpressionStatement => call_user_func(function () use ($node) {
                $error = $this->compile($node->expression);
                if ($error != null) {
                    return $error;
                }
            }),
            $node instanceof InfixExpression => call_user_func(function () use ($node) {
                $error = $this->compile($node->left);
                if ($error != null) {
                    return $error;
                }

                $error = $this->compile($node->right);
                if ($error != null) {
                    return $error;
                }
            }),
            $node instanceof IntegerLiteral => call_user_func(function () use ($node) {
            }),
        };

        return null;
    }
}
