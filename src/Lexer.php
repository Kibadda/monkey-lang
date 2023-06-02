<?php

namespace Lexer;

use Lexer\Lib\Token;
use Lexer\Lib\Type;

class Lexer
{
    public static function new(string $input): self
    {
        return new self(
            $input,
            0,
            0,
            substr($input, 0, 1),
        );
    }

    public function __construct(
        public string $input,
        public int $position,
        public int $readPosition,
        public string $ch,
    ) {
    }

    private function isLetter(string $char): bool
    {
        return strlen($char) == 1 && (('a' <= $char && $char <= 'z') || ('A' <= $char && $char <= 'Z') || ($char == '_'));
    }

    private function isDigit(string $char): bool
    {
        return strlen($char) == 1 && '0' <= $char && $char <= '9';
    }

    private function readChar()
    {
        if ($this->readPosition >= strlen($this->input)) {
            $this->ch = '';
        } else {
            $this->ch = substr($this->input, $this->readPosition, 1);
        }
        $this->position = $this->readPosition;
        $this->readPosition++;
    }

    private function readIdentifier(): string
    {
        $pos = $this->position;

        while ($this->isLetter($this->ch)) {
            $this->readChar();
        }

        return substr($this->input, $pos, $this->position - $pos);
    }

    private function readNumber(): string
    {
        $pos = $this->position;

        while ($this->isDigit($this->ch)) {
            $this->readChar();
        }

        return substr($this->input, $pos, $this->position - $pos);
    }

    private function skipWhitespace()
    {
        while ($this->ch == ' ' || $this->ch == "\t" || $this->ch == "\n" || $this->ch == "\r") {
            $this->readChar();
        }
    }

    public function nextToken(): Token
    {
        $token = null;

        $this->skipWhitespace();

        switch ($this->ch) {
            case '=':
                $token = new Token(Type::ASSIGN, $this->ch);
                break;
            case ';':
                $token = new Token(Type::SEMICOLON, $this->ch);
                break;
            case '(':
                $token = new Token(Type::LPAREN, $this->ch);
                break;
            case ')':
                $token = new Token(Type::RPAREN, $this->ch);
                break;
            case ',':
                $token = new Token(Type::COMMA, $this->ch);
                break;
            case '+':
                $token = new Token(Type::PLUS, $this->ch);
                break;
            case '{':
                $token = new Token(Type::LBRACE, $this->ch);
                break;
            case '}':
                $token = new Token(Type::RBRACE, $this->ch);
                break;
            case '':
                $token = new Token(Type::EOL, $this->ch);
                break;
            default:
                if ($this->isLetter($this->ch)) {
                    $identifier = $this->readIdentifier();
                    $token = new Token(Type::lookup($identifier), $identifier);
                    return $token;
                } else if ($this->isDigit($this->ch)) {
                    $token = new Token(Type::INT, $this->readNumber());
                    return $token;
                } else {
                    $token = new Token(Type::ILLEGAL, $this->ch);
                }
                break;
        }

        $this->readChar();

        return $token;
    }
}
