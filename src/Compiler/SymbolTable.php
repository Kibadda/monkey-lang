<?php

namespace Monkey\Compiler;

class SymbolTable
{
    /**
     * @param array<string, Symbol> $store
     */
    public function __construct(
        public array $store = [],
        public int $numDefinitions = 0,
    ) {
    }

    public function define(string $name): Symbol
    {
        $symbol = new Symbol($name, Scope::GLOBAL, $this->numDefinitions);
        $this->store[$name] = $symbol;
        $this->numDefinitions++;
        return $symbol;
    }

    public function resolve(string $name): ?Symbol
    {
        if (empty($this->store[$name])) {
            return null;
        }

        return $this->store[$name];
    }
}
