<?php

namespace Monkey\Object;

interface EvalObject
{
    public function type(): EvalType;
    public function inspect(): string;
}
