<?php

namespace Monkey\Ast;

use Monkey\Ast\Expression\ArrayLiteral;
use Monkey\Ast\Expression\Expression;
use Monkey\Ast\Expression\FunctionLiteral;
use Monkey\Ast\Expression\HashLiteral;
use Monkey\Ast\Expression\Identifier;
use Monkey\Ast\Expression\IfExpression;
use Monkey\Ast\Expression\IndexExpression;
use Monkey\Ast\Expression\InfixExpression;
use Monkey\Ast\Expression\PrefixExpression;
use Monkey\Ast\Statement\BlockStatement;
use Monkey\Ast\Statement\ExpressionStatement;
use Monkey\Ast\Statement\LetStatement;
use Monkey\Ast\Statement\ReturnStatement;
use Monkey\Ast\Statement\Statement;

trait Modify
{
    public function modify(callable $modifier): Node
    {
        match (self::class) {
            Program::class => call_user_func(function () use ($modifier) {
                /** @var Program $this */
                $this->statements = array_map(fn (Statement $statement) => $statement->modify($modifier), $this->statements);
            }),
            ExpressionStatement::class => call_user_func(function () use ($modifier) {
                /** @var ExpressionStatement $this */
                $this->value = $this->value->modify($modifier);
            }),
            InfixExpression::class => call_user_func(function () use ($modifier) {
                /** @var InfixExpression $this */
                $this->left = $this->left->modify($modifier);
                $this->right = $this->right->modify($modifier);
            }),
            PrefixExpression::class => call_user_func(function () use ($modifier) {
                /** @var PrefixExpression $this */
                $this->right = $this->right->modify($modifier);
            }),
            IndexExpression::class => call_user_func(function () use ($modifier) {
                /** @var IndexExpression $this */
                $this->left = $this->left->modify($modifier);
                $this->index = $this->index->modify($modifier);
            }),
            IfExpression::class => call_user_func(function () use ($modifier) {
                /** @var IfExpression $this */
                $this->condition = $this->condition->modify($modifier);
                $this->consequence = $this->consequence->modify($modifier);
                $this->alternative = is_null($this->alternative) ?: $this->alternative->modify($modifier);
            }),
            BlockStatement::class => call_user_func(function () use ($modifier) {
                /** @var BlockStatement $this */
                $this->statements = array_map(fn (Statement $statement) => $statement->modify($modifier), $this->statements);
            }),
            ReturnStatement::class => call_user_func(function () use ($modifier) {
                /** @var ReturnStatement $this */
                $this->value = $this->value->modify($modifier);
            }),
            LetStatement::class => call_user_func(function () use ($modifier) {
                /** @var LetStatement $this */
                $this->value = $this->value->modify($modifier);
            }),
            FunctionLiteral::class => call_user_func(function () use ($modifier) {
                /** @var FunctionLiteral $this */
                $this->parameters = array_map(fn (Identifier $parameter) => $parameter->modify($modifier), $this->parameters);
                $this->body = $this->body->modify($modifier);
            }),
            ArrayLiteral::class => call_user_func(function () use ($modifier) {
                /** @var ArrayLiteral $this */
                $this->elements = array_map(fn (Expression $element) => $element->modify($modifier), $this->elements);
            }),
            HashLiteral::class => call_user_func(function () use ($modifier) {
                /** @var HashLiteral $this */
                $this->pairs = array_map(fn (array $pair) => [$pair[0]->modify($modifier), $pair[1]->modify($modifier)], $this->pairs);
            }),
            default => null,
        };

        return $modifier($this);
    }
}
