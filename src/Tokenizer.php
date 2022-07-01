<?php

/**
 * Platine Expression
 *
 * Platine Expression is an expression parser, evaluator with support of custom
 * operators and functions
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2020 Platine Expression
 * Copyright (c) Alexander Kiryukhin
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

/**
 * @file Tokenizer.php
 *
 * The Tokenizer class
 *
 *  @package    Platine\Expression
 *  @author Platine Developers Team
 *  @copyright  Copyright (c) 2020
 *  @license    http://opensource.org/licenses/MIT  MIT License
 *  @link   http://www.iacademy.cf
 *  @version 1.0.0
 *  @filesource
 */
declare(strict_types=1);

namespace Platine\Expression;

use Platine\Expression\Exception\IncorrectBracketsException;
use Platine\Expression\Exception\UnknownOperatorException;
use RuntimeException;
use SplStack;

/**
 * @class Tokenizer
 * @package Platine\Expression
 */
class Tokenizer
{
    /**
     * List of token
     * @var array<Token>
     */
    protected array $tokens = [];

    /**
     * The expression to evaluate
     * @var string
     */
    protected string $input = '';

    /**
     * The number buffering
     * @var string
     */
    protected string $numberBuffer = '';

    /**
     * The string buffering
     * @var string
     */
    protected string $stringBuffer = '';

    /**
     * Whether to allow negative or not
     * @var bool
     */
    protected bool $allowNegative = true;

    /**
     * The list of operator
     * @var array<string, Operator>
     */
    protected array $operators = [];

    /**
     * Whether the current pointer is inside single quoted string
     * @var bool
     */
    protected bool $inSingleQuotedString = false;

    /**
     * Whether the current pointer is inside double quoted string
     * @var bool
     */
    protected bool $inDoubleQuotedString = false;

    /**
     * Create new instance
     * @param string $input
     * @param array<string, Operator> $operators
     */
    public function __construct(string $input, array $operators)
    {
        $this->input = $input;
        $this->operators = $operators;
    }

