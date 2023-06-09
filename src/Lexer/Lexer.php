<?php

namespace Monkey\Lexer;

use Monkey\Token\Token;
use Monkey\Token\Type;

class Lexer
{
    public function __construct(
        public string $input,
        public int $position = -1,
        public int $readPosition = 0,
        public string $ch = '',
    ) {
        $this->readChar();
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

    private function peekChar(): string
    {
        if ($this->readPosition >= strlen($this->input)) {
            return '';
        } else {
            return substr($this->input, $this->readPosition, 1);
        }
    }

    private function readString(): string
    {
        $pos = $this->position + 1;
        while (true) {
            $this->readChar();
            if ($this->ch == '"' || $this->ch = '') {
                break;
            }
        }

        return substr($this->input, $pos, $this->position - $pos);
    }

    public function nextToken(): Token
    {
        $token = null;

        $this->skipWhitespace();

        if ($this->isLetter($this->ch)) {
            $identifier = $this->readIdentifier();
            $token = new Token(Type::lookup($identifier), $identifier);
            return $token;
        } else if ($this->isDigit($this->ch)) {
            return new Token(Type::INT, $this->readNumber());
        }

        $token = match ($this->ch) {
            '=' => call_user_func(function () {
                if ($this->peekChar() == '=') {
                    $ch = $this->ch;
                    $this->readChar();
                    return new Token(Type::EQ, "{$ch}{$this->ch}");
                }

                return new Token(Type::ASSIGN, $this->ch);
            }),
            ';' => new Token(Type::SEMICOLON, $this->ch),
            '(' => new Token(Type::LPAREN, $this->ch),
            ')' => new Token(Type::RPAREN, $this->ch),
            ',' => new Token(Type::COMMA, $this->ch),
            '+' => new Token(Type::PLUS, $this->ch),
            '-' => new Token(Type::MINUS, $this->ch),
            '/' => new Token(Type::SLASH, $this->ch),
            '*' => new Token(Type::ASTERISK, $this->ch),
            '!' => call_user_func(function () {
                if ($this->peekChar() == '=') {
                    $ch = $this->ch;
                    $this->readChar();
                    return new Token(Type::NOT_EQ, "{$ch}{$this->ch}");
                }

                return new Token(Type::BANG, $this->ch);
            }),
            '<' => new Token(Type::LT, $this->ch),
            '>' => new Token(Type::GT, $this->ch),
            '{' => new Token(Type::LBRACE, $this->ch),
            '}' => new Token(Type::RBRACE, $this->ch),
            '"' => new Token(Type::STRING, $this->readString()),
            '[' => new Token(Type::LBRACKET, $this->ch),
            ']' => new Token(Type::RBRACKET, $this->ch),
            ':' => new Token(Type::COLON, $this->ch),
            '' => new Token(Type::EOF, $this->ch),
            default => new Token(Type::ILLEGAL, $this->ch),
        };

        $this->readChar();

        return $token;
    }
}
