<?php

namespace Monkey\Compiler;

use Exception;
use Monkey\Ast\Expression\ArrayLiteral;
use Monkey\Ast\Expression\Boolean;
use Monkey\Ast\Expression\CallExpression;
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
use Monkey\Code\Code;
use Monkey\Object\Builtins;
use Monkey\Object\EvalCompiledFunction;
use Monkey\Object\EvalInteger;
use Monkey\Object\EvalObject;
use Monkey\Object\EvalString;

class Compiler
{
    /**
     * @param EvalObject[] $constants
     * @param CompilationScope[] $scopes
     */
    public function __construct(
        public array $constants = [],
        public SymbolTable $symbolTable = new SymbolTable(),
        public array $scopes = [new CompilationScope()],
        public int $scopeIndex = 0,
    ) {
        $builtins = new Builtins();
        foreach (array_keys($builtins->builtins) as $i => $name) {
            $this->symbolTable->defineBuiltin($i, $name);
        }
    }

    public function compile(Node $node): void
    {
        if ($node instanceof Program) {
            foreach ($node->statements as $statement) {
                $this->compile($statement);
            }
        } else if ($node instanceof ExpressionStatement) {
            $this->compile($node->value);
            $this->emit(Code::POP);
        } else if ($node instanceof InfixExpression) {
            if ($node->operator == '<') {
                $this->compile($node->right);
                $this->compile($node->left);

                $this->emit(Code::GREATER_THAN);
                return;
            }

            $this->compile($node->left);
            $this->compile($node->right);

            match ($node->operator) {
                '+' => $this->emit(Code::ADD),
                '-' => $this->emit(Code::SUB),
                '*' => $this->emit(Code::MUL),
                '/' => $this->emit(Code::DIV),
                '>' => $this->emit(Code::GREATER_THAN),
                '==' => $this->emit(Code::EQUAL),
                '!=' => $this->emit(Code::NOT_EQUAL),
                default => throw new Exception("unknown operator {$node->operator}"),
            };
        } else if ($node instanceof PrefixExpression) {
            $this->compile($node->right);

            match ($node->operator) {
                '!' => $this->emit(Code::BANG),
                '-' => $this->emit(Code::MINUS),
                default => throw new Exception("unknown operator {$node->operator}"),
            };
        } else if ($node instanceof IfExpression) {
            $this->compile($node->condition);

            $jumpNotTruthyPosition = $this->emit(Code::JUMP_NOT_TRUTHY, 9999);

            $this->compile($node->consequence);

            if ($this->lastInstructionIs(Code::POP)) {
                $this->removeLastPop();
            }

            $jumpPosition = $this->emit(Code::JUMP, 9999);

            $afterConsequencePosition = $this->currentInstructions()->count();
            $this->changeOperand($jumpNotTruthyPosition, $afterConsequencePosition);

            if ($node->alternative == null) {
                $this->emit(Code::NULL);
            } else {
                $this->compile($node->alternative);

                if ($this->lastInstructionIs(Code::POP)) {
                    $this->removeLastPop();
                }
            }

            $afterAlternativePosition = $this->currentInstructions()->count();
            $this->changeOperand($jumpPosition, $afterAlternativePosition);
        } else if ($node instanceof BlockStatement) {
            foreach ($node->statements as $statement) {
                $this->compile($statement);
            }
        } else if ($node instanceof LetStatement) {
            $symbol = $this->symbolTable->define($node->name->value);
            $this->compile($node->value);

            $this->emit($symbol->scope == Scope::GLOBAL ? Code::SET_GLOBAL : Code::SET_LOCAL, $symbol->index);
        } else if ($node instanceof Identifier) {
            $symbol = $this->symbolTable->resolve($node->value);

            if ($symbol == null) {
                throw new Exception("undefined variable {$node->value}");
            }

            $this->loadSymbol($symbol);
        } else if ($node instanceof IndexExpression) {
            $this->compile($node->left);
            $this->compile($node->index);

            $this->emit(Code::INDEX);
        } else if ($node instanceof CallExpression) {
            $this->compile($node->function);

            foreach ($node->arguments as $argument) {
                $this->compile($argument);
            }

            $this->emit(Code::CALL, count($node->arguments));
        } else if ($node instanceof IntegerLiteral) {
            $integer = new EvalInteger($node->value);
            $this->emit(Code::CONSTANT, $this->addConstant($integer));
        } else if ($node instanceof Boolean) {
            $this->emit($node->value ? Code::TRUE : Code::FALSE);
        } else if ($node instanceof StringLiteral) {
            $string = new EvalString($node->value);
            $this->emit(Code::CONSTANT, $this->addConstant($string));
        } else if ($node instanceof ArrayLiteral) {
            foreach ($node->elements as $element) {
                $this->compile($element);
            }

            $this->emit(Code::ARRAY, count($node->elements));
        } else if ($node instanceof HashLiteral) {
            foreach ($node->pairs as $pair) {
                $this->compile($pair[0]);
                $this->compile($pair[1]);
            }

            $this->emit(Code::HASH, count($node->pairs) * 2);
        } else if ($node instanceof FunctionLiteral) {
            $this->enterScope();

            if (!empty($node->name)) {
                $this->symbolTable->defineFunction($node->name);
            }

            foreach ($node->parameters as $parameter) {
                $this->symbolTable->define($parameter->value);
            }

            $this->compile($node->body);

            if ($this->lastInstructionIs(Code::POP)) {
                $this->replaceLastPopWithReturn();
            }
            if (!$this->lastInstructionIs(Code::RETURN_VALUE)) {
                $this->emit(Code::RETURN);
            }

            $freeSymbols = $this->symbolTable->free;
            $numLocals = $this->symbolTable->numDefinitions;
            $instructions = $this->leaveScope();

            foreach ($freeSymbols as $symbol) {
                $this->loadSymbol($symbol);
            }

            $compiledFunction = new EvalCompiledFunction($instructions, $numLocals, count($node->parameters));
            $this->emit(Code::CLOSURE, $this->addConstant($compiledFunction), count($freeSymbols));
        } else if ($node instanceof ReturnStatement) {
            $this->compile($node->value);

            $this->emit(Code::RETURN_VALUE);
        } else if ($node instanceof MatchLiteral) {
            $endJumps = [];
            foreach ($node->branches as $branch) {
                $this->compile($node->subject);
                $this->compile($branch->condition);
                $this->emit(Code::EQUAL);
                $jumpNotTruthyPosition = $this->emit(Code::JUMP_NOT_TRUTHY, 9999);
                $this->compile($branch->consequence);
                $endJumps[] = $this->emit(Code::JUMP, 9999);

                $afterConsequencePosition = $this->currentInstructions()->count();
                $this->changeOperand($jumpNotTruthyPosition, $afterConsequencePosition);
            }

            $this->emit(Code::NULL);

            $afterBranchesPosition = $this->currentInstructions()->count();
            foreach ($endJumps as $jump) {
                $this->changeOperand($jump, $afterBranchesPosition);
            }
        }
    }

