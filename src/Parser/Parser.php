<?php

namespace Monkey\Parser;

use Monkey\Ast\Expression\ArrayLiteral;
use Monkey\Ast\Expression\Boolean;
use Monkey\Ast\Expression\CallExpression;
use Monkey\Ast\Expression\Expression;
use Monkey\Ast\Expression\FunctionLiteral;
use Monkey\Ast\Expression\HashLiteral;
use Monkey\Ast\Expression\Identifier;
use Monkey\Ast\Expression\IfExpression;
use Monkey\Ast\Expression\IndexExpression;
use Monkey\Ast\Expression\InfixExpression;
use Monkey\Ast\Expression\IntegerLiteral;
use Monkey\Ast\Expression\PrefixExpression;
use Monkey\Ast\Expression\StringLiteral;
use Monkey\Ast\Program;
use Monkey\Ast\Statement\BlockStatement;
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

        $this->nextToken();

        $value = $this->parseExpression(Precedence::LOWEST);

        if ($this->peekTokenIs(Type::SEMICOLON)) {
            $this->nextToken();
        }

        return new LetStatement($token, $name, $value);
    }

    private function parseReturnStatement(): ReturnStatement
    {
        return new ReturnStatement(
            $this->curToken,
            call_user_func(function () {
                $this->nextToken();
                $value = $this->parseExpression(Precedence::LOWEST);
                if ($this->peekTokenIs(Type::SEMICOLON)) {
                    $this->nextToken();
                }
                return $value;
            }),
        );
    }

    private function parseBlockStatement(): BlockStatement
    {
        $token = $this->curToken;
        $statements = [];

        $this->nextToken();

        while (!$this->curTokenIs(Type::RBRACE) && !$this->curTokenIs(Type::EOF)) {
            $stmt = $this->parseStatement();

            if (!is_null($stmt)) {
                $statements[] = $stmt;
            }

            $this->nextToken();
        }

        return new BlockStatement($token, $statements);
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
            Type::LPAREN => $this->parseGroupedExpression(),
            Type::IF => $this->parseIfExpression(),
            Type::FUNCTION => $this->parseFunctionLiteral(),
            Type::STRING => $this->parseStringLiteral(),
            Type::LBRACKET => $this->parseArrayLiteral(),
            Type::LBRACE => $this->parseHashLiteral(),
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
                Type::LPAREN => call_user_func(function () use ($left) {
                    $this->nextToken();
                    return $this->parseCallExpression($left);
                }),
                Type::LBRACKET => call_user_func(function () use ($left) {
                    $this->nextToken();
                    return $this->parseIndexExpression($left);
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

    private function parseCallExpression(Expression $function): CallExpression
    {
        return new CallExpression(
            $this->curToken,
            $function,
            $this->parseExpressionList(Type::RPAREN),
        );
    }

    private function parseIndexExpression(Expression $left): ?IndexExpression
    {
        $token = $this->curToken;

        $this->nextToken();
        $index = $this->parseExpression(Precedence::LOWEST);

        if (!$this->expectPeek(Type::RBRACKET)) {
            return null;
        }

        return new IndexExpression($token, $left, $index);
    }

    private function parseGroupedExpression(): ?Expression
    {
        $this->nextToken();

        $expression = $this->parseExpression(Precedence::LOWEST);

        if (!$this->expectPeek(Type::RPAREN)) {
            return null;
        }

        return $expression;
    }

    private function parseIfExpression(): ?IfExpression
    {
        $token = $this->curToken;

        if (!$this->expectPeek(Type::LPAREN)) {
            return null;
        }

        $this->nextToken();
        $condition = $this->parseExpression(Precedence::LOWEST);

        if (!$this->expectPeek(Type::RPAREN)) {
            return null;
        }

        if (!$this->expectPeek(Type::LBRACE)) {
            return null;
        }

        $consequence = $this->parseBlockStatement();

        $alternative = null;
        if ($this->peekTokenIs(Type::ELSE)) {
            $this->nextToken();

            if (!$this->expectPeek(Type::LBRACE)) {
                return null;
            }

            $alternative = $this->parseBlockStatement();
        }

        return new IfExpression($token, $condition, $consequence, $alternative);
    }

    private function parseFunctionLiteral(): ?FunctionLiteral
    {
        $token = $this->curToken;

        if (!$this->expectPeek(Type::LPAREN)) {
            return null;
        }

        $parameters = $this->parseFunctionParameters();

        if (is_null($parameters) || !$this->expectPeek(Type::LBRACE)) {
            return null;
        }

        return new FunctionLiteral($token, $parameters, $this->parseBlockStatement());
    }

    private function parseFunctionParameters(): ?array
    {
        if ($this->peekTokenIs(Type::RPAREN)) {
            $this->nextToken();
            return [];
        }

        $this->nextToken();

        $parameters = [
            new Identifier($this->curToken, $this->curToken->literal),
        ];

        while ($this->peekTokenIs(Type::COMMA)) {
            $this->nextToken();
            $this->nextToken();
            $parameters[] = new Identifier($this->curToken, $this->curToken->literal);
        }

        if (!$this->expectPeek(Type::RPAREN)) {
            return null;
        }

        return $parameters;
    }

    private function parseStringLiteral(): StringLiteral
    {
        return new StringLiteral(
            $this->curToken,
            $this->curToken->literal,
        );
    }

    private function parseArrayLiteral(): ?ArrayLiteral
    {
        $token = $this->curToken;
        $elements = $this->parseExpressionList(Type::RBRACKET);

        if (is_null($elements)) {
            return $elements;
        }

        return new ArrayLiteral($token, $elements);
    }

    private function parseHashLiteral(): ?HashLiteral
    {
        $token = $this->curToken;

        $pairs = [];

        while (!$this->peekTokenIs(Type::RBRACE)) {
            $this->nextToken();
            $key = $this->parseExpression(Precedence::LOWEST);

            if (!$this->expectPeek(Type::COLON)) {
                return null;
            }

            $this->nextToken();

            $value = $this->parseExpression(Precedence::LOWEST);

            $pairs[] = [$key, $value];

            if (!$this->peekTokenIs(Type::RBRACE) && !$this->expectPeek(Type::COMMA)) {
                return null;
            }
        }

        if (!$this->expectPeek(Type::RBRACE)) {
            return null;
        }

        return new HashLiteral($token, $pairs);
    }

    private function parseExpressionList(Type $end): ?array
    {
        if ($this->peekTokenIs($end)) {
            $this->nextToken();
            return [];
        }

        $this->nextToken();

        $list = [
            $this->parseExpression(Precedence::LOWEST),
        ];

        while ($this->peekTokenIs(Type::COMMA)) {
            $this->nextToken();
            $this->nextToken();
            $list[] = $this->parseExpression(Precedence::LOWEST);
        }

        if (!$this->expectPeek($end)) {
            return null;
        }

        return $list;
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
