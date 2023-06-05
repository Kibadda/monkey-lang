<?php

use Monkey\Ast\Expression\ArrayLiteral;
use Monkey\Ast\Expression\FunctionLiteral;
use Monkey\Ast\Expression\HashLiteral;
use Monkey\Ast\Expression\Identifier;
use Monkey\Ast\Expression\IfExpression;
use Monkey\Ast\Expression\IndexExpression;
use Monkey\Ast\Expression\InfixExpression;
use Monkey\Ast\Expression\IntegerLiteral;
use Monkey\Ast\Expression\PrefixExpression;
use Monkey\Ast\Node;
use Monkey\Ast\Program;
use Monkey\Ast\Statement\BlockStatement;
use Monkey\Ast\Statement\ExpressionStatement;
use Monkey\Ast\Statement\LetStatement;
use Monkey\Ast\Statement\ReturnStatement;

$one = fn () => new IntegerLiteral(null, 1);
$two = fn () => new IntegerLiteral(null, 2);

it('modifies', function (Node $input, Node $expected) {
    $turnOneIntoTwo = function (Node $node): Node {
        if (!$node instanceof IntegerLiteral) {
            return $node;
        }

        if ($node->value != 1) {
            return $node;
        }

        $node->value = 2;
        return $node;
    };

    $modified = $input->modify($turnOneIntoTwo);

    expect(print_r($modified, true))->toBe(print_r($expected, true));
})->with([
    [
        $one(),
        $two(),
    ],
    [
        new Program([new ExpressionStatement(value: $one())]),
        new Program([new ExpressionStatement(value: $two())]),
    ],
    [
        new InfixExpression(null, $one(), '+', $two()),
        new InfixExpression(null, $two(), '+', $two()),
    ],
    [
        new PrefixExpression(null, '-', $two()),
        new PrefixExpression(null, '-', $two()),
    ],
    [
        new IndexExpression(null, $one(), $one()),
        new IndexExpression(null, $two(), $two()),
    ],
    [
        new IfExpression(null, $one(), new BlockStatement(null, [new ExpressionStatement(null, $one())]), new BlockStatement(null, [new ExpressionStatement(null, $one())])),
        new IfExpression(null, $two(), new BlockStatement(null, [new ExpressionStatement(null, $two())]), new BlockStatement(null, [new ExpressionStatement(null, $two())])),
    ],
    [
        new ReturnStatement(null, $one()),
        new ReturnStatement(null, $two()),
    ],
    [
        new LetStatement(null, null, $one()),
        new LetStatement(null, null, $two()),
    ],
    [
        new FunctionLiteral(null, [new Identifier(null, null)], new BlockStatement(null, [new ExpressionStatement(null, $one())])),
        new FunctionLiteral(null, [new Identifier(null, null)], new BlockStatement(null, [new ExpressionStatement(null, $two())])),
    ],
    [
        new ArrayLiteral(null, [$one(), $one()]),
        new ArrayLiteral(null, [$two(), $two()]),
    ],
    [
        new HashLiteral(null, [[$one(), $one()]]),
        new HashLiteral(null, [[$two(), $two()]]),
    ],
]);
