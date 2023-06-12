<?php

namespace Monkey\Evaluator;

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
use Monkey\Ast\Expression\MatchLiteral;
use Monkey\Ast\Expression\PrefixExpression;
use Monkey\Ast\Expression\StringLiteral;
use Monkey\Ast\Node;
use Monkey\Ast\Program;
use Monkey\Ast\Statement\BlockStatement;
use Monkey\Ast\Statement\ExpressionStatement;
use Monkey\Ast\Statement\LetStatement;
use Monkey\Ast\Statement\ReturnStatement;
use Monkey\Object\Builtins;
use Monkey\Object\Environment;
use Monkey\Object\EvalArray;
use Monkey\Object\EvalBoolean;
use Monkey\Object\EvalBuiltin;
use Monkey\Object\EvalError;
use Monkey\Object\EvalFunction;
use Monkey\Object\EvalHash;
use Monkey\Object\EvalInteger;
use Monkey\Object\EvalNull;
use Monkey\Object\EvalObject;
use Monkey\Object\EvalQuote;
use Monkey\Object\EvalReturn;
use Monkey\Object\EvalString;
use Monkey\Object\HashKey;
use Monkey\Token\Token;
use Monkey\Token\Type;

class Evaluator
{
    public function __construct(
        private Environment $environment,
        private Builtins $builtins = new Builtins(),
        private EvalBoolean $true = new EvalBoolean(true),
        private EvalBoolean $false = new EvalBoolean(false),
        private EvalNull $null = new EvalNull(),
    ) {
    }

