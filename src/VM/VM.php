<?php

namespace Monkey\VM;

use Exception;
use Monkey\Code\Code;
use Monkey\Compiler\Compiler;
use Monkey\Compiler\Instructions;
use Monkey\Evaluator\Object\EvalArray;
use Monkey\Evaluator\Object\EvalBoolean;
use Monkey\Evaluator\Object\EvalHash;
use Monkey\Evaluator\Object\EvalInteger;
use Monkey\Evaluator\Object\EvalNull;
use Monkey\Evaluator\Object\EvalObject;
use Monkey\Evaluator\Object\EvalString;
use Monkey\Evaluator\Object\EvalType;
use Monkey\Evaluator\Object\HashKey;

class VM
{
    private const STACK_SIZE = 2048;
    private const GLOBALS_SIZE = 65536;

    public static function new(Compiler $compiler): self
    {
        return new self($compiler->constants, $compiler->instructions);
    }

    public static function newWithGlobalsStore(Compiler $compiler, array $globals): self
    {
        return new self($compiler->constants, $compiler->instructions, globals: $globals);
    }

    /**
     * @param EvalObject[] $constants
     * @param EvalObject[] $stack
     * @param EvalObject[] $globals
     */
    public function __construct(
        public array $constants,
        public Instructions $instructions,
        public array $stack = [],
        public int $sp = 0,
        public EvalBoolean $true = new EvalBoolean(true),
        public EvalBoolean $false = new EvalBoolean(false),
        public EvalNull $null = new EvalNull(),
        public array $globals = [],
    ) {
    }

    public function stackTop(): EvalObject
    {
        return $this->sp == 0 ? null : $this->stack[$this->sp - 1];
    }

