<?php

namespace Monkey\Evaluator;

use Error;
use Monkey\Ast\Expression\ArrayLiteral;
use Monkey\Ast\Expression\Boolean;
use Monkey\Ast\Expression\CallExpression;
use Monkey\Ast\Expression\Expression;
use Monkey\Ast\Expression\FunctionLiteral;
use Monkey\Ast\Expression\HashLiteral;
use Monkey\Ast\Expression\Identifier;
use Monkey\Ast\Expression\IfExpression;
use Monkey\Ast\Expression\IndexExpression;
use Monkey\Ast\Expression\InfixExpression;
use Monkey\Ast\Expression\IntegerLiteral;
use Monkey\Ast\Expression\MacroLiteral;
use Monkey\Ast\Expression\PrefixExpression;
use Monkey\Ast\Expression\StringLiteral;
use Monkey\Ast\Node;
use Monkey\Ast\Program;
use Monkey\Ast\Statement\BlockStatement;
use Monkey\Ast\Statement\ExpressionStatement;
use Monkey\Ast\Statement\LetStatement;
use Monkey\Ast\Statement\ReturnStatement;
use Monkey\Evaluator\Object\EvalArray;
use Monkey\Evaluator\Object\EvalBoolean;
use Monkey\Evaluator\Object\EvalBuiltin;
use Monkey\Evaluator\Object\EvalError;
use Monkey\Evaluator\Object\EvalFunction;
use Monkey\Evaluator\Object\EvalHash;
use Monkey\Evaluator\Object\EvalInteger;
use Monkey\Evaluator\Object\EvalMacro;
use Monkey\Evaluator\Object\EvalNull;
use Monkey\Evaluator\Object\EvalObject;
use Monkey\Evaluator\Object\EvalQuote;
use Monkey\Evaluator\Object\EvalReturn;
use Monkey\Evaluator\Object\EvalString;
use Monkey\Evaluator\Object\EvalType;
use Monkey\Evaluator\Object\HashKey;
use Monkey\Token\Token;
use Monkey\Token\Type;

class Evaluator
{
    public static function new(Environment $environment): self
    {
        $singletons = [
            true => new EvalBoolean(true),
            false => new EvalBoolean(false),
            null => new EvalNull(),
        ];

        $builtins = [
            'len' => new EvalBuiltin(function (...$args) {
                if (count($args) != 1) {
                    return new EvalError('wrong number of arguments: got ' . count($args) . ', wanted 1');
                }

                return match ($args[0]::class) {
                    EvalString::class => new EvalInteger(strlen($args[0]->value)),
                    EvalArray::class => new EvalInteger(count($args[0]->elements)),
                    default => new EvalError("argument to `len` not supported, got {$args[0]->type()->name}"),
                };
            }),
            'first' => new EvalBuiltin(function (...$args) use ($singletons) {
                if (count($args) != 1) {
                    return new EvalError('wrong number of arguments: got ' . count($args) . ', wanted 1');
                }

                if ($args[0]->type() != EvalType::ARRAY) {
                    return new EvalError("argument to `first` must be ARRAY: got {$args[0]->type()->name}");
                }

                if (count($args[0]->elements) > 0) {
                    return $args[0]->elements[0];
                }

                return $singletons[null];
            }),
            'last' => new EvalBuiltin(function (...$args) use ($singletons) {
                if (count($args) != 1) {
                    return new EvalError('wrong number of arguments: got ' . count($args) . ', wanted 1');
                }

                if ($args[0]->type() != EvalType::ARRAY) {
                    return new EvalError("argument to `first` must be ARRAY: got {$args[0]->type()->name}");
                }

                if (count($args[0]->elements) > 0) {
                    return $args[0]->elements[count($args[0]->elements) - 1];
                }

                return $singletons[null];
            }),
            'rest' => new EvalBuiltin(function (...$args) use ($singletons) {
                if (count($args) != 1) {
                    return new EvalError('wrong number of arguments: got ' . count($args) . ', wanted 1');
                }

                if ($args[0]->type() != EvalType::ARRAY) {
                    return new EvalError("argument to `first` must be ARRAY: got {$args[0]->type()->name}");
                }

                if (count($args[0]->elements) > 0) {
                    return new EvalArray(array_splice($args[0]->elements, 1));
                }

                return $singletons[null];
            }),
            'push' => new EvalBuiltin(function (...$args) {
                if (count($args) != 2) {
                    return new EvalError('wrong number of arguments: got ' . count($args) . ', wanted 2');
                }

                if ($args[0]->type() != EvalType::ARRAY) {
                    return new EvalError("argument to `first` must be ARRAY: got {$args[0]->type()->name}");
                }

                return new EvalArray([...$args[0]->elements, $args[1]]);
            }),
            'puts' => new EvalBuiltin(function (...$args) use ($singletons) {
                foreach ($args as $arg) {
                    fwrite(STDOUT, "{$arg->inspect()}\n");
                }

                return $singletons[null];
            }),
        ];

        return new self($environment, $singletons, $builtins);
    }

