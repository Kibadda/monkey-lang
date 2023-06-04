<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

// uses(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

use Monkey\Ast\Expression\Boolean;
use Monkey\Ast\Expression\Identifier;
use Monkey\Ast\Expression\InfixExpression;
use Monkey\Ast\Expression\IntegerLiteral;
use Monkey\Ast\Expression\PrefixExpression;
use Monkey\Ast\Statement\ExpressionStatement;
use Monkey\Ast\Statement\LetStatement;
use Monkey\Ast\Statement\ReturnStatement;

expect()->extend('toBeLetStatement', function (string $name) {
    expect($this->value)->toBeInstanceOf(LetStatement::class);
    expect($this->value->tokenLiteral())->toBe('let');
    expect($this->value->name->value)->toBe($name);
    expect($this->value->name->tokenLiteral())->toBe($name);
});

expect()->extend('toBeReturnStatement', function () {
    expect($this->value)->toBeInstanceOf(ReturnStatement::class);
    expect($this->value->tokenLiteral())->toBe('return');
});

expect()->extend('toBeExpressionStatement', function (string $expression, ...$args) {
    expect($this->value)->toBeInstanceOf(ExpressionStatement::class);

    match ($expression) {
        Boolean::class => expect($this->value->value)->toBeBoolean(...$args),
        Identifier::class => expect($this->value->value)->toBeIdentifier(...$args),
        IntegerLiteral::class => expect($this->value->value)->toBeIntegerLiteral(...$args),
        InfixExpression::class => expect($this->value->value)->toBeInfixExpression(...$args),
        PrefixExpression::class => expect($this->value->value)->toBePrefixExpression(...$args),
    };
});

expect()->extend('toBeLiteralExpression', function (string $expression, string|int|bool $value) {
    match ($expression) {
        Boolean::class => expect($this->value)->toBeBoolean($value),
        Identifier::class => expect($this->value)->toBeIdentifier($value),
        IntegerLiteral::class => expect($this->value)->toBeIntegerLiteral($value),
    };
});

expect()->extend('toBeBoolean', function (bool $value) {
    expect($this->value)->toBeInstanceOf(Boolean::class);
    expect($this->value->value)->toBe($value);
    expect($this->value->tokenLiteral())->toBe($value ? 'true' : 'false');
});

expect()->extend('toBeIdentifier', function (string $value) {
    expect($this->value)->toBeInstanceOf(Identifier::class);
    expect($this->value->value)->toBe($value);
    expect($this->value->tokenLiteral())->toBe($value);
});

expect()->extend('toBeIntegerLiteral', function (int $value) {
    expect($this->value)->toBeInstanceOf(IntegerLiteral::class);
    expect($this->value->value)->toBe($value);
    expect($this->value->tokenLiteral())->toBe("{$value}");
});

expect()->extend('toBeInfixExpression', function ($left, string $operator, $right) {
    expect($this->value)->toBeInstanceOf(InfixExpression::class);
    expect($this->value->left)->toBeLiteralExpression(...$left);
    expect($this->value->operator)->toBe($operator);
    expect($this->value->right)->toBeLiteralExpression(...$right);
});

expect()->extend('toBePrefixExpression', function (string $operator, $right) {
    expect($this->value)->toBeInstanceOf(PrefixExpression::class);
    expect($this->value->operator)->toBe($operator);
    expect($this->value->right)->toBeLiteralExpression(...$right);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}
