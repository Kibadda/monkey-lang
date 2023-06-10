<?php

namespace Monkey\Compiler;

class SymbolTable
{
    /**
     * @param array<string, Symbol> $store
     * @param Symbol[] $free
     */
    public function __construct(
        public ?self $outer = null,
        public array $store = [],
        public int $numDefinitions = 0,
        public array $free = [],
    ) {
    }

    public function define(string $name): Symbol
    {
        $symbol = new Symbol($name, is_null($this->outer) ? Scope::GLOBAL : Scope::LOCAL, $this->numDefinitions);
        $this->store[$name] = $symbol;
        $this->numDefinitions++;
        return $symbol;
    }

    public function defineBuiltin(int $index, string $name): Symbol
    {
        $symbol = new Symbol($name, Scope::BUILTIN, $index);
        $this->store[$name] = $symbol;
        return $symbol;
    }

    public function defineFree(Symbol $original): Symbol
    {
        $this->free[] = $original;

        $symbol = new Symbol($original->name, Scope::FREE, count($this->free) - 1);
        $this->store[$original->name] = $symbol;

        return $symbol;
    }

    public function defineFunction(string $name): Symbol
    {
        $symbol = new Symbol($name, Scope::FUNCTION, 0);
        $this->store[$name] = $symbol;
        return $symbol;
    }

    public function resolve(string $name): ?Symbol
    {
        if (!empty($this->store[$name])) {
            return $this->store[$name];
        }

        if ($this->outer == null) {
            return null;
        }

        $result = $this->outer->resolve($name);

        if (empty($result)) {
            return $result;
        }

        if (in_array($result->scope, [Scope::GLOBAL, Scope::BUILTIN])) {
            return $result;
        }

        return $this->defineFree($result);
    }
}
