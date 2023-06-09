<?php

namespace Monkey\Object;

use Exception;
use Monkey\Ast\Expression\CallExpression;
use Monkey\Ast\Expression\Identifier;
use Monkey\Ast\Expression\MacroLiteral;
use Monkey\Ast\Node;
use Monkey\Ast\Program;
use Monkey\Ast\Statement\LetStatement;
use Monkey\Evaluator\Evaluator;

class Environment
{
    /**
     * @param array<string, EvalObject> $store
     */
    public function __construct(
        public ?Environment $outer = null,
        public array $store = [],
    ) {
    }

    public function defineMacros(Program $program)
    {
        $definitions = [];

        foreach ($program->statements as $i => $statement) {
            if (!$statement instanceof LetStatement) {
                continue;
            }

            if (!$statement->value instanceof MacroLiteral) {
                continue;
            }

            $this->set($statement->name->value, new EvalMacro(
                $statement->value->parameters,
                $statement->value->body,
                $this,
            ));

            $definitions[] = $i;
        }

        for ($i = count($definitions) - 1; $i >= 0; $i--) {
            array_splice($program->statements, $i, 1);
        }
    }

    public function expandMacros(Program $program)
    {
        return $program->modify(function (Node $node): Node {
            if (!$node instanceof CallExpression) {
                return $node;
            }

            if (!$node->function instanceof Identifier) {
                return $node;
            }

            $macro = $this->get($node->function->value);
            if (empty($macro)) {
                return $node;
            }

            if (!$macro instanceof EvalMacro) {
                return $node;
            }

            $arguments = [];
            foreach ($node->arguments as $argument) {
                $arguments[] = new EvalQuote($argument);
            }

            $extended = new Environment($macro->environment);

            foreach ($macro->parameters as $i => $parameter) {
                $extended->set($parameter->value, $arguments[$i]);
            }

            $evaluator = new Evaluator($extended);
            $evaluated = $evaluator->eval($macro->body);

            if (!$evaluated instanceof EvalQuote) {
                throw new Exception('we only support returning AST-nodes from macros');
            }

            return $evaluated->node;
        });
    }

    public function get(string $name): ?EvalObject
    {
        if (!empty($this->store[$name])) {
            return $this->store[$name];
        }

        if (!is_null($this->outer) && !empty($this->outer->store[$name])) {
            return $this->outer->store[$name];
        }

        return null;
    }

    public function set(string $name, EvalObject $evalObject): EvalObject
    {
        $this->store[$name] = $evalObject;
        return $evalObject;
    }

    public function extend(Environment $environment)
    {
        foreach ($environment->store as $key => $evalObject) {
            $this->set($key, $evalObject);
        }
    }
}
