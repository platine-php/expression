<?php

declare(strict_types=1);

namespace Platine\Test\Expression;

use Exception;
use InvalidArgumentException;
use Platine\Dev\PlatineTestCase;
use Platine\Expression\Exception\DivisionByZeroException;
use Platine\Expression\Exception\ExpressionException;
use Platine\Expression\Exception\IncorrectBracketsException;
use Platine\Expression\Exception\IncorrectExpressionException;
use Platine\Expression\Exception\UnknownFunctionException;
use Platine\Expression\Exception\UnknownOperatorException;
use Platine\Expression\Exception\UnknownVariableException;
use Platine\Expression\Executor;

/**
 * Executor class tests
 *
 * @group core
 * @group expression
 */
class ExecutorTest extends PlatineTestCase
{
    public function testInvalidFunctionBracketRight(): void
    {
        $o = new Executor();

        $this->expectException(IncorrectBracketsException::class);
        $o->execute('max)2,');
    }

    public function testDefaults(): void
    {
        $o = new Executor();
        $this->assertCount(20, $o->getFunctions());
        $this->assertCount(17, $o->getOperators());
        $this->assertCount(0, $o->getCaches());

        $o->removeOperator('+');
        $this->assertCount(16, $o->getOperators());
    }

    public function testVariables(): void
    {
        $o = new Executor();
        $this->assertNull($o->getVariableNotFoundHandler());
        $this->assertNull($o->getVariableValidationHandler());

        $o->setVariable('a', 13);
        $this->assertEquals(13, $o->getVariable('a'));


        $o->setVariableNotFoundHandler(fn($name) => sprintf('%s.%d', $name, 125));
        $this->assertIsCallable($o->getVariableNotFoundHandler());
        $this->assertEquals('c.125', $o->getVariable('c'));

        $o->removeVariable('a');
        $this->assertCount(2, $o->getVariables()); // we have default variables
    }

    public function testInvalidOperator(): void
    {
        $o = new Executor();

        $this->expectException(UnknownOperatorException::class);
        $o->execute('2 | 4');
    }

    public function testInvalidBrackets(): void
    {
        $o = new Executor();

        $this->expectException(IncorrectBracketsException::class);
        $o->execute('max(2,4');
    }

    public function testSetVariableWithValidation(): void
    {
        $o = new Executor();
        $o->setVariableValidationHandler(function ($name, $value) {
            if ($name === 'c') {
                throw new ExpressionException();
            }
        });

        $this->expectException(ExpressionException::class);
        $o->setVariable('c', 13);
    }

    public function testGetVariableNotFound(): void
    {
        $o = new Executor();

        $this->expectException(UnknownVariableException::class);
        $this->assertNull($o->getVariable('b'));
    }


    public function testClone(): void
    {
        $o = new Executor();
        $oc = clone $o;
        $this->assertEquals($o, $oc);
    }

    public function testAvg(): void
    {
        $o = new Executor();
        $this->assertEquals(4.25, $o->execute('avg([2, 4, 5, 6])'));
        $this->assertEquals(4.4, $o->execute('avg(5, 2, 4, 5, 6)'));
    }

    public function testAvgEmptyArray(): void
    {
        $o = new Executor();
        $this->expectException(InvalidArgumentException::class);
        $o->execute('avg([])');
    }

    public function testMaxEmptyArray(): void
    {
        $o = new Executor();
        $this->expectException(InvalidArgumentException::class);
        $o->execute('max([])');
    }

    public function testMinEmptyArray(): void
    {
        $o = new Executor();
        $this->expectException(InvalidArgumentException::class);
        $o->execute('min([])');
    }


    public function testUnknownFunctionException(): void
    {
        $o = new Executor();
        $this->expectException(UnknownFunctionException::class);
        $o->execute('1 * tnh("foobar") - 3');
    }

    public function testZeroDivision(): void
    {
        $o = new Executor();
        $this->expectException(DivisionByZeroException::class);
        $o->execute('10 / 0');
    }

