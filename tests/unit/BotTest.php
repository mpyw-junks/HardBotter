<?php

namespace mpyw\HardBotterTest;

use mpyw\HardBotter\Bot;
use mpyw\Cowitter\Client;

/**
 * @requires PHP 7.0
 */
class BotTest extends \Codeception\TestCase\Test
{
    use \Codeception\Specify;

    private static $file;
    private static $filename;

    public function _before()
    {
        if (self::$file === null) {
            self::$file = tmpfile();
            self::$filename = stream_get_meta_data(self::$file)['uri'];
        }
    }

    public function _after()
    {
        ftruncate(self::$file, 0);
        rewind(self::$file);
    }

    public function testDuplicatedLaunch()
    {
        $bot1 = new Bot(new Client(['', '']), self::$filename);
        $this->setExpectedException(\RuntimeException::class, 'Failed to lock file.');
        $bot2 = new Bot(new Client(['', '']), self::$filename);
    }

    public function testInsufficientSpan()
    {
        $bot1 = new Bot(new Client(['', '']), self::$filename);
        unset($bot1);
        $this->setExpectedException(\RuntimeException::class, 'Execution span is not enough.');
        $bot2 = new Bot(new Client(['', '']), self::$filename, 200);
    }

    public function testMarkRetrived()
    {
        $bot1 = new Bot(new Client(['', '']), self::$filename);
        $bot1->mark((object)['id_str' => '114514']);
        unset($bot1);
        $bot2 = new Bot(new Client(['', '']), self::$filename);
        $this->assertArrayHasKey('114514', $bot2->getMarkedStatusIds());
    }

    public function testSettersAndGetters()
    {
        $bot = new Bot($client = new Client(['', '']), self::$filename, 0, 20000, 5000);
        $this->assertEquals(20000, $bot->getMarkLimitCounts());
        $this->assertEquals(5000, $bot->getBackLimitSeconds());
        $this->assertEquals(Bot::ERRMODE_EXCEPTION, $bot->getGetErrorMode());
        $this->assertEquals(Bot::ERRMODE_WARNING, $bot->getPostErrorMode());
        $bot->setGetErrorMode(Bot::ERRMODE_SILENT);
        $bot->setPostErrorMode(Bot::ERRMODE_SILENT);
        $this->assertEquals(Bot::ERRMODE_SILENT, $bot->getGetErrorMode());
        $this->assertEquals(Bot::ERRMODE_SILENT, $bot->getPostErrorMode());
        $this->assertSame($client, $bot->getClient());
    }

    public function testSleep()
    {
        $this->setExpectedException(\BadMethodCallException::class, 'Instances are not serializable.');
        serialize(new Bot($client = new Client(['', ''])));
    }

    public function testClone()
    {
        $this->setExpectedException(\BadMethodCallException::class, 'Instances are not clonable.');
        clone new Bot($client = new Client(['', '']));
    }
}