    public function run(): void
    {
        for ($ip = 0; $ip < $this->instructions->count(); $ip++) {
            $code = Code::tryFrom($this->instructions[$ip]);

            match ($code) {
                Code::CONSTANT => call_user_func(function () use (&$ip, $code) {
                    $constIndex = $code->readInt($this->instructions, $ip + 1);
                    $ip += 2;

                    $this->push($this->constants[$constIndex]);
                }),
                Code::ADD,
                Code::SUB,
                Code::MUL,
                Code::DIV => call_user_func(function () use ($code) {
                    $right = $this->pop();
                    $left = $this->pop();

                    match (true) {
                        $left->type() == EvalType::INTEGER && $right->type() == EvalType::INTEGER => $this->push(new EvalInteger(match ($code) {
                            Code::ADD => $left->value + $right->value,
                            Code::SUB => $left->value - $right->value,
                            Code::MUL => $left->value * $right->value,
                            Code::DIV => $left->value / $right->value,
                            default => throw new Exception("unknown integer operator: {$code->name}"),
                        })),
                        $left->type() == EvalType::STRING && $right->type() == EvalType::STRING => $this->push(new EvalString(match ($code) {
                            Code::ADD => $left->value . $right->value,
                            default => throw new Exception("unknown string operator: {$code->name}"),
                        })),
                        default => throw new Exception("unsupported types for binary operation: {$left->type()->name} {$right->type()->name}"),
                    };
                }),
                Code::POP => $this->pop(),
                Code::TRUE => $this->push($this->true),
                Code::FALSE => $this->push($this->false),
                Code::EQUAL,
                Code::NOT_EQUAL,
                Code::GREATER_THAN => call_user_func(function () use ($code) {
                    $right = $this->pop();
                    $left = $this->pop();

                    $result = match (true) {
                        $left->type() == EvalType::INTEGER && $right->type() == EvalType::INTEGER => match ($code) {
                            Code::EQUAL => $left->value == $right->value,
                            Code::NOT_EQUAL => $left->value != $right->value,
                            Code::GREATER_THAN => $left->value > $right->value,
                        },
                        default => match ($code) {
                            Code::EQUAL => $left == $right,
                            Code::NOT_EQUAL => $left != $right,
                            default => throw new Exception("unknown operator: {$code->name} ({$left->type()->name} {$right->type()->name})"),
                        },
                    };

                    $this->push($result ? $this->true : $this->false);
                }),
                Code::BANG => call_user_func(function () {
                    $operand = $this->pop();

                    if ($operand instanceof EvalBoolean) {
                        $this->push($operand->value ? $this->false : $this->true);
                    } else if ($operand instanceof EvalNull) {
                        $this->push($this->true);
                    } else {
                        $this->push($this->false);
                    }
                }),
                Code::MINUS => call_user_func(function () {
                    $operand = $this->pop();

                    if ($operand->type() != EvalType::INTEGER) {
                        throw new Exception("unsupported type for negation: {$operand->type()->name}");
                    }

                    $this->push(new EvalInteger(-$operand->value));
                }),
                Code::JUMP => call_user_func(function () use (&$ip, $code) {
                    $position = $code->readInt($this->instructions, $ip + 1);
                    $ip = $position - 1;
                }),
                Code::JUMP_NOT_TRUTHY => call_user_func(function () use (&$ip, $code) {
                    $position = $code->readInt($this->instructions, $ip + 1);
                    $ip += 2;

                    $condition = $this->pop();
                    if (($condition instanceof EvalBoolean && !$condition->value) || $condition instanceof EvalNull) {
                        $ip = $position - 1;
                    }
                }),
                Code::NULL => $this->push($this->null),
                Code::SET_GLOBAL => call_user_func(function () use (&$ip, $code) {
                    $globalIndex = $code->readInt($this->instructions, $ip + 1);
                    $ip += 2;

                    $this->globals[$globalIndex] = $this->pop();
                }),
                Code::GET_GLOBAL => call_user_func(function () use (&$ip, $code) {
                    $globalIndex = $code->readInt($this->instructions, $ip + 1);
                    $ip += 2;

                    $this->push($this->globals[$globalIndex]);
                }),
                Code::ARRAY => call_user_func(function () use (&$ip, $code) {
                    $numElements = $code->readInt($this->instructions, $ip + 1);
                    $ip += 2;

                    $elements = [];
                    for ($i = $this->sp - $numElements; $i < $this->sp; $i++) {
                        $elements[$i - $this->sp + $numElements] = $this->stack[$i];
                    }

                    $array = new EvalArray($elements);

                    $this->sp -= $numElements;

                    $this->push($array);
                }),
                Code::HASH => call_user_func(function () use (&$ip, $code) {
                    $numElements = $code->readInt($this->instructions, $ip + 1);
                    $ip += 2;

                    $pairs = [];
                    for ($i = $this->sp - $numElements; $i < $this->sp; $i += 2) {
                        $key = $this->stack[$i];
                        $value = $this->stack[$i + 1];

                        if (!$key instanceof HashKey) {
                            throw new Exception("unusable as hash key: {$key->type()->name}");
                        }

                        $pairs[$key->hashKey()] = $value;
                    }

                    $hash = new EvalHash($pairs);

                    $this->sp -= $numElements;

                    $this->push($hash);
                }),
                Code::INDEX => call_user_func(function () {
                    $index = $this->pop();
                    $left = $this->pop();

                    if ($left->type() == EvalType::ARRAY && $index->type() == EvalType::INTEGER) {
                        $i = $index->value;
                        $max = count($left->elements) - 1;

                        if ($i < 0 || $i > $max) {
                            $this->push($this->null);
                        } else {
                            $this->push($left->elements[$i]);
                        }
                    } else if ($left->type() == EvalType::HASH) {
                        if (!$index instanceof HashKey) {
                            throw new Exception("unusable as hash key: {$index->type()->name}");
                        }

                        if (!array_key_exists($index->hashKey(), $left->pairs)) {
                            $this->push($this->null);
                        } else {
                            $this->push($left->pairs[$index->hashKey()]);
                        }
                    } else {
                        throw new Exception("index operator not supported: {$left->type()->name}");
                    }
                }),
            };
        }
    }

    public function push(EvalObject $evalObject): void
    {
        if ($this->sp >= self::STACK_SIZE) {
            throw new Exception('stack overflow');
        }

        $this->stack[$this->sp] = $evalObject;
        $this->sp++;
    }

    public function pop(): EvalObject
    {
        $evalObject = $this->stack[$this->sp - 1];
        $this->sp--;
        return $evalObject;
    }

    public function lastPoppedStackElem(): EvalObject
    {
        return $this->stack[$this->sp];
    }
}
