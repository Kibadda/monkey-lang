<?php

use Monkey\Code\Code;

it('makes', function (Code $code, array $operands, array $expected) {
    $instruction = Code::make($code, ...$operands);

    expect($instruction)->toHaveCount(count($expected));

    foreach ($expected as $i => $b) {
        expect($instruction[$i])->toBe($b);
    }
})->with([
    [Code::CONSTANT, [65534], [Code::CONSTANT->value, 255, 254]],
]);
