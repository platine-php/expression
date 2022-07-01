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
 * @file Calculator.php
 *
 * The Calculator class
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

use Platine\Expression\Exception\IncorrectExpressionException;
use Platine\Expression\Exception\UnknownFunctionException;
use Platine\Expression\Exception\UnknownOperatorException;
use Platine\Expression\Exception\UnknownVariableException;
use Platine\Expression\Token;

/**
 * @class Calculator
 * @package Platine\Expression
 */
class Calculator
{
    /**
     * The list of functions
     * @var array<string, CustomFunction>
     */
    protected array $functions = [];

    /**
     * The list of operators
     * @var array<string, Operator>
     */
    protected array $operators = [];

    /**
     * Create new instance
     * @param array<string, CustomFunction> $functions
     * @param array<string, Operator> $operators
     */
    public function __construct(array $functions, array $operators)
    {
        $this->functions = $functions;
        $this->operators = $operators;
    }

    /**
     * Calculate array of tokens in reverse polish notation
     * @param array<Token> $tokens
     * @param array<string, float|string> $variables
     * @param callable|null $variableNotFoundHandler
     * @return int|float|string|null
     */
    public function calculate(
        array $tokens,
        array $variables,
        ?callable $variableNotFoundHandler = null
    ) {
        /** @var array<Token> $stack */
        $stack = [];
        foreach ($tokens as $token) {
            if (Token::LITERAL === $token->getType() || Token::STRING === $token->getType()) {
                $stack[] = $token;
            } elseif (Token::VARIABLE === $token->getType()) {
                $variable = $token->getValue();
                $value = null;
                if (array_key_exists($variable, $variables)) {
                    $value = $variables[$variable];
                } elseif ($variableNotFoundHandler) {
                    $value = call_user_func($variableNotFoundHandler, $variable);
                } else {
                    throw new UnknownVariableException(sprintf(
                        'Unknown variable [%s]',
                        $variable
                    ));
                }
                $stack[] = new Token(Token::LITERAL, $value, $variable);
            } elseif (Token::FUNCTION === $token->getType()) {
                if (! array_key_exists($token->getValue(), $this->functions)) {
                    throw new UnknownFunctionException(sprintf(
                        'Unknown function [%s]',
                        $token->getValue()
                    ));
                }
                $stack[] = $this->functions[$token->getValue()]
                                        ->execute($stack, $token->getParamCount());
            } elseif (Token::OPERATOR === $token->getType()) {
                if (! array_key_exists($token->getValue(), $this->operators)) {
                    throw new UnknownOperatorException(sprintf(
                        'Unknown operator [%s]',
                        $token->getValue()
                    ));
                }
                $stack[] = $this->operators[$token->getValue()]->execute($stack);
            }
        }
        $result = array_pop($stack);
        if ($result === null || ! empty($stack)) {
            throw new IncorrectExpressionException('Expression stack is not empty');
        }

        return $result->getValue();
    }

    /**
     * Return the list of functions
     * @return array<string, CustomFunction>
     */
    public function getFunctions(): array
    {
        return $this->functions;
    }

    /**
     * Return the list of operators
     * @return array<string, Operator>
     */
    public function getOperators(): array
    {
        return $this->operators;
    }
}