    public static function defineMacros(Program $program): Environment
    {
        $env = Environment::new();
        $definitions = [];

        foreach ($program->statements as $i => $statement) {
            if (!$statement instanceof LetStatement) {
                continue;
            }

            if (!$statement->value instanceof MacroLiteral) {
                continue;
            }

            $env->set($statement->name->value, new EvalMacro(
                $statement->value->parameters,
                $statement->value->body,
                $env,
            ));

            $definitions[] = $i;
        }

        for ($i = count($definitions) - 1; $i >= 0; $i--) {
            array_splice($program->statements, $i, 1);
        }

        return $env;
    }

    public static function expandMacros(Program $program, Environment $environment): Node
    {
        return $program->modify(function (Node $node) use ($environment): Node {
            if (!$node instanceof CallExpression) {
                return $node;
            }

            if (!$node->function instanceof Identifier) {
                return $node;
            }

            $macro = $environment->get($node->function->value);
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

            $extended = Environment::closed($macro->environment);

            foreach ($macro->parameters as $i => $parameter) {
                $extended->set($parameter->value, $arguments[$i]);
            }

            $evaluated = self::new($extended)->eval($macro->body);

            if (!$evaluated instanceof EvalQuote) {
                throw new Error('we only support returning AST-nodes from macros');
            }

            return $evaluated->node;
        });
    }

    private function __construct(
        private Environment $environment,
        private array $singletons,
        private array $builtins,
    ) {
    }

