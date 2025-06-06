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
 * @file Token.php
 *
 * The Token class
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

/**
 * @class Token
 * @package Platine\Expression
 */
class Token
{
    /**
     * Constants
     */
    public const LITERAL = 'literal';
    public const VARIABLE = 'variable';
    public const OPERATOR = 'operator';
    public const LEFT_PARENTHESIS = 'LP';
    public const RIGHT_PARENTHESIS = 'RP';
    public const FUNCTION = 'function';
    public const PARAM_SEPARATOR = 'PS';
    public const STRING = 'string';
    public const SPACE = 'space';

    /**
     * The function number of parameter
     * @var int
     */
    protected int $paramCount = 0;

    /**
     * Create new instance
     * @param string $type The token type
     * @param mixed $value The token value
     * @param string|null $name The token name
     */
    public function __construct(
        protected string $type,
        protected mixed $value,
        protected ?string $name = null
    ) {
    }

    /**
     * Return the token type
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Return the token value
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Return the token name
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Return the number of function parameter count
     * @return int
     */
    public function getParamCount(): int
    {
        return $this->paramCount;
    }

    /**
     * Set the parameter count
     * @param int $paramCount
     * @return $this
     */
    public function setParamCount(int $paramCount): self
    {
        $this->paramCount = $paramCount;
        return $this;
    }

    /**
     * Set the token value
     * @param mixed $value
     * @return $this
     */
    public function setValue(mixed $value): self
    {
        $this->value = $value;
        return $this;
    }
}
