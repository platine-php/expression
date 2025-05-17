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
 * @file Executor.php
 *
 * The Executor class
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

use InvalidArgumentException;
use Platine\Expression\Exception\DivisionByZeroException;
use Platine\Expression\Exception\UnknownVariableException;

/**
 * @class Executor
 * @package Platine\Expression
 */
class Executor
{
    /**
     * The variable list
     * @var array<string, mixed>
     */
    protected array $variables = [];

    /**
     * The callable that will be called if variable not found
     * @var callable|null
     */
    protected $variableNotFoundHandler = null;

    /**
     * The callable that will be called for variable validation
     * @var callable|null
     */
    protected $variableValidationHandler = null;

    /**
     * The list of operators
     * @var array<string, Operator>
     */
    protected array $operators = [];

    /**
     * The list of functions
     * @var array<string, CustomFunction>
     */
    protected array $functions = [];

    /**
     * The list of cache
     * @var array<string, Token[]>
     */
    protected array $caches = [];

    /**
     * Create new instance
     */
    public function __construct()
    {
        $this->addDefaults();
    }

    /**
     * When do clone of this object
     */
    public function __clone()
    {
        $this->addDefaults();
    }

    /**
     * Execute the expression and return the result
     * @param string $expression
     * @param bool $cache
     * @return mixed
     */
    public function execute(string $expression, bool $cache = true): mixed
    {
        $cacheKey = $expression;
        if (!array_key_exists($cacheKey, $this->caches)) {
            $tokens = (new Tokenizer($expression, $this->operators))
                       ->tokenize()
                       ->buildReversePolishNotation();

            if ($cache) {
                $this->caches[$cacheKey] = $tokens;
            }
        } else {
            $tokens = $this->caches[$cacheKey];
        }

        $calculator = new Calculator($this->functions, $this->operators);

        return $calculator->calculate(
            $tokens,
            $this->variables,
            $this->variableNotFoundHandler
        );
    }

    /**
     * Add new operator
     * @param Operator $operator
     * @return $this
     */
    public function addOperator(Operator $operator): self
    {
        $this->operators[$operator->getOperator()] = $operator;
        return $this;
    }

    /**
     * Add new function
     * @param string $name
     * @param callable $function
     * @return $this
     */
    public function addFunction(string $name, callable $function): self
    {
        $this->functions[$name] = new CustomFunction($name, $function);
        return $this;
    }

    /**
     * Return the list of variables
     * @return array<string, int|float>
     */
    public function getVariables(): array
    {
        return $this->variables;
    }

    /**
     * Return the value for the given variable name
     * @param string $name
     * @return mixed
     */
    public function getVariable(string $name): mixed
    {
        if (! array_key_exists($name, $this->variables)) {
            if ($this->variableNotFoundHandler !== null) {
                return call_user_func($this->variableNotFoundHandler, $name);
            }

            throw new UnknownVariableException(sprintf(
                'Unknown variable [%s]',
                $name
            ));
        }

        return $this->variables[$name];
    }

    /**
     * Set the variable to be used later
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function setVariable(string $name, mixed $value): self
    {
        if ($this->variableValidationHandler !== null) {
            call_user_func($this->variableValidationHandler, $name, $value);
        }
        $this->variables[$name] = $value;

        return $this;
    }

    /**
     * Set the variables using array
     * @param array<string, mixed> $variables
     * @param bool $clear whether to clear all existing variables
     * @return $this
     */
    public function setVariables(array $variables, bool $clear = true): self
    {
        if ($clear) {
            $this->clearVariables();
        }

        foreach ($variables as $name => $value) {
            $this->setVariable($name, $value);
        }

        return $this;
    }

    /**
     * Check whether the given variable exists
     * @param string $name
     * @return bool
     */
    public function variableExist(string $name): bool
    {
        return array_key_exists($name, $this->variables);
    }

    /**
     * Remove the given variable
     * @param string $name
     * @return $this
     */
    public function removeVariable(string $name): self
    {
        unset($this->variables[$name]);

        return $this;
    }

    /**
     * Remove the given operator
     * @param string $name
     * @return $this
     */
    public function removeOperator(string $name): self
    {
        unset($this->operators[$name]);

        return $this;
    }

    /**
     * Clear all variables
     * @return $this
     */
    public function clearVariables(): self
    {
        $this->variables = [];
        $this->variableNotFoundHandler = null;

        return $this;
    }

    /**
     * Set the callable to be used for variable not found
     * @param callable $handler
     * @return $this
     */
    public function setVariableNotFoundHandler(callable $handler): self
    {
        $this->variableNotFoundHandler = $handler;
        return $this;
    }

    /**
     * Set the callable to be used for variable validation
     * @param callable $handler
     * @return $this
     */
    public function setVariableValidationHandler(callable $handler): self
    {
        $this->variableValidationHandler = $handler;
        return $this;
    }

    /**
     * Return the variable not found handler
     * @return callable|null
     */
    public function getVariableNotFoundHandler(): ?callable
    {
        return $this->variableNotFoundHandler;
    }

    /**
     * Return the variable validation handler
     * @return callable|null
     */
    public function getVariableValidationHandler(): ?callable
    {
        return $this->variableValidationHandler;
    }

    /**
     * Return the list of caches
     * @return array<string, Token[]>
     */
    public function getCaches(): array
    {
        return $this->caches;
    }