    public function eval(Node $node): ?EvalObject
    {
        return match ($node::class) {
            Program::class => $this->evalProgram($node),
            ExpressionStatement::class => $this->eval($node->value),
            IntegerLiteral::class => new EvalInteger($node->value),
            Boolean::class => $this->boolean($node->value),
            StringLiteral::class => new EvalString($node->value),
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
                if ($node->function->tokenLiteral() == 'quote') {
                    return new EvalQuote($this->evalUnquoteCalls($node->arguments[0]));
                }

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
            ArrayLiteral::class => call_user_func(function () use ($node) {
                $elements = $this->evalExpressions($node->elements);

                if (count($elements) == 1 && $this->isError($elements[0])) {
                    return $elements[0];
                }

                return new EvalArray($elements);
            }),
            IndexExpression::class => call_user_func(function () use ($node) {
                $left = $this->eval($node->left);

                if ($this->isError($left)) {
                    return $left;
                }

                $index = $this->eval($node->index);

                if ($this->isError($index)) {
                    return $index;
                }

                return $this->evalIndexExpression($left, $index);
            }),
            HashLiteral::class => call_user_func(function () use ($node) {
                $pairs = [];

                foreach ($node->pairs as $pair) {
                    $key = $this->eval($pair[0]);

                    if ($this->isError($key)) {
                        return $key;
                    }


                    if (!$key instanceof HashKey) {
                        return new EvalError("unusable as hash key: {$key->type()->name}");
                    }

                    $value = $this->eval($pair[1]);

                    if ($this->isError($value)) {
                        return $value;
                    }

                    $pairs[$key->hashKey()] = [$key, $value];
                }

                return new EvalHash($pairs);
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
            $left->type() == EvalType::STRING && $right->type() == EvalType::STRING => match ($operator) {
                '+' => new EvalString($left->value . $right->value),
                '<' => $this->boolean($left->value < $right->value),
                '>' => $this->boolean($left->value > $right->value),
                '==' => $this->boolean($left->value == $right->value),
                '!=' => $this->boolean($left->value != $right->value),
                default => new EvalError("unknown operator: {$left->type()->name} {$operator} {$right->type()->name}"),
            },
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

        if ($value) {
            return $value;
        }

        if (!empty($this->builtins[$identifier->value])) {
            return $this->builtins[$identifier->value];
        }

        return new EvalError("identifier not found: {$identifier->value}");
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
        return match ($function::class) {
            EvalFunction::class => call_user_func(function () use ($function, $arguments) {
                $oldEnv = $this->environment;
                $this->environment = $this->extendFunctionEnv($function, $arguments);
                $evaluated = $this->eval($function->body);
                $this->environment = $oldEnv;
                return $this->unwrapReturnValue($evaluated);
            }),
            EvalBuiltin::class => ($function->builtinFunction)(...$arguments),
            default => new EvalError("not a function: {$function->type()}"),
        };
    }

    /**
     * @param EvalObject[] $arguments
     */
    private function extendFunctionEnv(EvalFunction $function, array $arguments): Environment
    {
        $environment = Environment::closed($function->environment);

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

    private function evalIndexExpression(EvalObject $left, EvalObject $index): ?EvalObject
    {
        return match (true) {
            $left->type() == EvalType::ARRAY && $index->type() == EvalType::INTEGER => call_user_func(function () use ($left, $index) {
                $i = $index->value;
                $max = count($left->elements) - 1;

                if ($i < 0 || $i > $max) {
                    return $this->singletons[null];
                }

                return $left->elements[$i];
            }),
            $left->type() == EvalType::HASH => call_user_func(function () use ($left, $index) {
                if (!$index instanceof HashKey) {
                    return new EvalError("unusable as hash key: {$index->type()->name}");
                }

                if (empty($left->pairs[$index->hashKey()])) {
                    return $this->singletons[null];
                }

                return $left->pairs[$index->hashKey()][1];
            }),
            default => new EvalError("index operator not supported: {$left->type()->name}"),
        };
    }

    private function evalUnquoteCalls(Node $node): Node
    {
        return $node->modify(function (Node $node): Node {
            if (!$this->isUnquotedCall($node)) {
                return $node;
            }

            if (!$node instanceof CallExpression) {
                return $node;
            }

            if (count($node->arguments) != 1) {
                return $node;
            }

            $unquoted = $this->eval($node->arguments[0]);
            return $this->convertObjectToAstNode($unquoted);
        });
    }

    private function isUnquotedCall(Node $node): bool
    {
        if ($node instanceof CallExpression) {
            return $node->function->tokenLiteral() == 'unquote';
        }

        return false;
    }

    private function convertObjectToAstNode(EvalObject $evalObject): ?Node
    {
        return match ($evalObject::class) {
            EvalInteger::class => new IntegerLiteral(new Token(Type::INT, "{$evalObject->value}"), $evalObject->value, $evalObject->value),
            EvalBoolean::class => new Boolean($evalObject->value ? new Token(Type::TRUE, 'true') : new Token(Type::FALSE, 'false'), $evalObject->value),
            EvalQuote::class => $evalObject->node,
            default => null,
        };
    }
}
