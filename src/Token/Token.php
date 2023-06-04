<?php

namespace Monkey\Token;

class Token
{
    public function __construct(
        public Type $type,
        public string $literal,
    ) {
    }
}
