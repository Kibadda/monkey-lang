<?php

namespace Monkey\Parser;

use Monkey\Ast\Expression\ArrayLiteral;
use Monkey\Ast\Expression\Boolean;
use Monkey\Ast\Expression\Branch;
use Monkey\Ast\Expression\CallExpression;
use Monkey\Ast\Expression\Expression;
use Monkey\Ast\Expression\FunctionLiteral;
use Monkey\Ast\Expression\HashLiteral;
use Monkey\Ast\Expression\Identifier;
use Monkey\Ast\Expression\IfExpression;
use Monkey\Ast\Expression\IndexExpression;
use Monkey\Ast\Expression\InfixExpression;
use Monkey\Ast\Expression\IntegerLiteral;
use Monkey\Ast\Expression\MacroLiteral;
use Monkey\Ast\Expression\MatchLiteral;
use Monkey\Ast\Expression\Pair;
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
    public Token $curToken;
    public Token $peekToken;
    /** @var string[] $errors */
    public array $errors = [];

    public function __construct(Lexer $lexer)
    {
        $this->lexer = $lexer;
        $this->curToken = $this->lexer->nextToken();
        $this->peekToken = $this->lexer->nextToken();
    }

    public function nextToken(): void
    {
        $this->curToken = $this->peekToken;
        $this->peekToken = $this->lexer->nextToken();
    }

    public function parseProgam(): Program
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

        if (is_null($value)) {
            return $value;
        }

        if ($value instanceof FunctionLiteral) {
            $value->name = $name->value;
        }

        if ($this->peekTokenIs(Type::SEMICOLON)) {
            $this->nextToken();
        }

        return new LetStatement($token, $name, $value);
    }

    private function parseReturnStatement(): ?ReturnStatement
    {
        $token = $this->curToken;

        $this->nextToken();

        $value = $this->parseExpression(Precedence::LOWEST);

        if (is_null($value)) {
            return $value;
        }

        if ($this->peekTokenIs(Type::SEMICOLON)) {
            $this->nextToken();
        }

        return new ReturnStatement($token, $value);
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

    private function parseExpressionStatement(): ?ExpressionStatement
    {
        $token = $this->curToken;

        $expression = $this->parseExpression(Precedence::LOWEST);

        if (is_null($expression)) {
            return $expression;
        }

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
            Type::MACRO => $this->parseMacroLiteral(),
            Type::MATCH => $this->parseMatchLiteral(),
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
                Type::GT => $this->parseInfixExpression($left),
                Type::LPAREN => $this->parseCallExpression($left),
                Type::LBRACKET => $this->parseIndexExpression($left),
                default => null,
            };

            if (is_null($new)) {
                return $left;
            }

            $left = $new;
        }

        return $left;
    }

    private function parsePrefixExpression(): ?PrefixExpression
    {
        $token = $this->curToken;

        $this->nextToken();

        $right = $this->parseExpression(Precedence::PREFIX);

        if (is_null($right)) {
            return $right;
        }

        return new PrefixExpression($token, $token->literal, $right);
    }

    private function parseInfixExpression(Expression $left): ?InfixExpression
    {
        $this->nextToken();

        $token = $this->curToken;

        $precedence = $this->curPrecedence();
        $this->nextToken();

        $right = $this->parseExpression($precedence);

        if (is_null($right)) {
            return $right;
        }

        return new InfixExpression($token, $left, $token->literal, $right);
    }

    private function parseCallExpression(Expression $function): ?CallExpression
    {
        $this->nextToken();

        $token = $this->curToken;

        $arguments = $this->parseExpressionList(Type::RPAREN);

        if (is_null($arguments)) {
            return $arguments;
        }

        return new CallExpression($token, $function, $arguments);
    }

    private function parseIndexExpression(Expression $left): ?IndexExpression
    {
        $this->nextToken();

        $token = $this->curToken;

        $this->nextToken();
        $index = $this->parseExpression(Precedence::LOWEST);

        if ($index == null) {
            return null;
        }

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

        if ($condition == null) {
            return null;
        }

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

    private function parseMacroLiteral(): ?MacroLiteral
    {
        $token = $this->curToken;

        if (!$this->expectPeek(Type::LPAREN)) {
            return null;
        }

        $parameters = $this->parseFunctionParameters();

        if (is_null($parameters) || !$this->expectPeek(Type::LBRACE)) {
            return null;
        }

        return new MacroLiteral($token, $parameters, $this->parseBlockStatement());
    }

    private function parseMatchLiteral(): ?MatchLiteral
    {
        $token = $this->curToken;

        if (!$this->expectPeek(Type::LPAREN)) {
            return null;
        }

        $this->nextToken();
        $subject = $this->parseExpression(Precedence::LOWEST);

        if ($subject == null) {
            return $subject;
        }

        if (!$this->expectPeek(Type::RPAREN) || !$this->expectPeek(Type::LBRACE)) {
            return null;
        }

        $branches = $this->parseBranches();

        if ($branches == null) {
            return null;
        }

        $default = null;
        if ($this->peekTokenIs(Type::QUESTION)) {
            $this->nextToken();

            if (!$this->expectPeek(Type::ARROW)) {
                return null;
            }

            $this->nextToken();

            $default = $this->parseExpression(Precedence::LOWEST);

            if ($default == null) {
                return $default;
            }
        }

        if (!$this->expectPeek(Type::RBRACE)) {
            return null;
        }

        return new MatchLiteral($token, $subject, $branches, $default);
    }

    /**
     * @return null|Branch[]
     */
    private function parseBranches(): ?array
    {
        if ($this->peekTokenIs(Type::RBRACE)) {
            $this->nextToken();
            return [];
        }

        $branches = [];

        while (!$this->peekTokenIs(Type::RBRACE) && !$this->peekTokenIs(Type::QUESTION)) {
            $this->nextToken();
            $condition = $this->parseExpression(Precedence::LOWEST);

            if ($condition == null) {
                return $condition;
            }

            if (!$this->expectPeek(Type::ARROW)) {
                return null;
            }

            $this->nextToken();

            $consequence = $this->parseExpression(Precedence::LOWEST);

            if ($consequence == null) {
                return $consequence;
            }

            $branches[] = new Branch($condition, $consequence);

            if ($this->peekTokenIs(Type::COMMA)) {
                $this->nextToken();
            }
        }

        return $branches;
    }

    /**
     * @return null|Identifier[]
     */
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

            if ($key == null) {
                return null;
            }

            if (!$this->expectPeek(Type::COLON)) {
                return null;
            }

            $this->nextToken();

            $value = $this->parseExpression(Precedence::LOWEST);

            if ($value == null) {
                return null;
            }

            $pairs[] = new Pair($key, $value);

            if (!$this->peekTokenIs(Type::RBRACE) && !$this->expectPeek(Type::COMMA)) {
                return null;
            }
        }

        if (!$this->expectPeek(Type::RBRACE)) {
            return null;
        }

        return new HashLiteral($token, $pairs);
    }

    /**
     * @return null|Expression[]
     */
    private function parseExpressionList(Type $end): ?array
    {
        if ($this->peekTokenIs($end)) {
            $this->nextToken();
            return [];
        }

        $this->nextToken();

        $list = [];

        $expression = $this->parseExpression(Precedence::LOWEST);

        if ($expression == null) {
            return null;
        }

        $list[] = $expression;

        while ($this->peekTokenIs(Type::COMMA)) {
            $this->nextToken();
            $this->nextToken();
            $expression = $this->parseExpression(Precedence::LOWEST);

            if ($expression == null) {
                return null;
            }

            $list[] = $expression;
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
            intval($this->curToken->literal),
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
        return $this->peekToken->type->precedence();
    }

    private function curPrecedence(): Precedence
    {
        return $this->curToken->type->precedence();
    }

    private function peekError(Type $type): void
    {
        $this->errors[] = "expected next token to be {$type->name}, got {$this->peekToken->type->name} instead";
    }

    private function noPrefixParseFnError(Type $type): void
    {
        $this->errors[] = "no prefix parse function for {$type->name} found";
    }
}
