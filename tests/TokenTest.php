<?php

declare(strict_types=1);

namespace Platine\Test\Expression;

use Platine\Dev\PlatineTestCase;
use Platine\Expression\Token;

/**
 * Token class tests
 *
 * @group core
 * @group expression
 */
class TokenTest extends PlatineTestCase
{
    public function testConstructor(): void
    {
        $t = new Token(Token::OPERATOR, 1, 'tnh');
        $this->assertEquals(Token::OPERATOR, $t->getType());
        $this->assertEquals('tnh', $t->getName());
        $this->assertEquals(1, $t->getValue());
    }
}
