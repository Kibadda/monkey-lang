<?php

namespace Monkey\Evaluator;

use Monkey\Ast\Expression\Boolean;
use Monkey\Ast\Expression\CallExpression;
use Monkey\Ast\Expression\Expression;
use Monkey\Ast\Expression\FunctionLiteral;
use Monkey\Ast\Expression\Identifier;
use Monkey\Ast\Expression\IfExpression;
use Monkey\Ast\Expression\InfixExpression;
use Monkey\Ast\Expression\IntegerLiteral;
use Monkey\Ast\Expression\PrefixExpression;
use Monkey\Ast\Node;
use Monkey\Ast\Program;
use Monkey\Ast\Statement\BlockStatement;
use Monkey\Ast\Statement\ExpressionStatement;
use Monkey\Ast\Statement\LetStatement;
use Monkey\Ast\Statement\ReturnStatement;
use Monkey\Evaluator\Object\EvalBoolean;
use Monkey\Evaluator\Object\EvalError;
use Monkey\Evaluator\Object\EvalFunction;
use Monkey\Evaluator\Object\EvalInteger;
use Monkey\Evaluator\Object\EvalNull;
use Monkey\Evaluator\Object\EvalObject;
use Monkey\Evaluator\Object\EvalReturn;
use Monkey\Evaluator\Object\EvalType;

class Evaluator
{
    public static function new(Environment $environment): self
    {
        return new self(
            $environment,
            [
                true => new EvalBoolean(true),
                false => new EvalBoolean(false),
                null => new EvalNull(),
            ],
        );
    }

    private function __construct(
        private Environment $environment,
        private array $singletons,
    ) {
    }

    public function eval(Node $node): ?EvalObject
    {
        return match ($node::class) {
            Program::class => $this->evalProgram($node),
            ExpressionStatement::class => $this->eval($node->value),
            IntegerLiteral::class => new EvalInteger($node->value),
            Boolean::class => $this->boolean($node->value),
            PrefixExpression::class => call_user_func(function () use ($node) {
                $right = $this->eval($node->right);

                if ($this->isError($right)) {
                    return $right;
                }

                return $this->evalPrefixExpression($node->operator, $right);
            }),
            InfixExpression::class => call_user_func(function () use ($node) {
                $left = $this->eval($node->left);

                if ($this->isError($left)) {
                    return $left;
                }

                $right = $this->eval($node->right);

                if ($this->isError($right)) {
                    return $right;
                }

                return $this->evalInfixExpression($left, $node->operator, $right);
            }),
            BlockStatement::class => $this->evalBlockStatement($node),
            IfExpression::class => $this->evalIfExpression($node),
            ReturnStatement::class => call_user_func(function () use ($node) {
                $value = $this->eval($node->value);

                if ($this->isError($value)) {
                    return $value;
                }

                return new EvalReturn($value);
            }),
            LetStatement::class => call_user_func(function () use ($node) {
                $value = $this->eval($node->value);

                if ($this->isError($value)) {
                    return $value;
                }

                $this->environment->set($node->name->value, $value);
            }),
            Identifier::class => $this->evalIdentifier($node),
            FunctionLiteral::class => call_user_func(function () use ($node) {
                $parameters = $node->parameters;
                $body = $node->body;
                return new EvalFunction($parameters, $body, $this->environment);
            }),
            CallExpression::class => call_user_func(function () use ($node) {
                $function = $this->eval($node->function);

                if ($this->isError($function)) {
                    return $function;
                }

                $arguments = $this->evalExpressions($node->arguments);

                if (count($arguments) == 1 && $this->isError($arguments[0])) {
                    return $arguments[0];
                }

                return $this->applyFunction($function, $arguments);
            }),
            default => null,
        };
    }

    private function isError(?EvalObject $evalObject): bool
    {
        return is_null($evalObject) ?: $evalObject->type() == EvalType::ERROR;
    }

    private function boolean(bool $bool): EvalBoolean
    {
        return $this->singletons[$bool];
    }

    private function evalProgram(Program $program): ?EvalObject
    {
        $result = null;

        foreach ($program->statements as $statement) {
            $result = $this->eval($statement);

            if ($result instanceof EvalReturn) {
                return $result->value;
            }

            if ($result instanceof EvalError) {
                return $result;
            }
        }

        return $result;
    }

