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
 * @file Operator.php
 *
 * The Operator class
 *
 *  @package    Platine\Expression
 *  @author Platine Developers Team
 *  @copyright  Copyright (c) 2020
 *  @license    http://opensource.org/licenses/MIT  MIT License
 *  @link   https://www.platine-php.com
 *  @version 1.0.0
 *  @filesource
 */
declare(strict_types=1);

namespace Platine\Expression;

use Closure;
use Platine\Expression\Exception\IncorrectExpressionException;
use ReflectionFunction;

/**
 * @class Operator
 * @package Platine\Expression
 */
class Operator
{
    /**
     * Number of function argument
     * @var int
     */
    protected int $places = 0;

    /**
     * Create new instance
     * @param string $operator The operator like =, >=, ..
     * @param bool $isRightAssociative Whether the operator is or right associativity
     * @param int $priority The priority of the operator
     * @param callable $function The function to be called
     */
    public function __construct(
        protected string $operator,
        protected bool $isRightAssociative,
        protected int $priority,
        protected $function
    ) {
        $this->operator = $operator;
        $this->isRightAssociative = $isRightAssociative;
        $this->priority = $priority;
        $this->function = $function;

        $reflection = new ReflectionFunction(Closure::fromCallable($function));
        $this->places = $reflection->getNumberOfParameters();
    }

    /**
     * Execute the expression for this operator
     * @param Token[] $stack
     * @return Token
     */
    public function execute(array &$stack): Token
    {
        if (count($stack) < $this->places) {
            throw new IncorrectExpressionException(sprintf(
                'Incorrect number of function parameters, [%d] needed',
                $this->places
            ));
        }

        $args = [];
        for ($i = 0; $i < $this->places; $i++) {
            $token = array_pop($stack);
            if ($token !== null) {
                array_unshift($args, $token->getValue());
            }
        }

        $result = call_user_func_array($this->function, $args);

        return new Token(Token::LITERAL, $result);
    }

    /**
     * Return the operator
     * @return string
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * Whether is right associative
     * @return bool
     */
    public function isRightAssociative(): bool
    {
        return $this->isRightAssociative;
    }

    /**
     * Return the priority
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Return the function
     * @return callable
     */
    public function getFunction(): callable
    {
        return $this->function;
    }

    /**
     * Return the number of parameters
     * @return int
     */
    public function getPlaces(): int
    {
        return $this->places;
    }
}
