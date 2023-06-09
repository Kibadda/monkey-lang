<?php

namespace Monkey\Compiler;

class SymbolTable
{
    /**
     * @param array<string, Symbol> $store
     */
    public function __construct(
        public ?self $outer = null,
        public array $store = [],
        public int $numDefinitions = 0,
    ) {
    }

    public function define(string $name): Symbol
    {
        $symbol = new Symbol($name, is_null($this->outer) ? Scope::GLOBAL : Scope::LOCAL, $this->numDefinitions);
        $this->store[$name] = $symbol;
        $this->numDefinitions++;
        return $symbol;
    }

    public function resolve(string $name): ?Symbol
    {
        if (empty($this->store[$name])) {
            if ($this->outer != null) {
                return $this->outer->resolve($name);
            }

            return null;
        }

        return $this->store[$name];
    }
}