    /**
     * Produce the tokens
     * @return $this
     */
    public function tokenize(): self
    {
        $isLastCharEscape = false;
        foreach (str_split($this->input) as $ch) {
            switch (true) {
                case $this->inSingleQuotedString:
                    if ($ch === '\\') {
                        if ($isLastCharEscape) {
                            $this->stringBuffer .= '\\';
                            $isLastCharEscape = false;
                        } else {
                            $isLastCharEscape = true;
                        }

                        continue 2;
                    } elseif ($ch === "'") {
                        if ($isLastCharEscape) {
                            $this->stringBuffer .= "'";
                            $isLastCharEscape = false;
                        } else {
                            $this->tokens[] = new Token(Token::STRING, $this->stringBuffer);
                            $this->inSingleQuotedString = false;
                            $this->stringBuffer = '';
                        }

                        continue 2;
                    }

                    if ($isLastCharEscape) {
                        $this->stringBuffer .= '\\';
                        $isLastCharEscape = false;
                    }

                    $this->stringBuffer .= $ch;

                    continue 2;

                case $this->inDoubleQuotedString:
                    if ($ch === '\\') {
                        if ($isLastCharEscape) {
                            $this->stringBuffer .= '\\';
                            $isLastCharEscape = false;
                        } else {
                            $isLastCharEscape = true;
                        }

                        continue 2;
                    } elseif ($ch === '"') {
                        if ($isLastCharEscape) {
                            $this->stringBuffer .= '"';
                            $isLastCharEscape = false;
                        } else {
                            $this->tokens[] = new Token(Token::STRING, $this->stringBuffer);
                            $this->inDoubleQuotedString = false;
                            $this->stringBuffer = '';
                        }

                        continue 2;
                    }

                    if ($isLastCharEscape) {
                        $this->stringBuffer .= '\\';
                        $isLastCharEscape = false;
                    }

                    $this->stringBuffer .= $ch;

                    continue 2;

                case $ch === '[':
                    $this->tokens[] = new Token(Token::FUNCTION, 'array');
                    $this->allowNegative = true;
                    $this->tokens[] = new Token(Token::LEFT_PARENTHESIS, '');

                    continue 2;

                case $ch == ' ' || $ch == "\n" || $ch == "\r" || $ch == "\t":
                    $this->emptyNumberBufferAsLiteral();
                    $this->emptyStringBufferAsVariable();
                    $this->tokens[] = new Token(Token::SPACE, '');
                    continue 2;

                case $this->isNumber($ch):
                    if ($this->stringBuffer != '') {
                        $this->stringBuffer .= $ch;

                        continue 2;
                    }
                    $this->numberBuffer .= $ch;
                    $this->allowNegative = false;
                    break;

                case strtolower($ch) === 'e':
                    if (strlen($this->numberBuffer) > 0 && strpos($this->numberBuffer, '.') !== false) {
                        $this->numberBuffer .= 'e';
                        $this->allowNegative = false;

                        break;
                    }
                // no break
                // Intentionally fall through
                case $this->isAlpha($ch):
                    if (strlen($this->numberBuffer) > 0) {
                        $this->emptyNumberBufferAsLiteral();
                        $this->tokens[] = new Token(Token::OPERATOR, '*');
                    }
                    $this->allowNegative = false;
                    $this->stringBuffer .= $ch;

                    break;

                case $ch == '"':
                    $this->inDoubleQuotedString = true;

                    continue 2;

                case $ch == "'":
                    $this->inSingleQuotedString = true;

                    continue 2;

                case $this->isDot($ch):
                    $this->numberBuffer .= $ch;
                    $this->allowNegative = false;

                    break;

                case $this->isLeftParenthesis($ch):
                    if ($this->stringBuffer != '') {
                        $this->tokens[] = new Token(Token::FUNCTION, $this->stringBuffer);
                        $this->stringBuffer = '';
                    } elseif (strlen($this->numberBuffer) > 0) {
                        $this->emptyNumberBufferAsLiteral();
                        $this->tokens[] = new Token(Token::OPERATOR, '*');
                    }
                    $this->allowNegative = true;
                    $this->tokens[] = new Token(Token::LEFT_PARENTHESIS, '');

                    break;

                case $this->isRightParenthesis($ch) || $ch === ']':
                    $this->emptyNumberBufferAsLiteral();
                    $this->emptyStringBufferAsVariable();
                    $this->allowNegative = false;
                    $this->tokens[] = new Token(Token::RIGHT_PARENTHESIS, '');

                    break;

                case $this->isComma($ch):
                    $this->emptyNumberBufferAsLiteral();
                    $this->emptyStringBufferAsVariable();
                    $this->allowNegative = true;
                    $this->tokens[] = new Token(Token::PARAM_SEPARATOR, '');

                    break;

                default:
                    // special case for unary operations
                    if ($ch == '-' || $ch == '+') {
                        if ($this->allowNegative) {
                            $this->allowNegative = false;
                            $this->tokens[] = new Token(Token::OPERATOR, $ch == '-' ? 'uNeg' : 'uPos');

                            continue 2;
                        }
                        // could be in exponent, in which case negative
                        // should be added to the number buffer
                        if ($this->numberBuffer && 'e' == $this->numberBuffer[strlen($this->numberBuffer) - 1]) {
                            $this->numberBuffer .= $ch;

                            continue 2;
                        }
                    }
                    $this->emptyNumberBufferAsLiteral();
                    $this->emptyStringBufferAsVariable();

                    if ($ch != '$') {
                        if (count($this->tokens) > 0) {
                            if (Token::OPERATOR === $this->tokens[count($this->tokens) - 1]->getType()) {
                                $token = $this->tokens[count($this->tokens) - 1];
                                $token->setValue($token->getValue() . $ch);
                            } else {
                                $this->tokens[] = new Token(Token::OPERATOR, $ch);
                            }
                        } else {
                            $this->tokens[] = new Token(Token::OPERATOR, $ch);
                        }
                    }
                    $this->allowNegative = true;
            }
        }
        $this->emptyNumberBufferAsLiteral();
        $this->emptyStringBufferAsVariable();

        return $this;
    }