    public function testUnaryOperators(): void
    {
        $o = new Executor();
        $this->assertEquals(5, $o->execute('+5'));
        $this->assertEquals(5, $o->execute('+(3+2)'));
        $this->assertEquals(-5, $o->execute('-5'));
        $this->assertEquals(5, $o->execute('-(-5)'));
        $this->assertEquals(-5, $o->execute('+(-5)'));
        $this->assertEquals(-5, $o->execute('-(3+2)'));
    }

    public function testVariableIncorrectExpressionException(): void
    {
        $o = new Executor();
        $o->setVariable('four', 4);
        $this->assertCount(3, $o->getVariables()); // with default variable pi and e
        $this->assertTrue($o->variableExist('four'));
        $this->assertEquals(4, $o->getVariable('four'));
        $this->assertEquals(4, $o->execute('$four'));
        $this->expectException(IncorrectExpressionException::class);
        $this->assertEquals(0.0, $o->execute('$'));
        $this->assertEquals(0.0, $o->execute('$ + $four'));
    }

    public function testExponentiation(): void
    {
        $o = new Executor();
        $this->assertEquals(100, $o->execute('10 ^ 2'));
    }

    public function testStringEscapeDouble(): void
    {
        $o = new Executor();
        $this->assertEquals("test \ demo '", $o->execute("'test \ demo \\''"));
    }


    public function testStringEscape(): void
    {
        $o = new Executor();
        $this->assertEquals("test\string", $o->execute('"test\string"'));
        $this->assertEquals("\\test\string\\", $o->execute('"\test\string\\\\"'));
        $this->assertEquals('\test\string\\', $o->execute('"\test\string\\\\"'));
        $this->assertEquals('test\\\\string', $o->execute('"test\\\\\\\\string"'));
        $this->assertEquals('test"string', $o->execute('"test\"string"'));
        $this->assertEquals('test""string', $o->execute('"test\"\"string"'));
        $this->assertEquals('"teststring', $o->execute('"\"teststring"'));
        $this->assertEquals('teststring"', $o->execute('"teststring\""'));
        $this->assertEquals("test'string", $o->execute("'test\'string'"));
        $this->assertEquals("test''string", $o->execute("'test\'\'string'"));
        $this->assertEquals("'teststring", $o->execute("'\'teststring'"));
        $this->assertEquals("teststring'", $o->execute("'teststring\''"));

        $o->addFunction('concat', static function ($arg1, $arg2) {
            return $arg1 . $arg2;
        });
        $this->assertEquals('test"ing', $o->execute('concat("test\"","ing")'));
        $this->assertEquals("test'ing", $o->execute("concat('test\'','ing')"));
    }

    public function testArrays(): void
    {
        $o = new Executor();
        $this->assertEquals([1, 5, 2], $o->execute('array(1, 5, 2)'));
        $this->assertEquals([1, 5, 2], $o->execute('[1, 5, 2]'));
        $this->assertEquals(max([1, 5, 2]), $o->execute('max([1, 5, 2])'));
        $this->assertEquals(max([1, 5, 2]), $o->execute('max(array(1, 5, 2))'));
        $o->addFunction('arr_with_max_elements', static function ($arg1, ...$args) {
            $args = is_array($arg1) ? $arg1 : [$arg1, ...$args];
            usort($args, static fn($arr1, $arr2) => count($arr2) <=> count($arr1));

            return $args[0];
        });
        $this->assertEquals([3, 3, 3], $o->execute('arr_with_max_elements([[1],array(2,2),[3,3,3]])'));
    }

    /**
     * @dataProvider incorrectExpressionsDataProvider
     */
    public function testIncorrectExpressionException(string $expression): void
    {
        $o = new Executor();
        $o->setVariables(['a' => 12, 'b' => 24]);
        $this->expectException(IncorrectExpressionException::class);
        $o->execute($expression);
    }

