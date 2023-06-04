<?php

namespace Monkey\Parser;

use Monkey\Ast\Expression\Boolean;
use Monkey\Ast\Expression\Expression;
use Monkey\Ast\Expression\Identifier;
use Monkey\Ast\Expression\InfixExpression;
use Monkey\Ast\Expression\IntegerLiteral;
use Monkey\Ast\Expression\PrefixExpression;
use Monkey\Ast\Program;
use Monkey\Ast\Statement\ExpressionStatement;
use Monkey\Ast\Statement\LetStatement;
use Monkey\Ast\Statement\ReturnStatement;
use Monkey\Ast\Statement\Statement;
use Monkey\Lexer\Lexer;
use Monkey\Token\Token;
use Monkey\Token\Type;

class Parser
{
    public Lexer $lexer;
    public ?Token $curToken = null;
    public ?Token $peekToken = null;
    /** @var string[] $errors */
    public array $errors = [];

    public static function new(Lexer $lexer): self
    {
        return new self($lexer);
    }

    private function __construct(Lexer $lexer)
    {
        $this->lexer = $lexer;
        $this->nextToken();
        $this->nextToken();
    }

    public function nextToken()
    {
        $this->curToken = $this->peekToken;
        $this->peekToken = $this->lexer->nextToken();
    }

    public function parseProgam(): ?Program
    {
        $program = new Program();

        while ($this->curToken->type != Type::EOF) {
            $stmt = $this->parseStatement();
            if (!is_null($stmt)) {
                $program->statements[] = $stmt;
            }
            $this->nextToken();
        }

        return $program;
    }

    private function parseStatement(): ?Statement
    {
        return match ($this->curToken->type) {
            Type::LET => $this->parseLetStatement(),
            Type::RETURN => $this->parseReturnStatement(),
            default => $this->parseExpressionStatement(),
        };
    }

    private function parseLetStatement(): ?LetStatement
    {
        $token = $this->curToken;

        if (!$this->expectPeek(Type::IDENTIFIER)) {
            return null;
        }

        $name = new Identifier($this->curToken, $this->curToken->literal);

        if (!$this->expectPeek(Type::ASSIGN)) {
            return null;
        }

        while (!$this->curTokenIs(Type::SEMICOLON)) {
            $this->nextToken();
        }

        return new LetStatement($token, $name);
    }

    private function parseReturnStatement(): ReturnStatement
    {
        $token = $this->curToken;

        $this->nextToken();

        while (!$this->curTokenIs(Type::SEMICOLON)) {
            $this->nextToken();
        }

        return new ReturnStatement($token);
    }

    private function parseExpressionStatement(): ExpressionStatement
    {
        $token = $this->curToken;
        $expression = $this->parseExpression(Precedence::LOWEST);

        if ($this->peekTokenIs(Type::SEMICOLON)) {
            $this->nextToken();
        }

        return new ExpressionStatement($token, $expression);
    }

    private function parseExpression(Precedence $precedence): ?Expression
    {
        $left = match ($this->curToken->type) {
            Type::IDENTIFIER => $this->parseIdentifier(),
            Type::INT => $this->parseIntegerLiteral(),
            Type::BANG,
            Type::MINUS => $this->parsePrefixExpression(),
            Type::TRUE,
            Type::FALSE => $this->parseBoolean(),
            default => null,
        };

        if (is_null($left)) {
            $this->noPrefixParseFnError($this->curToken->type);
            return null;
        }

        while (!$this->peekTokenIs(Type::SEMICOLON) && $precedence->value < $this->peekPrecedence()->value) {
            $new = match ($this->peekToken->type) {
                Type::PLUS,
                Type::MINUS,
                Type::SLASH,
                Type::ASTERISK,
                Type::EQ,
                Type::NOT_EQ,
                Type::LT,
                Type::GT => call_user_func(function () use ($left) {
                    $this->nextToken();
                    return $this->parseInfixExpression($left);
                }),
                default => null,
            };

            if (is_null($new)) {
                return $left;
            }

            $left = $new;
        }

        return $left;
    }

    private function parsePrefixExpression(): PrefixExpression
    {
        return new PrefixExpression(
            $this->curToken,
            $this->curToken->literal,
            call_user_func(function () {
                $this->nextToken();
                return $this->parseExpression(Precedence::PREFIX);
            }),
        );
    }

    private function parseInfixExpression(Expression $left): InfixExpression
    {
        return new InfixExpression(
            $this->curToken,
            $left,
            $this->curToken->literal,
            call_user_func(function () {
                $precedence = $this->curPrecedence();
                $this->nextToken();
                return $this->parseExpression($precedence);
            }),
        );
    }

    private function parseIdentifier(): Identifier
    {
        return new Identifier(
            $this->curToken,
            $this->curToken->literal,
        );
    }

    private function parseIntegerLiteral(): IntegerLiteral
    {
        return new IntegerLiteral(
            $this->curToken,
            $this->curToken->literal,
        );
    }

    private function parseBoolean(): Boolean
    {
        return new Boolean(
            $this->curToken,
            $this->curTokenIs(Type::TRUE),
        );
    }

    private function expectPeek(Type $type): bool
    {
        if (!$this->peekTokenIs($type)) {
            $this->peekError($type);
            return false;
        }

        $this->nextToken();
        return true;
    }

    private function peekTokenIs(Type $type): bool
    {
        return $this->peekToken->type == $type;
    }

    private function curTokenIs(Type $type): bool
    {
        return $this->curToken->type == $type;
    }

    private function peekPrecedence(): Precedence
    {
        return Precedence::fromType($this->peekToken->type);
    }

    private function curPrecedence(): Precedence
    {
        return Precedence::fromType($this->curToken->type);
    }

    private function peekError(Type $type)
    {
        $this->errors[] = "expected next token to be {$type->name}, got {$this->peekToken->type->name} instead";
    }

    private function noPrefixParseFnError(Type $type)
    {
        $this->errors[] = "no prefix parse function for {$type->name} found";
    }
}
