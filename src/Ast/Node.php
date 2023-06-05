<?php

namespace Monkey\Ast;

interface Node
{
    public function tokenLiteral(): string;
    public function string(): string;
    public function modify(callable $modifier): self;
}
