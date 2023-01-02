<?php

declare(strict_types=1);

namespace Platine\Test\Expression;

use Platine\Dev\PlatineTestCase;
use Platine\Expression\Calculator;
use Platine\Expression\CustomFunction;
use Platine\Expression\Exception\IncorrectExpressionException;
use Platine\Expression\Exception\UnknownFunctionException;
use Platine\Expression\Exception\UnknownOperatorException;
use Platine\Expression\Exception\UnknownVariableException;
use Platine\Expression\Operator;
use Platine\Expression\Token;

/**
 * Calculator class tests
 *
 * @group core
 * @group expression
 */
class CalculatorTest extends PlatineTestCase
{
    public function testConstructor(): void
    {
        $o = new Calculator([], []);
        $this->assertEmpty($o->getFunctions());
        $this->assertEmpty($o->getOperators());
    }

    public function testConstructorWithArgs(): void
    {
        $o = new Calculator(
            [
                'strlen' => new CustomFunction('strlen', 'strlen')
            ],
            [
                '+' => new Operator('+', true, 100, function ($a, $b) {
                    return $a + $b;
                })
            ]
        );
        $this->assertCount(1, $o->getFunctions());
        $this->assertCount(1, $o->getOperators());
    }

    public function testCalculateLiteral(): void
    {
        $o = new Calculator(
            [
                'strlen' => new CustomFunction('strlen', 'strlen')
            ],
            [
                '+' => new Operator('+', true, 100, function ($a, $b) {
                    return $a + $b;
                })
            ]
        );
        $tokens = [
            new Token(Token::LITERAL, 17, 'tnh')
        ];
        $variables = [];
        $result = $o->calculate($tokens, $variables, $onVariableNotFound = null);
        $this->assertEquals(17, $result);
    }

    public function testCalculateVariable(): void
    {
        $o = new Calculator(
            [
                'strlen' => new CustomFunction('strlen', 'strlen')
            ],
            [
                '+' => new Operator('+', true, 100, function ($a, $b) {
                    return $a + $b;
                })
            ]
        );
        $tokens = [
            new Token(Token::VARIABLE, 'name', 'tnh')
        ];
        $variables = [
            'name' => 'Tony'
        ];
        $result = $o->calculate($tokens, $variables, $onVariableNotFound = null);
        $this->assertEquals('Tony', $result);
    }

    public function testCalculateVariableNotFoundUsingCallback(): void
    {
        $o = new Calculator(
            [
                'strlen' => new CustomFunction('strlen', 'strlen')
            ],
            [
                '+' => new Operator('+', true, 100, function ($a, $b) {
                    return $a + $b;
                })
            ]
        );
        $tokens = [
            new Token(Token::VARIABLE, 'foo', 'tnh')
        ];
        $variables = [
            'name' => 'Tony'
        ];
        $result = $o->calculate($tokens, $variables, function ($var) {
            return 109;
        });
        $this->assertEquals(109, $result);
    }

    public function testCalculateVariableNotFound(): void
    {
        $o = new Calculator(
            [
                'strlen' => new CustomFunction('strlen', 'strlen')
            ],
            [
                '+' => new Operator('+', true, 100, function ($a, $b) {
                    return $a + $b;
                })
            ]
        );
        $tokens = [
            new Token(Token::VARIABLE, 'foo', 'tnh')
        ];
        $variables = [
            'name' => 'Tony'
        ];
        $this->expectException(UnknownVariableException::class);
        $o->calculate($tokens, $variables, null);
    }

    public function testCalculateFunction(): void
    {
        $o = new Calculator(
            [
                'strlen' => new CustomFunction('strlen', 'strlen')
            ],
            [
                '+' => new Operator('+', true, 100, function ($a, $b) {
                    return $a + $b;
                })
            ]
        );
        $tokens = [
            new Token(Token::LITERAL, 'Tony', 'tnh'),
            (new Token(Token::FUNCTION, 'strlen', 'tnh'))->setParamCount(1)
        ];
        $variables = [];
        $result = $o->calculate($tokens, $variables, null);
        $this->assertEquals(4, $result);
    }

    public function testCalculateFunctionNotFound(): void
    {
        $o = new Calculator(
            [
                'strlen' => new CustomFunction('strlen', 'strlen')
            ],
            [
                '+' => new Operator('+', true, 100, function ($a, $b) {
                    return $a + $b;
                })
            ]
        );
        $tokens = [
            new Token(Token::FUNCTION, 'foo', 'tnh')
        ];
        $variables = [
            'name' => 'Tony'
        ];
        $this->expectException(UnknownFunctionException::class);
        $o->calculate($tokens, $variables, null);
    }

    public function testCalculateOperatorNotFound(): void
    {
        $o = new Calculator(
            [
                'strlen' => new CustomFunction('strlen', 'strlen')
            ],
            [
                '+' => new Operator('+', true, 100, function ($a, $b) {
                    return $a + $b;
                })
            ]
        );
        $tokens = [
            new Token(Token::OPERATOR, 'foo', 'tnh')
        ];
        $variables = [];
        $this->expectException(UnknownOperatorException::class);
        $o->calculate($tokens, $variables, null);
    }

    public function testCalculateOperator(): void
    {
        $o = new Calculator(
            [
                'strlen' => new CustomFunction('strlen', 'strlen')
            ],
            [
                '+' => new Operator('+', true, 100, function ($a, $b) {
                    return $a + $b;
                })
            ]
        );
        $tokens = [
            new Token(Token::LITERAL, 16, 'tnh'),
            new Token(Token::LITERAL, 3, 'tnh'),
            new Token(Token::OPERATOR, '+', 'plus'),
        ];
        $variables = [];
        $result = $o->calculate($tokens, $variables, null);
        $this->assertEquals(19, $result);
    }

    public function testCalculateIncorrectExpression(): void
    {
        $o = new Calculator(
            [
                'strlen' => new CustomFunction('strlen', 'strlen')
            ],
            [
                '+' => new Operator('+', true, 100, function ($a, $b) {
                    return $a + $b;
                })
            ]
        );
        $tokens = [
            new Token(Token::LITERAL, 16, 'tnh'),
            new Token(Token::LITERAL, 3, 'tnh'),
            new Token(Token::LITERAL, 8, 'tnh'),
            new Token(Token::OPERATOR, '+', 'plus'),
        ];
        $variables = [];
        $this->expectException(IncorrectExpressionException::class);
        $o->calculate($tokens, $variables, null);
    }
}
