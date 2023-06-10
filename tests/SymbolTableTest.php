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

it('resolves builtins', function () {
    $global = new SymbolTable();
    $firstLocal = new SymbolTable($global);
    $secondLocal = new SymbolTable($firstLocal);

    $expected = [
        new Symbol('a', Scope::BUILTIN, 0),
        new Symbol('b', Scope::BUILTIN, 1),
        new Symbol('c', Scope::BUILTIN, 2),
        new Symbol('d', Scope::BUILTIN, 3),
    ];

    foreach ($expected as $i => $symbol) {
        $global->defineBuiltin($i, $symbol->name);
    }

    foreach ([$global, $firstLocal, $secondLocal] as $table) {
        foreach ($expected as $symbol) {
            $result = $table->resolve($symbol->name);
            expect($result)->not->toBeNull();
            expect(print_r($result, true))->toBe(print_r($symbol, true));
        }
    }
});

it('resolves free', function () {
    $global = new SymbolTable();
    $global->define('a');
    $global->define('b');

    $firstLocal = new SymbolTable($global);
    $firstLocal->define('c');
    $firstLocal->define('d');

    $secondLocal = new SymbolTable($firstLocal);
    $secondLocal->define('e');
    $secondLocal->define('f');

    $tests = [
        [
            $firstLocal,
            [
                new Symbol('a', Scope::GLOBAL, 0),
                new Symbol('b', Scope::GLOBAL, 1),
                new Symbol('c', Scope::LOCAL, 0),
                new Symbol('d', Scope::LOCAL, 1),
            ],
            [],
        ],
        [
            $secondLocal,
            [
                new Symbol('a', Scope::GLOBAL, 0),
                new Symbol('b', Scope::GLOBAL, 1),
                new Symbol('c', Scope::FREE, 0),
                new Symbol('d', Scope::FREE, 1),
                new Symbol('e', Scope::LOCAL, 0),
                new Symbol('f', Scope::LOCAL, 1),
            ],
            [
                new Symbol('c', Scope::LOCAL, 0),
                new Symbol('d', Scope::LOCAL, 1),
            ],
        ],
    ];

    foreach ($tests as $test) {
        foreach ($test[1] as $symbol) {
            $result = $test[0]->resolve($symbol->name);
            expect($result)->not->toBeNull();
            expect(print_r($result, true))->toBe(print_r($symbol, true));
        }

        expect($test[0]->free)->toHaveCount(count($test[2]));

        foreach ($test[2] as $i => $symbol) {
            $result = $test[0]->free[$i];
            expect($result)->not->toBeNull();
            expect(print_r($result, true))->toBe(print_r($symbol, true));
        }
    }
});

it('does not resolve unresolvable free', function () {
    $global = new SymbolTable();
    $global->define('a');

    $firstLocal = new SymbolTable($global);
    $firstLocal->define('c');

    $secondLocal = new SymbolTable($firstLocal);
    $secondLocal->define('e');
    $secondLocal->define('f');

    $expected = [
        new Symbol('a', Scope::GLOBAL, 0),
        new Symbol('c', Scope::FREE, 0),
        new Symbol('e', Scope::LOCAL, 0),
        new Symbol('f', Scope::LOCAL, 1),
    ];

    foreach ($expected as $symbol) {
        $result = $secondLocal->resolve($symbol->name);
        expect($result)->not->toBeNull();
        expect(print_r($result, true))->toBe(print_r($symbol, true));
    }

    $expectedUnresolvable = ['b', 'd'];

    foreach ($expectedUnresolvable as $name) {
        expect($secondLocal->resolve($name))->toBeNull();
    }
});

it('defines and resolves function names', function () {
    $global = new SymbolTable();
    $global->defineFunction('a');

    $expected = new Symbol('a', Scope::FUNCTION, 0);

    $result = $global->resolve($expected->name);
    expect($result)->not->toBeNull();
    expect(print_r($result, true))->toBe(print_r($expected, true));
});

it('shadowing function names', function () {
    $global = new SymbolTable();
    $global->defineFunction('a');
    $global->define('a');

    $expected = new Symbol('a', Scope::GLOBAL, 0);

    $result = $global->resolve($expected->name);
    expect($result)->not->toBeNull();
    expect(print_r($result, true))->toBe(print_r($expected, true));
});
