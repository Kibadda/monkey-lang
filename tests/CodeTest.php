<?php

use Monkey\Code\Code;

it('makes', function (Code $code, array $operands, array $expected) {
    $instruction = $code->make(...$operands);

    expect($instruction->elements)->toHaveCount(count($expected));

    foreach ($expected as $i => $b) {
        expect($instruction[$i])->toBe($b);
    }
})->with([
    [Code::CONSTANT, [65534], [Code::CONSTANT->value, 255, 254]],
    [Code::ADD, [], [Code::ADD->value]],
    [Code::GET_LOCAL, [255], [Code::GET_LOCAL->value, 255]],
]);