    private function evalBlockStatement(BlockStatement $block): EvalObject
    {
        $result = null;

        foreach ($block->statements as $statement) {
            $result = $this->eval($statement);

            if (!is_null($result) && ($result->type() == EvalType::RETURN || $result->type() == EvalType::ERROR)) {
                return $result;
            }
        }

        return $result;
    }

    private function evalPrefixExpression(string $operator, EvalObject $right): EvalObject
    {
        return match ($operator) {
            '!' => match ($right) {
                $this->singletons[true] => $this->boolean(false),
                $this->singletons[false] => $this->boolean(true),
                $this->singletons[null] => $this->boolean(true),
                default => $this->boolean(false),
            },
            '-' => call_user_func(function () use ($right) {
                if ($right->type() != EvalType::INTEGER) {
                    return new EvalError("unknown operator: -{$right->type()->name}");
                }

                return new EvalInteger(-$right->value);
            }),
            default => new EvalError("unknown operator: {$operator}{$right->type()->name}"),
        };
    }

    private function evalInfixExpression(EvalObject $left, string $operator, EvalObject $right): EvalObject
    {
        return match (true) {
            $left->type() == EvalType::INTEGER && $right->type() == EvalType::INTEGER => match ($operator) {
                '+' => new EvalInteger($left->value + $right->value),
                '-' => new EvalInteger($left->value - $right->value),
                '/' => new EvalInteger($left->value / $right->value),
                '*' => new EvalInteger($left->value * $right->value),
                '<' => $this->boolean($left->value < $right->value),
                '>' => $this->boolean($left->value > $right->value),
                '==' => $this->boolean($left->value == $right->value),
                '!=' => $this->boolean($left->value != $right->value),
                default => $this->singletons[null],
            },
            $operator == '==' => $this->boolean($left->value == $right->value),
            $operator == '!=' => $this->boolean($left->value != $right->value),
            $left->type() != $right->type() => new EvalError("type mismatch: {$left->type()->name} {$operator} {$right->type()->name}"),
            default => new EvalError("unknown operator: {$left->type()->name} {$operator} {$right->type()->name}"),
        };
    }

    private function evalIfExpression(IfExpression $if): EvalObject
    {
        $condition = $this->eval($if->condition);

        if ($this->isError($condition)) {
            return $condition;
        }

        return match (true) {
            $condition != $this->singletons[false] && $condition != $this->singletons[null] => $this->eval($if->consequence),
            !is_null($if->alternative) => $this->eval($if->alternative),
            default => $this->singletons[null],
        };
    }

    private function evalIdentifier(Identifier $identifier): EvalObject
    {
        $value = $this->environment->get($identifier->value);

        if (is_null($value)) {
            return new EvalError("identifier not found: {$identifier->value}");
        }

        return $value;
    }

    /**
     * @param Expression[] $expressions
     * @return EvalObject[]
     */
    private function evalExpressions(array $expressions): array
    {
        $result = [];

        foreach ($expressions as $expression) {
            $evaluated = $this->eval($expression);

            if ($this->isError($evaluated)) {
                return [$evaluated];
            }

            $result[] = $evaluated;
        }

        return $result;
    }

    /**
     * @param EvalObject[] $arguments
     */
    private function applyFunction(EvalObject $function, array $arguments): EvalObject
    {
        if (!$function instanceof EvalFunction) {
            return new EvalError("not a function: {$function->type()}");
        }

        $oldEnv = $this->environment;
        $this->environment = $this->extendFunctionEnv($function, $arguments);
        $evaluated = $this->eval($function->body);
        $this->environment = $oldEnv;
        return $this->unwrapReturnValue($evaluated);
    }

    /**
     * @param EvalObject[] $arguments
     */
    private function extendFunctionEnv(EvalFunction $function, array $arguments): Environment
    {
        $environment = Environment::closed($this->environment);

        foreach ($function->parameters as $i => $parameter) {
            $environment->set($parameter->value, $arguments[$i]);
        }

        return $environment;
    }

    private function unwrapReturnValue(EvalObject $evalObject): EvalObject
    {
        if ($evalObject instanceof EvalReturn) {
            return $evalObject->value;
        }

        return $evalObject;
    }
}