    /**
     * Build the reverse polish notation
     * @return array<Token>
     */
    public function buildReversePolishNotation(): array
    {
        $tokens  = [];
        /** @var SplStack<Token> $stack */
        $stack = new SplStack();

        /** @var SplStack<Int> $paramCounter */
        $paramCounter = new SplStack();

        foreach ($this->tokens as $token) {
            switch ($token->getType()) {
                case Token::LITERAL:
                case Token::VARIABLE:
                case Token::STRING:
                    $tokens[] = $token;

                    if ($paramCounter->count() > 0 && $paramCounter->top() === 0) {
                        $paramCounter->push($paramCounter->top() + 1);
                    }

                    break;

                case Token::FUNCTION:
                    if ($paramCounter->count() > 0 && $paramCounter->top() === 0) {
                        $paramCounter->push($paramCounter->top() + 1);
                    }
                    $stack->push($token);
                    $paramCounter->push(0);

                    break;

                case Token::LEFT_PARENTHESIS:
                    $stack->push($token);

                    break;

                case Token::PARAM_SEPARATOR:
                    while (Token::LEFT_PARENTHESIS !== $stack->top()->getType()) {
                        if ($stack->count() === 0) {
                            throw new IncorrectBracketsException('Incorrect brackets');
                        }
                        $tokens[] = $stack->pop();
                    }
                    $paramCounter->push($paramCounter->top() + 1);
                    break;

                case Token::OPERATOR:
                    if (!array_key_exists($token->getValue(), $this->operators)) {
                        throw new UnknownOperatorException(sprintf(
                            'Unknown operator [%s]',
                            $token->getValue()
                        ));
                    }
                    $op1 = $this->operators[$token->getValue()];
                    while ($stack->count() > 0 && Token::OPERATOR === $stack->top()->getType()) {
                        if (!array_key_exists($stack->top()->getValue(), $this->operators)) {
                            throw new UnknownOperatorException(sprintf(
                                'Unknown operator [%s]',
                                $stack->top()->getValue()
                            ));
                        }
                        $op2 = $this->operators[$stack->top()->getValue()];
                        if ($op2->getPriority() >= $op1->getPriority()) {
                            $tokens[] = $stack->pop();

                            continue;
                        }

                        break;
                    }
                    $stack->push($token);
                    break;

                case Token::RIGHT_PARENTHESIS:
                    while (true) {
                        try {
                            $ctoken = $stack->pop();
                            if (Token::LEFT_PARENTHESIS === $ctoken->getType()) {
                                break;
                            }
                            $tokens[] = $ctoken;
                        } catch (RuntimeException $ex) {
                            throw new IncorrectBracketsException('Incorrect brackets');
                        }
                    }
                    if ($stack->count() > 0 && Token::FUNCTION === $stack->top()->getType()) {
                        /** @var Token $funcToken */
                        $funcToken = $stack->pop();
                        $funcToken->setParamCount($paramCounter->pop());
                        $tokens[] = $funcToken;
                    }
                    break;
                case Token::SPACE:
                    // do nothing
            }
        }

        while ($stack->count() !== 0) {
            if (
                Token::LEFT_PARENTHESIS === $stack->top()->getType()
                || Token::RIGHT_PARENTHESIS === $stack->top()->getType()
            ) {
                throw new IncorrectBracketsException('Incorrect brackets');
            }

            if (Token::SPACE === $stack->top()->getType()) {
                $stack->pop();

                continue;
            }
            $tokens[] = $stack->pop();
        }

        return $tokens;
    }

    /**
     * Put the current number buffer content to token
     * as literal
     * @return void
     */
    protected function emptyNumberBufferAsLiteral(): void
    {
        if (strlen($this->numberBuffer) > 0) {
            $this->tokens[] = new Token(Token::LITERAL, $this->numberBuffer);
            $this->numberBuffer = '';
        }
    }

    /**
     * Put the current string buffer content to token
     * as variable
     * @return void
     */
    protected function emptyStringBufferAsVariable(): void
    {
        if (strlen($this->stringBuffer) > 0) {
            $this->tokens[] = new Token(Token::VARIABLE, $this->stringBuffer);
            $this->stringBuffer = '';
        }
    }

    /**
     * Whether the give argument is number
     * @param string $chr
     * @return bool
     */
    protected function isNumber(string $chr): bool
    {
        return $chr >= '0' && $chr <= '9';
    }

    /**
     * Whether the give argument is alphabetic
     * @param string $chr
     * @return bool
     */
    protected function isAlpha(string $chr): bool
    {
        return $chr >= 'a' && $chr <= 'z' || $chr >= 'A' && $chr <= 'Z' || $chr == '_';
    }

    /**
     * Whether the give argument is dot
     * @param string $chr
     * @return bool
     */
    protected function isDot(string $chr): bool
    {
        return $chr == '.';
    }

    /**
     * Whether the give argument is left parenthesis
     * @param string $chr
     * @return bool
     */
    protected function isLeftParenthesis(string $chr): bool
    {
        return $chr == '(';
    }

    /**
     * Whether the give argument is right parenthesis
     * @param string $chr
     * @return bool
     */
    protected function isRightParenthesis(string $chr): bool
    {
        return $chr == ')';
    }

    /**
     * Whether the give argument is comma
     * @param string $chr
     * @return bool
     */
    protected function isComma(string $chr): bool
    {
        return $chr == ',';
    }
}
