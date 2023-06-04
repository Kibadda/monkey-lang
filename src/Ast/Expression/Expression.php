<?php

namespace Monkey\Ast\Expression;

use Monkey\Ast\Node;

interface Expression extends Node
{
    public function expressionNode();
}
