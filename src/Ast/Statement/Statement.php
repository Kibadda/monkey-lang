<?php

namespace Monkey\Ast\Statement;

use Monkey\Ast\Node;

interface Statement extends Node
{
    public function statementNode();
}
