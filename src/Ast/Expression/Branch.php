<?php

namespace Monkey\Ast\Expression;

class Branch
{
    public function __construct(
        public Expression $condition,
        public Expression $consequence,
    ) {
    }
}
