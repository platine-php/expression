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
 *  @link   http://www.iacademy.cf
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
     * The token type
     * @var string
     */
    protected string $type = self::LITERAL;

    /**
     * The token value
     * @var mixed
     */
    protected $value;

    /**
     * The token name
     * @var string|null
     */
    protected ?string $name;

    /**
     * The function number of parameter
     * @var int
     */
    protected int $paramCount = 0;

    /**
     * Create new instance
     * @param string $type
     * @param mixed $value
     * @param string|null $name
     */
    public function __construct(string $type, $value, ?string $name = null)
    {
        $this->name = $name;
        $this->value = $value;
        $this->type = $type;
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
    public function getValue()
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
    public function setValue($value): self
    {
        $this->value = $value;
        return $this;
    }
}
