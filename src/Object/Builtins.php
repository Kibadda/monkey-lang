<?php

namespace Monkey\Object;

use Closure;

class Builtins
{
    /**
     * @param array<string, EvalBuiltin> $builtins
     */
    public function __construct(
        public array $builtins = [],
    ) {
        $this->addBuiltin('len', function (...$args) {
            if (count($args) != 1) {
                return new EvalError('wrong number of arguments: got ' . count($args) . ', wanted 1');
            }

            return match ($args[0]::class) {
                EvalString::class => new EvalInteger(strlen($args[0]->value)),
                EvalArray::class => new EvalInteger(count($args[0]->elements)),
                default => new EvalError("argument to `len` not supported, got {$args[0]->type()->name}"),
            };
        });

        $this->addBuiltin('puts', function (...$args) {
            foreach ($args as $arg) {
                fwrite(STDOUT, "{$arg->inspect()}\n");
            }

            return null;
        });

        $this->addBuiltin('first', function (...$args) {
            if (count($args) != 1) {
                return new EvalError('wrong number of arguments: got ' . count($args) . ', wanted 1');
            }

            if ($args[0]->type() != EvalType::ARRAY) {
                return new EvalError("argument to `first` must be ARRAY: got {$args[0]->type()->name}");
            }

            if (count($args[0]->elements) > 0) {
                return $args[0]->elements[0];
            }

            return null;
        });

        $this->addBuiltin('last', function (...$args) {
            if (count($args) != 1) {
                return new EvalError('wrong number of arguments: got ' . count($args) . ', wanted 1');
            }

            if ($args[0]->type() != EvalType::ARRAY) {
                return new EvalError("argument to `first` must be ARRAY: got {$args[0]->type()->name}");
            }

            if (count($args[0]->elements) > 0) {
                return $args[0]->elements[count($args[0]->elements) - 1];
            }

            return null;
        });

        $this->addBuiltin('rest', function (...$args) {
            if (count($args) != 1) {
                return new EvalError('wrong number of arguments: got ' . count($args) . ', wanted 1');
            }

            if ($args[0]->type() != EvalType::ARRAY) {
                return new EvalError("argument to `first` must be ARRAY: got {$args[0]->type()->name}");
            }

            if (count($args[0]->elements) > 0) {
                return new EvalArray(array_splice($args[0]->elements, 1));
            }

            return null;
        });

        $this->addBuiltin('push', function (...$args) {
            if (count($args) != 2) {
                return new EvalError('wrong number of arguments: got ' . count($args) . ', wanted 2');
            }

            if ($args[0]->type() != EvalType::ARRAY) {
                return new EvalError("argument to `first` must be ARRAY: got {$args[0]->type()->name}");
            }

            return new EvalArray([...$args[0]->elements, $args[1]]);
        });
    }

    public function addBuiltin(string $name, Closure $function): void
    {
        $this->builtins[$name] = new EvalBuiltin($function);
    }

    public function getByName(string $name): ?EvalBuiltin
    {
        if (!empty($this->builtins[$name])) {
            return $this->builtins[$name];
        }

        return null;
    }
}