    public function eval(Node $node): EvalObject
    {
        if ($node instanceof Program) {
            $result = null;

            foreach ($node->statements as $statement) {
                $result = $this->eval($statement);
                if ($result instanceof EvalError) {
                    return $result;
                }

                if ($result instanceof EvalReturn) {
                    return $result->value;
                }
            }

            return $result ?: $this->null;
        } else if ($node instanceof ExpressionStatement) {
            return $this->eval($node->value);
        } else if ($node instanceof IntegerLiteral) {
            return new EvalInteger($node->value);
        } else if ($node instanceof Boolean) {
            return $node->value ? $this->true : $this->false;
        } else if ($node instanceof StringLiteral) {
            return new EvalString($node->value);
        } else if ($node instanceof PrefixExpression) {
            $right = $this->eval($node->right);
            if ($this->isError($right)) {
                return $right;
            }

            return match ($node->operator) {
                '!' => match ($right) {
                    $this->true => $this->false,
                    $this->false => $this->true,
                    $this->null => $this->true,
                    default => $this->false,
                },
                '-' => match (true) {
                    $right instanceof EvalInteger => new EvalInteger(-$right->value),
                    default => new EvalError("unknown operator: -{$right->type()->name}"),
                },
                default => new EvalError("unknown operator: {$node->operator}{$right->type()->name}"),
            };
        } else if ($node instanceof InfixExpression) {
            $left = $this->eval($node->left);
            if ($this->isError($left)) {
                return $left;
            }

            $right = $this->eval($node->right);
            if ($this->isError($right)) {
                return $right;
            }

            if ($left instanceof EvalInteger && $right instanceof EvalInteger) {
                return match ($node->operator) {
                    '+' => new EvalInteger($left->value + $right->value),
                    '-' => new EvalInteger($left->value - $right->value),
                    '/' => new EvalInteger($left->value / $right->value),
                    '*' => new EvalInteger($left->value * $right->value),
                    '<' => $left->value < $right->value ? $this->true : $this->false,
                    '>' => $left->value > $right->value ? $this->true : $this->false,
                    '==' => $left->value == $right->value ? $this->true : $this->false,
                    '!=' => $left->value != $right->value ? $this->true : $this->false,
                    default => $this->null,
                };
            } else if ($left instanceof EvalString && $right instanceof EvalString) {
                return match ($node->operator) {
                    '+' => new EvalString($left->value . $right->value),
                    '<' => $left->value < $right->value ? $this->true : $this->false,
                    '>' => $left->value > $right->value ? $this->true : $this->false,
                    '==' => $left->value == $right->value ? $this->true : $this->false,
                    '!=' => $left->value != $right->value ? $this->true : $this->false,
                    default => new EvalError("unknown operator: {$left->type()->name} {$node->operator} {$right->type()->name}"),
                };
            } else if ($left instanceof EvalBoolean && $right instanceof EvalBoolean) {
                return match ($node->operator) {
                    '==' => $left->value == $right->value ? $this->true : $this->false,
                    '!=' => $left->value != $right->value ? $this->true : $this->false,
                    default => new EvalError("unknown operator: {$left->type()->name} {$node->operator} {$right->type()->name}"),
                };
            } else if ($left->type() != $right->type()) {
                return new EvalError("type mismatch: {$left->type()->name} {$node->operator} {$right->type()->name}");
            } else {
                return new EvalError("unknown operator: {$left->type()->name} {$node->operator} {$right->type()->name}");
            }
        } else if ($node instanceof BlockStatement) {
            $result = null;

            foreach ($node->statements as $statement) {
                $result = $this->eval($statement);
                if ($this->isError($result)) {
                    return $result;
                }

                if ($result instanceof EvalReturn) {
                    return $result;
                }
            }

            return $result ?: $this->null;
        } else if ($node instanceof IfExpression) {
            $condition = $this->eval($node->condition);

            if ($this->isError($condition)) {
                return $condition;
            }

            return match (true) {
                $condition != $this->false && $condition != $this->null => $this->eval($node->consequence),
                !is_null($node->alternative) => $this->eval($node->alternative),
                default => $this->null,
            };
        } else if ($node instanceof ReturnStatement) {
            $value = $this->eval($node->value);
            if ($this->isError($value)) {
                return $value;
            }

            return new EvalReturn($value);
        } else if ($node instanceof LetStatement) {
            $value = $this->eval($node->value);
            if ($this->isError($value)) {
                return $value;
            }

            $this->environment->set($node->name->value, $value);
            return $this->null;
        } else if ($node instanceof Identifier) {
            $value = $this->environment->get($node->value);

            if ($value) {
                return $value;
            }

            $builtin = $this->builtins->getByName($node->value);
            if (!empty($builtin)) {
                return $builtin;
            }

            return new EvalError("identifier not found: {$node->value}");
        } else if ($node instanceof FunctionLiteral) {
            return new EvalFunction($node->parameters, $node->body, $this->environment);
        } else if ($node instanceof CallExpression) {
            if ($node->function->tokenLiteral() == 'quote') {
                return new EvalQuote($node->arguments[0]->modify(function (Node $node): Node {
                    if (!$node instanceof CallExpression || $node->function->tokenLiteral() != 'unquote') {
                        return $node;
                    }

                    if (count($node->arguments) != 1) {
                        return $node;
                    }

                    $unquoted = $this->eval($node->arguments[0]);
                    if ($unquoted instanceof EvalInteger) {
                        return new IntegerLiteral(new Token(Type::INT, "{$unquoted->value}"), $unquoted->value);
                    } else if ($unquoted instanceof EvalBoolean) {
                        return new Boolean($unquoted->value ? new Token(Type::TRUE, 'true') : new Token(Type::FALSE, 'false'), $unquoted->value);
                    } else if ($unquoted instanceof EvalQuote) {
                        return $unquoted->node;
                    } else {
                        return $node;
                    }
                }));
            }

            $function = $this->eval($node->function);
            if ($this->isError($function)) {
                return $function;
            }

            $arguments = $this->evalExpressions($node->arguments);
            if (count($arguments) == 1 && $this->isError($arguments[0])) {
                return $arguments[0];
            }

            if ($function instanceof EvalFunction) {
                $oldEnv = $this->environment;

                $this->environment = new Environment($function->environment);

                foreach ($function->parameters as $i => $parameter) {
                    $this->environment->set($parameter->value, $arguments[$i]);
                }

                $evaluated = $this->eval($function->body);
                $this->environment = $oldEnv;

                if ($evaluated instanceof EvalReturn) {
                    return $evaluated->value;
                }

                return $evaluated;
            } else if ($function instanceof EvalBuiltin) {
                return ($function->builtinFunction)(...$arguments) ?: $this->null;
            } else {
                return new EvalError("not a function: {$function->type()->name}");
            }
        } else if ($node instanceof ArrayLiteral) {
            $elements = $this->evalExpressions($node->elements);
            if (count($elements) == 1 && $this->isError($elements[0])) {
                return $elements[0];
            }

            return new EvalArray($elements);
        } else if ($node instanceof IndexExpression) {
            $left = $this->eval($node->left);
            if ($this->isError($left)) {
                return $left;
            }

            $index = $this->eval($node->index);
            if ($this->isError($index)) {
                return $index;
            }

            if ($left instanceof EvalArray && $index instanceof EvalInteger) {
                $i = $index->value;
                $max = count($left->elements) - 1;

                if ($i < 0 || $i > $max) {
                    return $this->null;
                }

                return $left->elements[$i];
            } else if ($left instanceof EvalHash) {
                if (!$index instanceof HashKey) {
                    return new EvalError("unusable as hash key: {$index->type()->name}");
                }

                if (empty($left->pairs[$index->hashKey()])) {
                    return $this->null;
                }

                return $left->pairs[$index->hashKey()];
            } else {
                return new EvalError("index operator not supported: {$left->type()->name}");
            }
        } else if ($node instanceof HashLiteral) {
            $pairs = [];

            foreach ($node->pairs as $pair) {
                $key = $this->eval($pair->key);
                if ($this->isError($key)) {
                    return $key;
                }
                if (!$key instanceof HashKey) {
                    return new EvalError("unusable as hash key: {$key->type()->name}");
                }

                $value = $this->eval($pair->value);
                if ($this->isError($value)) {
                    return $value;
                }

                $pairs[$key->hashKey()] = $value;
            }

            return new EvalHash($pairs);
        } else if ($node instanceof MatchLiteral) {
            $subject = $this->eval($node->subject);
            if ($this->isError($subject)) {
                return $subject;
            }

            foreach ($node->branches as $branch) {
                $condition = $this->eval($branch->condition);
                if ($this->isError($condition)) {
                    return $condition;
                }

                if ($subject == $condition) {
                    $consequence = $this->eval($branch->consequence);
                    return $consequence;
                }
            }

            if ($node->default != null) {
                $default = $this->eval($node->default);
                return $default;
            }

            return $this->null;
        } else {
            $class = $node::class;
            return new EvalError("unknown node type: {$class}");
        }
    }

    private function isError(?EvalObject $evalObject): bool
    {
        return $evalObject == null || $evalObject instanceof EvalError;
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
}
