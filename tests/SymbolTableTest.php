<?php

use Monkey\Compiler\Scope;
use Monkey\Compiler\Symbol;
use Monkey\Compiler\SymbolTable;

it('defines', function () {
    $expected = [
        'a' => new Symbol('a', Scope::GLOBAL, 0),
        'b' => new Symbol('b', Scope::GLOBAL, 1),
        'c' => new Symbol('c', Scope::LOCAL, 0),
        'd' => new Symbol('d', Scope::LOCAL, 1),
        'e' => new Symbol('e', Scope::LOCAL, 0),
        'f' => new Symbol('f', Scope::LOCAL, 1),
    ];

    $global = new SymbolTable();

    $a = $global->define('a');
    expect(print_r($a, true))->toBe(print_r($expected['a'], true));

    $b = $global->define('b');
    expect(print_r($b, true))->toBe(print_r($expected['b'], true));

    $firstLocal = new SymbolTable($global);

    $c = $firstLocal->define('c');
    expect(print_r($c, true))->toBe(print_r($expected['c'], true));

    $d = $firstLocal->define('d');
    expect(print_r($d, true))->toBe(print_r($expected['d'], true));

    $secondLocal = new SymbolTable($firstLocal);

    $e = $secondLocal->define('e');
    expect(print_r($e, true))->toBe(print_r($expected['e'], true));

    $f = $secondLocal->define('f');
    expect(print_r($f, true))->toBe(print_r($expected['f'], true));
});

it('resolves globals', function () {
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

it('resolves locals', function () {
    $global = new SymbolTable();
    $global->define('a');
    $global->define('b');

    $local = new SymbolTable($global);
    $local->define('c');
    $local->define('d');

    $expected = [
        new Symbol('a', Scope::GLOBAL, 0),
        new Symbol('b', Scope::GLOBAL, 1),
        new Symbol('c', Scope::LOCAL, 0),
        new Symbol('d', Scope::LOCAL, 1),
    ];

    foreach ($expected as $symbol) {
        $result = $local->resolve($symbol->name);
        expect($result)->not->toBeNull();

        expect(print_r($result, true))->toBe(print_r($symbol, true));
    }
});

it('resolves nested locals', function () {
    $global = new SymbolTable();
    $global->define('a');
    $global->define('b');

    $firstLocal = new SymbolTable($global);
    $firstLocal->define('c');
    $firstLocal->define('d');

    $secondLocal = new SymbolTable($firstLocal);
    $secondLocal->define('e');
    $secondLocal->define('f');

    $expected = [
        [
            $firstLocal,
            [
                new Symbol('a', Scope::GLOBAL, 0),
                new Symbol('b', Scope::GLOBAL, 1),
                new Symbol('c', Scope::LOCAL, 0),
                new Symbol('d', Scope::LOCAL, 1),
            ],
        ],
        [
            $secondLocal,
            [
                new Symbol('a', Scope::GLOBAL, 0),
                new Symbol('b', Scope::GLOBAL, 1),
                new Symbol('e', Scope::LOCAL, 0),
                new Symbol('f', Scope::LOCAL, 1),
            ],
        ],
    ];

    foreach ($expected as $exp) {
        foreach ($exp[1] as $symbol) {
            $result = $exp[0]->resolve($symbol->name);
            expect($result)->not->toBeNull();

            expect(print_r($result, true))->toBe(print_r($symbol, true));
        }
    }
});
