<?php

namespace Monkey\Code;

use Exception;
use Monkey\Compiler\Instructions;

enum Code: int
{
    case CONSTANT = 1;
    case ADD = 2;
    case POP = 3;
    case SUB = 4;
    case MUL = 5;
    case DIV = 6;
    case TRUE = 7;
    case FALSE = 8;
    case EQUAL = 9;
    case NOT_EQUAL = 10;
    case GREATER_THAN = 11;
    case MINUS = 12;
    case BANG = 13;
    case JUMP_NOT_TRUTHY = 14;
    case JUMP = 15;
    case NULL = 16;
    case GET_GLOBAL = 17;
    case SET_GLOBAL = 18;
    case ARRAY = 19;
    case HASH = 20;
    case INDEX = 21;
    case CALL = 22;
    case RETURN_VALUE = 23;
    case RETURN = 24;
    case GET_LOCAL = 25;
    case SET_LOCAL = 26;
    case GET_BUILTIN = 27;
    case CLOSURE = 28;
    case GET_FREE = 29;
    case CURRENT_CLOSURE = 30;

    public function definition(): Definition
    {
        return match ($this) {
            self::CONSTANT,
            self::JUMP_NOT_TRUTHY,
            self::JUMP,
            self::GET_GLOBAL,
            self::SET_GLOBAL,
            self::ARRAY,
            self::HASH => new Definition($this->name, [2]),
            self::ADD,
            self::POP,
            self::SUB,
            self::MUL,
            self::DIV,
            self::TRUE,
            self::FALSE,
            self::EQUAL,
            self::NOT_EQUAL,
            self::GREATER_THAN,
            self::MINUS,
            self::BANG,
            self::NULL,
            self::INDEX,
            self::RETURN_VALUE,
            self::RETURN,
            self::CURRENT_CLOSURE => new Definition($this->name, []),
            self::CALL,
            self::GET_LOCAL,
            self::SET_LOCAL,
            self::GET_BUILTIN,
            self::GET_FREE => new Definition($this->name, [1]),
            self::CLOSURE => new Definition($this->name, [2, 1]),
        };
    }

    public function make(int ...$operands): Instructions
    {
        $definition = $this->definition();

        $instructionLength = 1;
        foreach ($definition->operandWidths as $width) {
            $instructionLength += $width;
        }

        $instruction = new Instructions([]);
        $instruction[] = $this->value;

        foreach ($operands as $i => $operand) {
            $width = $definition->operandWidths[$i];

            match ($width) {
                2 => call_user_func(function () use ($operand, $instruction) {
                    $instruction[] = $operand >> 0x8;
                    $instruction[] = $operand & 0xFF;
                }),
                1 => call_user_func(function () use ($operand, $instruction) {
                    $instruction[] = $operand;
                }),
                default => null,
            };
        }

        return $instruction;
    }

    /**
     * @return int[]
     */
    public function readOperands(Instructions $instructions, ?int &$offset): array
    {
        $operands = [];
        $offset = 0;

        foreach ($this->definition()->operandWidths as $i => $width) {
            match ($width) {
                2 => call_user_func(function () use (&$operands, $instructions, $offset, $i) {
                    $operands[$i] = ($instructions[$offset] << 0x8) | $instructions[$offset + 1];
                }),
                1 => call_user_func(function () use (&$operands, $instructions, $offset, $i) {
                    $operands[$i] = $instructions[$offset] ?? throw new Exception("instructions too short: got {$instructions->count()}, wanted {$offset}");
                }),
                default => null,
            };

            $offset += $width;
        }

        return $operands;
    }

    public function readInt(Instructions $instructions, int $offset): int
    {
        return ($instructions[$offset] << 0x8) | $instructions[$offset + 1];
    }
}
