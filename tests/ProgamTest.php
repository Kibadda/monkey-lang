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
use Monkey\Token\Token;
use Monkey\Token\Type;

$token = new Token(Type::ILLEGAL, '');

$one = fn () => new IntegerLiteral($token, 1);
$two = fn () => new IntegerLiteral($token, 2);

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
        new Program([new ExpressionStatement($token, $one())]),
        new Program([new ExpressionStatement($token, $two())]),
    ],
    [
        new InfixExpression($token, $one(), '+', $two()),
        new InfixExpression($token, $two(), '+', $two()),
    ],
    [
        new PrefixExpression($token, '-', $two()),
        new PrefixExpression($token, '-', $two()),
    ],
    [
        new IndexExpression($token, $one(), $one()),
        new IndexExpression($token, $two(), $two()),
    ],
    [
        new IfExpression($token, $one(), new BlockStatement($token, [new ExpressionStatement($token, $one())]), new BlockStatement($token, [new ExpressionStatement($token, $one())])),
        new IfExpression($token, $two(), new BlockStatement($token, [new ExpressionStatement($token, $two())]), new BlockStatement($token, [new ExpressionStatement($token, $two())])),
    ],
    [
        new ReturnStatement($token, $one()),
        new ReturnStatement($token, $two()),
    ],
    [
        new LetStatement($token, new Identifier($token, ''), $one()),
        new LetStatement($token, new Identifier($token, ''), $two()),
    ],
    [
        new FunctionLiteral($token, [new Identifier($token, '')], new BlockStatement($token, [new ExpressionStatement($token, $one())])),
        new FunctionLiteral($token, [new Identifier($token, '')], new BlockStatement($token, [new ExpressionStatement($token, $two())])),
    ],
    [
        new ArrayLiteral($token, [$one(), $one()]),
        new ArrayLiteral($token, [$two(), $two()]),
    ],
    [
        new HashLiteral($token, [[$one(), $one()]]),
        new HashLiteral($token, [[$two(), $two()]]),
    ],
]);