    /**
     * Return the list of operators
     * @return array<string, Operator>
     */
    public function getOperators(): array
    {
        return $this->operators;
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
     * Add the default values like variables, operators, functions
     * @return $this
     */
    protected function addDefaults(): self
    {
        foreach ($this->defaultOperators() as $name => $operator) {
            [$callable, $priority, $isRightAssociative] = $operator;
            $this->addOperator(new Operator($name, $isRightAssociative, $priority, $callable));
        }

        foreach ($this->defaultFunctions() as $name => $callable) {
            $this->addFunction($name, $callable);
        }

        $this->variables = $this->defaultVariables();

        return $this;
    }

    /**
     * Return the list of default operators
     * @return array<string, array{callable, int, bool}>
     */
    protected function defaultOperators(): array
    {
        return [
            '+' => [static fn($a, $b) => $a + $b, 170, false],
            '-' => [static fn($a, $b) => $a - $b, 170, false],
            // unary positive token
            'uPos' => [static fn($a) => $a, 200, false],
            // unary minus token
            'uNeg' => [static fn($a) => 0 - $a, 200, false],
            '*' => [static fn($a, $b) => $a * $b, 180, false],
            '/' => [
                static function ($a, $b) {
                    if ($b == 0) {
                        throw new DivisionByZeroException();
                    }

                    return $a / $b;
                },
                180,
                false
            ],
            '^' => [static fn($a, $b) => pow($a, $b), 220, true],
            '%' => [static fn($a, $b) => $a % $b, 180, false],
            '&&' => [static fn($a, $b) => $a && $b, 100, false],
            '||' => [static fn($a, $b) => $a || $b, 90, false],
            '==' => [static fn($a, $b) => is_string($a) || is_string($b) ? strcmp($a, $b) == 0 : $a == $b, 140, false],
            '!=' => [static fn($a, $b) => is_string($a) || is_string($b) ? strcmp($a, $b) != 0 : $a != $b, 140, false],
            '>=' => [static fn($a, $b) => $a >= $b, 150, false],
            '>' => [static fn($a, $b) => $a > $b, 150, false],
            '<=' => [static fn($a, $b) => $a <= $b, 150, false],
            '<' => [static fn($a, $b) => $a < $b, 150, false],
        ];
    }

    /**
     * Return the list of default functions
     * @return array<string, callable>
     */
    protected function defaultFunctions(): array
    {
        return [
            'abs' => static function ($arg) {
                if ((int) $arg == $arg) {
                    return abs(intval($arg));
                }
                return abs(floatval($arg));
            },
            'array' => static fn(...$args) => $args,
            'avg' => static function ($arg1, ...$args) {
                if (is_array($arg1)) {
                    if (count($arg1) === 0) {
                        throw new InvalidArgumentException('Array must contains at least one element');
                    }

                    return array_sum($arg1) / count($arg1);
                }

                $args = [$arg1, ...array_values($args)];
                return array_sum($args) / count($args);
            },
            'ceil' => static function ($arg) {
                if ((int) $arg == $arg) {
                    return ceil(intval($arg));
                }
                return ceil(floatval($arg));
            },
            'floor' => static function ($arg) {
                if ((int) $arg == $arg) {
                    return floor(intval($arg));
                }
                return floor(floatval($arg));
            },
            'exp' => static fn($arg) => exp(floatval($arg)),
            'ln' => static fn($arg) => log(floatval($arg)),
            'lg' => static fn($arg) => log10($arg),
            'log' => static fn($arg) => log(floatval($arg)),
            'log10' => static fn($arg) => log10(floatval($arg)),
            'log1p' => static fn($arg) => log1p(floatval($arg)),
            'fmod' => static fn($arg1, $arg2) => fmod(floatval($arg1), floatval($arg2)),
            'sqrt' => static fn($arg) => sqrt(floatval($arg)),
            'hypot' => static fn($arg1, $arg2) => hypot(floatval($arg1), floatval($arg2)),
            'intdiv' => static fn($arg1, $arg2) => intdiv(intval($arg1), intval($arg2)),
            'max' => static function ($arg1, ...$args) {
                if (is_array($arg1) && count($arg1) === 0) {
                    throw new InvalidArgumentException('Array must contains at least one element');
                }

                return max(is_array($arg1) && count($arg1) > 0 ? $arg1 : [$arg1, ...array_values($args)]);
            },
            'min' => static function ($arg1, ...$args) {
                if (is_array($arg1) && count($arg1) === 0) {
                    throw new InvalidArgumentException('Array must contains at least one element');
                }

                return min(is_array($arg1) && count($arg1) > 0  ? $arg1 : [$arg1, ...array_values($args)]);
            },
            'pow' => static fn($arg1, $arg2) => $arg1 ** $arg2,
            'round' => static function ($arg, int $precision = 0) {
                if ((int) $arg == $arg) {
                    return round(intval($arg), intval($precision));
                }
                return round(floatval($arg), intval($precision));
            },
            'pi' => static fn() => M_PI,
        ];
    }

    /**
     * Return the default variables
     * @return array<string, mixed>
     */
    protected function defaultVariables(): array
    {
        return [
          'pi' => 3.14159265359,
          'e' => 2.71828182846
        ];
    }
}
