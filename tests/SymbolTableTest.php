<?php

use Monkey\Compiler\Scope;
use Monkey\Compiler\Symbol;
use Monkey\Compiler\SymbolTable;

it('defines', function () {
    $expected = [
        'a' => new Symbol('a', Scope::GLOBAL, 0),
        'b' => new Symbol('b', Scope::GLOBAL, 1),
    ];

    $global = new SymbolTable();

    $a = $global->define('a');
    expect(print_r($a, true))->toBe(print_r($expected['a'], true));

    $b = $global->define('b');
    expect(print_r($b, true))->toBe(print_r($expected['b'], true));
});

it('resolves', function () {
    $global = new SymbolTable();
    $global->define('a');
    $global->define('b');

    $expected = [
        new Symbol('a', Scope::GLOBAL, 0),
        new Symbol('b', Scope::GLOBAL, 1),
    ];

    foreach ($expected as $symbol) {
        $result = $global->resolve($symbol->name);
        expect($result)->not->toBeNull();

        expect(print_r($result, true))->toBe(print_r($symbol, true));
    }
});
