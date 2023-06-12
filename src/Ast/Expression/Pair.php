<?php

namespace Monkey\Ast\Expression;

class Pair
{
    public function __construct(
        public Expression $key,
        public Expression $value,
    ) {
    }
}
