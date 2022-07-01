<?php

declare(strict_types=1);

namespace Platine\Test\Expression;

use Platine\Dev\PlatineTestCase;
use Platine\Expression\Exception\IncorrectExpressionException;
use Platine\Expression\Operator;
use Platine\Expression\Token;

/**
 * Operator class tests
 *
 * @group core
 * @group workflow
 */
class OperatorTest extends PlatineTestCase
{
    public function testConstructor(): void
    {
        $o = new Operator('=', true, 100, 'strlen');
        $this->assertEquals(1, $o->getPlaces());
        $this->assertEquals('=', $o->getOperator());
        $this->assertEquals(100, $o->getPriority());
        $this->assertEquals('strlen', $o->getFunction());
        $this->assertTrue($o->isRightAssociative());
    }

    public function testExecuteWrongParameterCount(): void
    {
        $o = new Operator('=', true, 100, 'strlen');
        $stack = [];

        $this->expectException(IncorrectExpressionException::class);
        $o->execute($stack);
    }

    public function testExecuteSuccess(): void
    {
        $o = new Operator('+', true, 100, function ($a, $b) {
            return $a + $b;
        });
        $stack = [
            new Token(Token::LITERAL, 15, 'a'),
            new Token(Token::LITERAL, 1, 'b'),
        ];

        $token = $o->execute($stack);
        $this->assertEquals(16, $token->getValue());
    }
}
