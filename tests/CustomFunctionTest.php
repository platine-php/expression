<?php

declare(strict_types=1);

namespace Platine\Test\Expression;

use Platine\Dev\PlatineTestCase;
use Platine\Expression\CustomFunction;
use Platine\Expression\Exception\IncorrectNumberOfFunctionParametersException;
use Platine\Expression\Token;

/**
 * CustomFunction class tests
 *
 * @group core
 * @group expression
 */
class CustomFunctionTest extends PlatineTestCase
{
    public function testConstructor(): void
    {
        $o = new CustomFunction('tnh', 'strlen');
        $this->assertEquals(1, $o->getRequiredParamCount());
        $this->assertEquals('tnh', $o->getName());
        $this->assertEquals('strlen', $o->getFunction());
    }

    public function testExecuteWrongParameterCount(): void
    {
        $o = new CustomFunction('tnh', 'strlen');
        $stack = [];

        $this->expectException(IncorrectNumberOfFunctionParametersException::class);
        $o->execute($stack, 0);
    }

    public function testExecuteSuccess(): void
    {
        $o = new CustomFunction('tnh', function ($a, $b) {
            return $a + $b;
        });
        $stack = [
            new Token(Token::LITERAL, 15, 'a'),
            new Token(Token::LITERAL, 1, 'b'),
        ];

        $token = $o->execute($stack, 2);
        $this->assertEquals(16, $token->getValue());
    }
}