    /**
     * @dataProvider expressionsDataProvider
     */
    public function testCalculating(string $expression): void
    {
        $o = new Executor();

        eval('$phpResult = ' . $expression . ';');

        try {
            $result = $o->execute($expression);
        } catch (Exception $e) {
            $this->fail(sprintf(
                'Exception: %s (%s:%d), expression was: %s',
                get_class($e),
                $e->getFile(),
                $e->getLine(),
                $expression
            ));
        }
        $this->assertEquals($phpResult, $result, "Expression was: {$expression}");
    }

    /**
     * Data provider for incorrect expression
     * @return array
     */
    public function incorrectExpressionsDataProvider(): array
    {
        return [
          ['1 * + '],
          [' 2 3'],
          ['2 3 '],
          [' 2 4 3 '],
          ['$a $b'],
          ['$a [3, 4, 5]'],
          ['$a (3 + 4)'],
          ['$a "string"'],
          ['5 "string"'],
          ['"string" $a'],
          ['$a round(12.345)'],
          ['round(12.345) $a'],
          ['4 round(12.345)'],
          ['round(12.345) 4'],
        ];
    }

    /**
     * Expressions data provider
     *
     * Most tests can go in here.  The idea is that each expression will be
     * evaluated by Executor and by PHP with eval().
     * The results should be the same.  If they are not, then the test fails.
     * No need to add extra test unless you are doing
     * something more complex and not a simple mathematical expression.
     */
    public function expressionsDataProvider(): array
    {
        return [
          ['-5'],
          ['-5+10'],
          ['4-5'],
          ['4 -5'],
          ['(4*2)-5'],
          ['(4*2) - 5'],
          ['4*-5'],
          ['4 * -5'],
          ['+5'],
          ['+(3+2)'],
          ['+(+3+2)'],
          ['+(-3+2)'],
          ['-5'],
          ['-(-5)'],
          ['-(+5)'],
          ['+(-5)'],
          ['+(+5)'],
          ['-(3+2)'],
          ['-(-3+-2)'],

          ['abs(1.5)'],
          ['abs(-1.5)'],
          ['abs(15)'],
          ['abs(-15)'],
          ['ceil(1.5)'],
          ['ceil(15)'],
          ['ceil(-15)'],
          ['exp(1.5)'],
          ['floor(1.5)'],
          ['floor(15)'],
          ['fmod(1.5, 3.5)'],
          ['hypot(1.5, 3.5)'],
          ['intdiv(10, 2)'],
          ['log(1.5)'],
          ['log10(1.5)'],
          ['log1p(1.5)'],
          ['max(1.5, 3.5)'],
          ['min(1.5, 3.5)'],
          ['pi()'],
          ['pow(1.5, 3.5)'],
          ['round(1.5)'],
          ['round(5)'],
          ['round(1.5 + 1)'],
          ['sqrt(1.5)'],

          ['0.1 + 0.2'],
          ['0.1 + 0.2 - 0.3'],
          ['1 + 2'],

          ['0.1 - 0.2'],
          ['1 - 2'],

          ['0.1 * 2'],
          ['1 * 2'],

          ['0.1 / 0.2'],
          ['1 / 2'],

          ['2 * 2 + 3 * 3'],
          ['2 * 2 / 3 * 3'],
          ['2 / 2 / 3 / 3'],
          ['2 / 2 * 3 / 3'],
          ['2 / 2 * 3 * 3'],

          ['1 + 0.6 - 3 * 2 / 50'],

          ['(5 + 3) * -1'],

          ['-2- 2*2'],
          ['2- 2*2'],
          ['2-(2*2)'],
          ['(2- 2)*2'],
          ['2 + 2*2'],
          ['2+ 2*2'],
          ['2+2*2'],
          ['(2+2)*2'],
          ['(2 + 2)*-2'],
          ['(2+-2)*2'],

          ['1 + 2 * 3 / (min(1, 5) + 2 + 1)'],
          ['1 + 2 * 3 / (min(1, 5) - 2 + 5)'],
          ['1 + 2 * 3 / (min(1, 5) * 2 + 1)'],
          ['1 + 2 * 3 / (min(1, 5) / 2 + 1)'],
          ['1 + 2 * 3 / (min(1, 5) / 2 * 1)'],
          ['1 + 2 * 3 / (min(1, 5) / 2 / 1)'],
          ['1 + 2 * 3 / (3 + min(1, 5) + 2 + 1)'],
          ['1 + 2 * 3 / (3 - min(1, 5) - 2 + 1)'],
          ['1 + 2 * 3 / (3 * min(1, 5) * 2 + 1)'],
          ['1 + 2 * 3 / (3 / min(1, 5) / 2 + 1)'],

          ['(1 + 2) * 3 / (3 / min(1, 5) / 2 + 1)'],

          ['100500 * 3.5e5'],
          ['100500 * 3.5e-5'],
          ['100500 * 3.5E5'],
          ['100500 * 3.5E-5'],

          ['1 + "2" / 3'],
          ["1.5 + '2.5' / 4"],
          ['1.5 + "2.5" * ".5"'],

          ['-1 + -2'],
          ['-1+-2'],
          ['-1- -2'],
          ['-1/-2'],
          ['-1*-2'],

          ['(1+2+3+4-5)*7/100'],
          ['(-1+2+3+4- 5)*7/100'],
          ['(1+2+3+4- 5)*7/100'],
          ['( 1 + 2 + 3 + 4 - 5 ) * 7 / 100'],

          ['1 && 0'],
          ['1 && 0 && 1'],
          ['1 || 0'],
          ['1 && 0 || 1'],

          ['!5'],
          ['5 == 3'],
          ['5 == 5'],
          ['5 != 3'],
          ['5 != 5'],
          ['5 > 3'],
          ['3 > 5'],
          ['3 >= 5'],
          ['3 >= 3'],
          ['3 < 5'],
          ['5 < 3'],
          ['3 <= 5'],
          ['5 <= 5'],
          ['10 < 9 || 4 > (2+1)'],
          ['10 < 9 || 4 > (-2+1)'],
          ['10 < 9 || 4 > (2+1) && 5 == 5 || 4 != 6 || 3 >= 4 || 3 <= 7'],

          ['1 + 5 == 3 + 1'],
          ['1 + 5 == 5 + 1'],
          ['1 + 5 != 3 + 1'],
          ['1 + 5 != 5 + 1'],
          ['1 + 5 > 3 + 1'],
          ['1 + 3 > 5 + 1'],
          ['1 + 3 >= 5 + 1'],
          ['1 + 3 >= 3 + 1'],
          ['1 + 3 < 5 + 1'],
          ['1 + 5 < 3 + 1'],
          ['1 + 3 <= 5 + 1'],
          ['1 + 5 <= 5 + 1'],

          ['(-4)'],
          ['(-4 + 5)'],
          ['(3 * 1)'],
          ['(-3 * -1)'],
          ['1 + (-3 * -1)'],
          ['1 + ( -3 * 1)'],
          ['1 + (3 *-1)'],
          ['1 - 0'],
          ['1-0'],

          ['-(1.5)'],
          ['-log(4)'],
          ['-(-4)'],
          ['-(-4 + 5)'],
          ['-(3 * 1)'],
          ['-(-3 * -1)'],
          ['-1 + (-3 * -1)'],
          ['-1 + ( -3 * 1)'],
          ['-1 + (3 *-1)'],
          ['-1 - 0'],
          ['-1-0'],
          ['-(4*2)-5'],
          ['-(4*-2)-5'],
          ['-(-4*2) - 5'],
          ['-4*-5'],
          ['max(1,2,4.9,3)'],
          ['min(1,2,4.9,3)'],
          ['max([1,2,4.9,3])'],
          ['min([1,2,4.9,3])'],

          ['4 % 4'],
          ['7 % 4'],
          ['99 % 4'],
          ['123 % 7'],
        ];
    }
}
