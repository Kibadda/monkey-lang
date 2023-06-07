<?php

namespace Monkey\Code;

use Monkey\Compiler\Instructions;

enum Code: int
{
    case CONSTANT = 1;

    public static function make(self $code, ...$operands): Instructions
    {
        $definition = $code->definition();

        $instructionLength = 1;
        foreach ($definition->operandWidths as $width) {
            $instructionLength += $width;
        }

        $instruction = new Instructions([]);
        $instruction[] = $code->value;

        foreach ($operands as $i => $operand) {
            $width = $definition->operandWidths[$i];

            match ($width) {
                2 => call_user_func(function () use ($operand, $instruction) {
                    $instruction[] = $operand >> 0x8;
                    $instruction[] = $operand & 0xFF;
                }),
            };
        }

        return $instruction;
    }

    public static function readOperands(Definition $definition, Instructions $instructions): array
    {
        $operands = [];
        $offset = 0;

        foreach ($definition->operandWidths as $i => $width) {
            match ($width) {
                2 => call_user_func(function () use (&$operands, $instructions, $offset, $i) {
                    $operands[$i] = ($instructions[$offset] << 0x8) | $instructions[$offset + 1];
                }),
            };

            $offset += $width;
        }

        return [$operands, $offset];
    }

    public function definition(): Definition
    {
        return match ($this) {
            self::CONSTANT => new Definition($this->name, [2]),
        };
    }
}
