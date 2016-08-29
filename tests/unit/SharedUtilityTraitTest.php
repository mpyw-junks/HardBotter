<?php

namespace mpyw\HardBotterTest;

/**
 * @requires PHP 7.0
 */
class SharedUtilityTraitTest extends \Codeception\TestCase\Test
{
    use \Codeception\Specify;
    use \mpyw\HardBotter\Traits\SharedUtilityTrait;

    public function _before()
    {
    }

    public function _after()
    {
    }

    public function testOut()
    {
        ob_start();
        try {
            self::out('abc');
            self::out('def');
        } finally {
            $buffer = ob_get_clean();
        }
        $this->assertEquals('abc' . PHP_EOL . 'def' . PHP_EOL, $buffer);
    }

    public function testExpired()
    {
        $this->assertFalse(self::expired('now -100 seconds', 150));
        $this->assertTrue(self::expired('now -100 seconds', 50));
    }

    public function testMatchString()
    {
        $expected = '2円しかないのか…それは惨めですな…';
        $actual = self::match(
            '私の所持金は2円です。今の気分は惨めです。',
            [
                '/ダミー/' => '',
                '/私の所持金は(\d+)円です。今の気分は(?<feel>.*?)です。/' => '$1円しかないのか…それは${feel}ですな…',
            ]
        );
        $this->assertEquals($expected, $actual);
    }

    public function testMatchCallback()
    {
        $expected = '2円しかないのか…それは惨めですな…';
        $actual = self::match(
            '私の所持金は2円です。今の気分は惨めです。',
            [
                '/私の所持金は(\d+)円です。今の気分は(?<feel>.*?)です。/' => function ($m) {
                    return sprintf('%s円しかないのか…それは%sですな…', $m[1], $m['feel']);
                },
            ]
        );
        $this->assertEquals($expected, $actual);
    }

    public function testMatchNull()
    {
        $expected = '2円しかないのか…それは惨めですな…';
        $actual = self::match(
            '私の所持金は2円です。今の気分は惨めです。',
            [
                '/私の所持金は(\d+)円です。今の気分は(?<feel>.*?)です。/' => null,
            ]
        );
        $this->assertNull($actual);
    }

    public function testMatchEmpty()
    {
        $expected = '2円しかないのか…それは惨めですな…';
        $actual = self::match(
            '私の所持金は2円です。今の気分は惨めです。',
            []
        );
        $this->assertNull($actual);
    }
}
