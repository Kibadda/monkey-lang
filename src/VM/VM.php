<?php

namespace Monkey\VM;

use Exception;
use Monkey\Code\Code;
use Monkey\Compiler\Compiler;
use Monkey\Object\Builtins;
use Monkey\Object\EvalArray;
use Monkey\Object\EvalBoolean;
use Monkey\Object\EvalBuiltin;
use Monkey\Object\EvalCompiledFunction;
use Monkey\Object\EvalHash;
use Monkey\Object\EvalInteger;
use Monkey\Object\EvalNull;
use Monkey\Object\EvalObject;
use Monkey\Object\EvalString;
use Monkey\Object\EvalType;
use Monkey\Object\HashKey;

class VM
{
    private const STACK_SIZE = 2048;
    private const GLOBALS_SIZE = 65536;
    private const FRAMES_SIZE = 1024;

    /** @var EvalObject[] $constants */
    public array $constants;

    /** @var EvalObject[] $stack */
    public array $stack = [];
    public int $sp = 0;

    /** @var EvalObject[] $globals */
    public array $globals;

    /** @var Frame[] $frames */
    public array $frames;
    public int $framesIndex;

    public EvalBoolean $true;
    public EvalBoolean $false;
    public EvalNull $null;

    public Builtins $builtins;

    public function __construct(Compiler $compiler, array $globals = [])
    {
        $mainFunction = new EvalCompiledFunction($compiler->currentInstructions(), 0, 0);
        $mainFrame = new Frame($mainFunction, 0);

        $this->constants = $compiler->constants;
        $this->globals = $globals;
        $this->frames = [$mainFrame];
        $this->framesIndex = 1;

        $this->true = new EvalBoolean(true);
        $this->false = new EvalBoolean(false);
        $this->null = new EvalNull();

        $this->builtins = new Builtins();
    }

    public function stackTop(): EvalObject
    {
        return $this->sp == 0 ? null : $this->stack[$this->sp - 1];
    }

    public function run(): void
    {
        while ($this->currentFrame()->ip < $this->currentFrame()->instructions()->count() - 1) {
            $this->currentFrame()->ip++;
            $ip = $this->currentFrame()->ip;
            $instructions = $this->currentFrame()->instructions();
            $code = Code::tryFrom($instructions[$ip]);

            match ($code) {
                Code::CONSTANT => call_user_func(function () use ($ip, $code, $instructions) {
                    $constIndex = $code->readInt($instructions, $ip + 1);
                    $this->currentFrame()->ip += 2;

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
                Code::JUMP => call_user_func(function () use ($ip, $code, $instructions) {
                    $position = $code->readInt($instructions, $ip + 1);
                    $this->currentFrame()->ip = $position - 1;
                }),
                Code::JUMP_NOT_TRUTHY => call_user_func(function () use ($ip, $code, $instructions) {
                    $position = $code->readInt($instructions, $ip + 1);
                    $this->currentFrame()->ip += 2;

                    $condition = $this->pop();
                    if (($condition instanceof EvalBoolean && !$condition->value) || $condition instanceof EvalNull) {
                        $this->currentFrame()->ip = $position - 1;
                    }
                }),
                Code::NULL => $this->push($this->null),
                Code::SET_GLOBAL => call_user_func(function () use ($ip, $code, $instructions) {
                    $globalIndex = $code->readInt($instructions, $ip + 1);
                    $this->currentFrame()->ip += 2;

                    $this->globals[$globalIndex] = $this->pop();
                }),
                Code::GET_GLOBAL => call_user_func(function () use ($ip, $code, $instructions) {
                    $globalIndex = $code->readInt($instructions, $ip + 1);

                    if ($globalIndex >= self::GLOBALS_SIZE) {
                        throw new Exception('stack overflow');
                    }

                    $this->currentFrame()->ip += 2;

                    $this->push($this->globals[$globalIndex]);
                }),
                Code::ARRAY => call_user_func(function () use ($ip, $code, $instructions) {
                    $numElements = $code->readInt($instructions, $ip + 1);
                    $this->currentFrame()->ip += 2;

                    $elements = [];
                    for ($i = $this->sp - $numElements; $i < $this->sp; $i++) {
                        $elements[$i - $this->sp + $numElements] = $this->stack[$i];
                    }

                    $array = new EvalArray($elements);

                    $this->sp -= $numElements;

                    $this->push($array);
                }),
                Code::HASH => call_user_func(function () use ($ip, $code, $instructions) {
                    $numElements = $code->readInt($instructions, $ip + 1);
                    $this->currentFrame()->ip += 2;

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
                Code::CALL => call_user_func(function () use ($ip, $instructions) {
                    $numArguments = $instructions[$ip + 1];
                    $this->currentFrame()->ip += 1;

                    $function = $this->stack[$this->sp - 1 - $numArguments];

                    if ($function instanceof EvalCompiledFunction) {
                        if ($numArguments != $function->numParameters) {
                            throw new Exception("wrong number of arguments: want={$function->numParameters}, got={$numArguments}");
                        }

                        $frame = new Frame($function, $this->sp - $numArguments);
                        $this->pushFrame($frame);
                        $this->sp = $frame->basePointer + $function->numLocals;
                    } else if ($function instanceof EvalBuiltin) {
                        $arguments = array_slice($this->stack, $this->sp - $numArguments, $numArguments);

                        $result = ($function->builtinFunction)(...$arguments);
                        $this->sp = $this->sp - $numArguments - 1;

                        $this->push($result ?: $this->null);
                    } else {
                        throw new Exception('calling non-function');
                    }
                }),
                Code::RETURN_VALUE => call_user_func(function () {
                    $returnValue = $this->pop();

                    $frame = $this->popFrame();
                    $this->sp = $frame->basePointer - 1;

                    $this->push($returnValue);
                }),
                Code::RETURN => call_user_func(function () {
                    $frame = $this->popFrame();
                    $this->sp = $frame->basePointer - 1;

                    $this->push($this->null);
                }),
                Code::SET_LOCAL => call_user_func(function () use ($ip, $instructions) {
                    $localIndex = $instructions[$ip + 1];
                    $this->currentFrame()->ip += 1;

                    $frame = $this->currentFrame();

                    $this->stack[$frame->basePointer + $localIndex] = $this->pop();
                }),
                Code::GET_LOCAL => call_user_func(function () use ($ip, $instructions) {
                    $localIndex = $instructions[$ip + 1];
                    $this->currentFrame()->ip += 1;

                    $frame = $this->currentFrame();

                    $this->push($this->stack[$frame->basePointer + $localIndex]);
                }),
                Code::GET_BUILTIN => call_user_func(function () use ($ip, $instructions) {
                    $builtinIndex = $instructions[$ip + 1];
                    $this->currentFrame()->ip += 1;

                    $definition = array_values($this->builtins->builtins)[$builtinIndex];

                    $this->push($definition);
                }),
                default => null,
                // default => throw new Exception("code not found for int: {$instructions[$ip]}"),
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

    public function currentFrame(): Frame
    {
        return $this->frames[$this->framesIndex - 1];
    }

    public function pushFrame(Frame $frame): void
    {
        if ($this->framesIndex >= self::FRAMES_SIZE) {
            throw new Exception('stack overflow');
        }

        $this->frames[$this->framesIndex] = $frame;
        $this->framesIndex++;
    }

    public function popFrame(): Frame
    {
        $this->framesIndex--;
        return $this->frames[$this->framesIndex];
    }
}
