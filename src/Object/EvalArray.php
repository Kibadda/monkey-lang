<?php

namespace Monkey\Object;

class EvalArray implements EvalObject
{
    /**
     * @param EvalObject[] $elements
     */
    public function __construct(
        public array $elements,
    ) {
    }

    public function type(): EvalType
    {
        return EvalType::ARRAY;
    }

    public function inspect(): string
    {
        $elements = [];

        foreach ($this->elements as $element) {
            $elements[] = $element->inspect();
        }

        return "[" . implode(', ', $elements) . "]";
    }
}
