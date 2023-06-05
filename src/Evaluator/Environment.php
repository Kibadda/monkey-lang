<?php

namespace Monkey\Evaluator;

use Monkey\Evaluator\Object\EvalObject;

class Environment
{
    /**
     * @param array<string, EvalObject> $store
     */
    private function __construct(
        public array $store = [],
        public ?Environment $outer = null,
    ) {
    }

    public static function new(): self
    {
        return new self();
    }

    public static function closed(Environment $outer): self
    {
        return new self(outer: $outer);
    }

    public function get(string $name): ?EvalObject
    {
        if (!empty($this->store[$name])) {
            return $this->store[$name];
        }

        if (!is_null($this->outer) && !empty($this->outer->store[$name])) {
            return $this->outer->store[$name];
        }

        return null;
    }

    public function set(string $name, EvalObject $evalObject): EvalObject
    {
        $this->store[$name] = $evalObject;
        return $evalObject;
    }

    public function extend(Environment $environment)
    {
        foreach ($environment->store as $key => $evalObject) {
            $this->set($key, $evalObject);
        }
    }
}