    public function addConstant(EvalObject $evalObject): int
    {
        $this->constants[] = $evalObject;

        return count($this->constants) - 1;
    }

    public function emit(Code $code, ...$operands): int
    {
        $position = $this->addInstruction($code->make(...$operands));
        $this->setLastIntruction($code, $position);

        return $position;
    }

    public function addInstruction(Instructions $instructions): int
    {
        $posNewInstruction = $this->currentInstructions()->count();
        $this->scopes[$this->scopeIndex]->instructions->merge($instructions);
        return $posNewInstruction;
    }

    public function setLastIntruction(Code $code, int $position): void
    {
        $previous = $this->scopes[$this->scopeIndex]->lastInstruction;
        $last = new EmittedInstruction($code, $position);

        $this->scopes[$this->scopeIndex]->previousInstruction = $previous;
        $this->scopes[$this->scopeIndex]->lastInstruction = $last;
    }

    public function lastInstructionIs(Code $code): bool
    {
        return $this->currentInstructions()->count() != 0 && $this->scopes[$this->scopeIndex]->lastInstruction->code == $code;
    }

    public function removeLastPop(): void
    {
        $this->scopes[$this->scopeIndex]->instructions = $this->currentInstructions()->slice(0, $this->scopes[$this->scopeIndex]->lastInstruction->position);
        $this->scopes[$this->scopeIndex]->lastInstruction = $this->scopes[$this->scopeIndex]->previousInstruction;
    }

    public function replaceInstruction(int $position, Instructions $newInstruction): void
    {
        for ($i = 0; $i < $newInstruction->count(); $i++) {
            $this->currentInstructions()[$position + $i] = $newInstruction[$i];
        }
    }

    public function changeOperand(int $position, int $operand): void
    {
        $code = Code::tryFrom($this->currentInstructions()[$position]);
        $newInstruction = $code->make($operand);

        $this->replaceInstruction($position, $newInstruction);
    }

    public function currentInstructions(): Instructions
    {
        return $this->scopes[$this->scopeIndex]->instructions;
    }

    public function enterScope(): void
    {
        $this->scopeIndex++;
        $this->scopes[$this->scopeIndex] = new CompilationScope();

        $this->symbolTable = new SymbolTable($this->symbolTable);
    }

    public function leaveScope(): Instructions
    {
        $instructions = $this->currentInstructions();

        unset($this->scopes[$this->scopeIndex]);
        $this->scopeIndex--;

        $this->symbolTable = $this->symbolTable->outer;

        return $instructions;
    }

    public function replaceLastPopWithReturn(): void
    {
        $lastPos = $this->scopes[$this->scopeIndex]->lastInstruction->position;
        $this->replaceInstruction($lastPos, Code::RETURN_VALUE->make());
        $this->scopes[$this->scopeIndex]->lastInstruction->code = Code::RETURN_VALUE;
    }

    public function loadSymbol(Symbol $symbol): void
    {
        match ($symbol->scope) {
            Scope::GLOBAL => $this->emit(Code::GET_GLOBAL, $symbol->index),
            Scope::LOCAL => $this->emit(Code::GET_LOCAL, $symbol->index),
            Scope::BUILTIN => $this->emit(Code::GET_BUILTIN, $symbol->index),
            Scope::FREE => $this->emit(Code::GET_FREE, $symbol->index),
            Scope::FUNCTION => $this->emit(Code::CURRENT_CLOSURE),
        };
    }
}
