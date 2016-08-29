<?php

namespace mpyw\HardBotter\Traits;

require_once __DIR__ . '/PseudoClient.php';
require_once __DIR__ . '/PseudoFunctions.php';

use mpyw\Co\Co;
use mpyw\HardBotterTest\PseudoClient;

/**
 * @requires PHP 7.0
 */
class CollectorTraitTest extends \Codeception\TestCase\Test
{
    use \Codeception\Specify;

    public function _before()
    {
        $GLOBALS['HARDBOTTER-TRIGGER-ERROR-LOG'] = [];
        $GLOBALS['HARDBOTTER-ERROR-COUNTER'] = PHP_INT_MAX;
    }

    public function _after()
    {
    }

    public function getBot($get_error_mode = 2)
    {
        return new class($get_error_mode)
        {
            const ERRMODE_SILENT    = 0;
            const ERRMODE_WARNING   = 1;
            const ERRMODE_EXCEPTION = 2;

            public $get_error_mode = 2;
            public $post_error_mode = 1;
            public $back_limit = 3600;
            public $marked = [];

            use InterceptorTrait;
            use CollectorTrait;

            public function __construct($get_error_mode) {
                $this->get_error_mode = $get_error_mode;
            }

            public function getClient()
            {
                return new PseudoClient([]);
            }

            public function getGetErrorMode()
            {
                return $this->get_error_mode;
            }

            public function getPostErrorMode()
            {
                return $this->post_error_mode;
            }

            public function getMarkedStatusIds()
            {
                return $this->marked;
            }

            public function getBackLimitSeconds()
            {
                return $this->back_limit;
            }

            protected static function expired($past, $interval)
            {
                $past = new \DateTimeImmutable($past, new \DateTimeZone('UTC'));
                $future = $past->add(new \DateInterval("PT{$interval}S"));
                $now = new \DateTimeImmutable('2000-10-10 12:30:00', new \DateTimeZone('UTC'));
                return $future <= $now;
            }
        };
    }

    public function testCollectHomeTimeline()
    {
        $expected = json_decode('[
            {
                "user": {
                    "id_str": "111",
                    "screen_name": "re4k",
                    "name": "omfg (at)mpyw"
                },
                "id_str": "5555",
                "text": "<This is holy & shit dummy text>",
                "created_at": "2000-10-10 12:29:00"
            },
            {
                "user": {
                    "id_str": "222",
                    "screen_name": "0xk",
                    "name": "omfg @mpywwwwwwwwwwwwwwwwwwwwwww"
                },
                "id_str": "4444",
                "text": "Hi",
                "created_at": "2000-10-10 12:28:00"
            }
        ]');
        $actual = $this->getBot()->collect('statuses/home_timeline', 0, ['count' => 2]);
        $this->assertEquals($expected, $actual);

        $expected = json_decode('[
            {
                "user": {
                    "id_str": "111",
                    "screen_name": "re4k",
                    "name": "omfg (at)mpyw"
                },
                "id_str": "5555",
                "text": "<This is holy & shit dummy text>",
                "created_at": "2000-10-10 12:29:00"
            },
            {
                "user": {
                    "id_str": "222",
                    "screen_name": "0xk",
                    "name": "omfg @mpywwwwwwwwwwwwwwwwwwwwwww"
                },
                "id_str": "4444",
                "text": "Hi",
                "created_at": "2000-10-10 12:28:00"
            },
            {
                "user": {
                    "id_str": "333",
                    "screen_name": "ce4k",
                    "name": "John"
                },
                "id_str": "3333",
                "text": "Hello",
                "created_at": "2000-10-10 12:27:00"
            },
            {
                "user": {
                    "id_str": "444",
                    "screen_name": "te4k",
                    "name": "Bob"
                },
                "id_str": "2222",
                "text": "lol",
                "created_at": "2000-10-10 12:26:00"
            }
        ]');
        $actual = $this->getBot()->collect('statuses/home_timeline', 1, ['count' => 2]);
        $this->assertEquals($expected, $actual);

        $expected = json_decode('[
            {
                "user": {
                    "id_str": "111",
                    "screen_name": "re4k",
                    "name": "omfg (at)mpyw"
                },
                "id_str": "5555",
                "text": "<This is holy & shit dummy text>",
                "created_at": "2000-10-10 12:29:00"
            },
            {
                "user": {
                    "id_str": "222",
                    "screen_name": "0xk",
                    "name": "omfg @mpywwwwwwwwwwwwwwwwwwwwwww"
                },
                "id_str": "4444",
                "text": "Hi",
                "created_at": "2000-10-10 12:28:00"
            },
            {
                "user": {
                    "id_str": "333",
                    "screen_name": "ce4k",
                    "name": "John"
                },
                "id_str": "3333",
                "text": "Hello",
                "created_at": "2000-10-10 12:27:00"
            },
            {
                "user": {
                    "id_str": "444",
                    "screen_name": "te4k",
                    "name": "Bob"
                },
                "id_str": "2222",
                "text": "lol",
                "created_at": "2000-10-10 12:26:00"
            },
            {
                "user": {
                    "id_str": "555",
                    "screen_name": "cat",
                    "name": "Alice"
                },
                "id_str": "1111",
                "text": "Nyan",
                "created_at": "2000-10-10 12:25:00"
            }
        ]');
        $actual = $this->getBot()->collect('statuses/home_timeline', 10, ['count' => 2]);
        $this->assertEquals($expected, $actual);
    }

    public function testCollectHomeTimelineErrorSlilent()
    {
        $GLOBALS['HARDBOTTER-ERROR-COUNTER'] = 0;
        $actual = $this->getBot(0)->collect('statuses/home_timeline', 10, ['count' => 2]);
        $this->assertFalse($actual);
        $this->assertEmpty($GLOBALS['HARDBOTTER-TRIGGER-ERROR-LOG']);

        $GLOBALS['HARDBOTTER-ERROR-COUNTER'] = 1;
        $actual = $this->getBot(0)->collect('statuses/home_timeline', 10, ['count' => 2]);
        $this->assertFalse($actual);
        $this->assertEmpty($GLOBALS['HARDBOTTER-TRIGGER-ERROR-LOG']);
    }

    public function testCollectHomeTimelineErrorWarning()
    {
        $GLOBALS['HARDBOTTER-ERROR-COUNTER'] = 0;
        $actual = $this->getBot(1)->collect('statuses/home_timeline', 10, ['count' => 2]);
        $this->assertFalse($actual);
        $this->assertEquals([['Error', E_USER_WARNING]], $GLOBALS['HARDBOTTER-TRIGGER-ERROR-LOG']);

        $GLOBALS['HARDBOTTER-TRIGGER-ERROR-LOG'] = [];
        $GLOBALS['HARDBOTTER-ERROR-COUNTER'] = 1;
        $actual = $this->getBot(1)->collect('statuses/home_timeline', 10, ['count' => 2]);
        $this->assertFalse($actual);
        $this->assertEquals([['Error', E_USER_WARNING]], $GLOBALS['HARDBOTTER-TRIGGER-ERROR-LOG']);
    }

    public function testCollectHomeTimelineErrorException()
    {
        $this->setExpectedException(\RuntimeException::class, 'Error');
        $GLOBALS['HARDBOTTER-ERROR-COUNTER'] = 1;
        $this->getBot(2)->collect('statuses/home_timeline', 10, ['count' => 2]);
    }

    public function testCollectAsyncHomeTimeline()
    {Co::wait(function () {
        $expected = json_decode('[
            {
                "user": {
                    "id_str": "111",
                    "screen_name": "re4k",
                    "name": "omfg (at)mpyw"
                },
                "id_str": "5555",
                "text": "<This is holy & shit dummy text>",
                "created_at": "2000-10-10 12:29:00"
            },
            {
                "user": {
                    "id_str": "222",
                    "screen_name": "0xk",
                    "name": "omfg @mpywwwwwwwwwwwwwwwwwwwwwww"
                },
                "id_str": "4444",
                "text": "Hi",
                "created_at": "2000-10-10 12:28:00"
            }
        ]');
        $actual = yield $this->getBot()->collectAsync('statuses/home_timeline', 0, ['count' => 2]);
        $this->assertEquals($expected, $actual);

        $expected = json_decode('[
            {
                "user": {
                    "id_str": "111",
                    "screen_name": "re4k",
                    "name": "omfg (at)mpyw"
                },
                "id_str": "5555",
                "text": "<This is holy & shit dummy text>",
                "created_at": "2000-10-10 12:29:00"
            },
            {
                "user": {
                    "id_str": "222",
                    "screen_name": "0xk",
                    "name": "omfg @mpywwwwwwwwwwwwwwwwwwwwwww"
                },
                "id_str": "4444",
                "text": "Hi",
                "created_at": "2000-10-10 12:28:00"
            },
            {
                "user": {
                    "id_str": "333",
                    "screen_name": "ce4k",
                    "name": "John"
                },
                "id_str": "3333",
                "text": "Hello",
                "created_at": "2000-10-10 12:27:00"
            },
            {
                "user": {
                    "id_str": "444",
                    "screen_name": "te4k",
                    "name": "Bob"
                },
                "id_str": "2222",
                "text": "lol",
                "created_at": "2000-10-10 12:26:00"
            }
        ]');
        $actual = yield $this->getBot()->collectAsync('statuses/home_timeline', 1, ['count' => 2]);
        $this->assertEquals($expected, $actual);

        $expected = json_decode('[
            {
                "user": {
                    "id_str": "111",
                    "screen_name": "re4k",
                    "name": "omfg (at)mpyw"
                },
                "id_str": "5555",
                "text": "<This is holy & shit dummy text>",
                "created_at": "2000-10-10 12:29:00"
            },
            {
                "user": {
                    "id_str": "222",
                    "screen_name": "0xk",
                    "name": "omfg @mpywwwwwwwwwwwwwwwwwwwwwww"
                },
                "id_str": "4444",
                "text": "Hi",
                "created_at": "2000-10-10 12:28:00"
            },
            {
                "user": {
                    "id_str": "333",
                    "screen_name": "ce4k",
                    "name": "John"
                },
                "id_str": "3333",
                "text": "Hello",
                "created_at": "2000-10-10 12:27:00"
            },
            {
                "user": {
                    "id_str": "444",
                    "screen_name": "te4k",
                    "name": "Bob"
                },
                "id_str": "2222",
                "text": "lol",
                "created_at": "2000-10-10 12:26:00"
            },
            {
                "user": {
                    "id_str": "555",
                    "screen_name": "cat",
                    "name": "Alice"
                },
                "id_str": "1111",
                "text": "Nyan",
                "created_at": "2000-10-10 12:25:00"
            }
        ]');
        $actual = yield $this->getBot()->collectAsync('statuses/home_timeline', 10, ['count' => 2]);
        $this->assertEquals($expected, $actual);
    });}

    public function testCollectAsyncHomeTimelineErrorSlilent()
    {Co::wait(function () {
        $GLOBALS['HARDBOTTER-ERROR-COUNTER'] = 0;
        $actual = yield $this->getBot(0)->collectAsync('statuses/home_timeline', 10, ['count' => 2]);
        $this->assertFalse($actual);
        $this->assertEmpty($GLOBALS['HARDBOTTER-TRIGGER-ERROR-LOG']);

        $GLOBALS['HARDBOTTER-ERROR-COUNTER'] = 1;
        $actual = yield $this->getBot(0)->collectAsync('statuses/home_timeline', 10, ['count' => 2]);
        $this->assertFalse($actual);
        $this->assertEmpty($GLOBALS['HARDBOTTER-TRIGGER-ERROR-LOG']);
    });}

    public function testCollectAsyncHomeTimelineErrorWarning()
    {Co::wait(function () {
        $GLOBALS['HARDBOTTER-ERROR-COUNTER'] = 0;
        $actual = yield $this->getBot(1)->collectAsync('statuses/home_timeline', 10, ['count' => 2]);
        $this->assertFalse($actual);
        $this->assertEquals([['Error', E_USER_WARNING]], $GLOBALS['HARDBOTTER-TRIGGER-ERROR-LOG']);

        $GLOBALS['HARDBOTTER-TRIGGER-ERROR-LOG'] = [];
        $GLOBALS['HARDBOTTER-ERROR-COUNTER'] = 1;
        $actual = yield $this->getBot(1)->collectAsync('statuses/home_timeline', 10, ['count' => 2]);
        $this->assertFalse($actual);
        $this->assertEquals([['Error', E_USER_WARNING]], $GLOBALS['HARDBOTTER-TRIGGER-ERROR-LOG']);
    });}

    public function testCollectAsyncHomeTimelineErrorException()
    {Co::wait(function () {
        $this->setExpectedException(\RuntimeException::class, 'Error');
        $GLOBALS['HARDBOTTER-ERROR-COUNTER'] = 1;
        yield $this->getBot(2)->collectAsync('statuses/home_timeline', 10, ['count' => 2]);
    });}

    public function testCollectSearchTweets()
    {
        $expected = json_decode('[
            {
                "user": {
                    "id_str": "111",
                    "screen_name": "re4k",
                    "name": "omfg (at)mpyw"
                },
                "id_str": "5555",
                "text": "<This is holy & shit dummy text>",
                "created_at": "2000-10-10 12:29:00"
            },
            {
                "user": {
                    "id_str": "222",
                    "screen_name": "0xk",
                    "name": "omfg @mpywwwwwwwwwwwwwwwwwwwwwww"
                },
                "id_str": "4444",
                "text": "Hi",
                "created_at": "2000-10-10 12:28:00"
            },
            {
                "user": {
                    "id_str": "333",
                    "screen_name": "ce4k",
                    "name": "John"
                },
                "id_str": "3333",
                "text": "Hello",
                "created_at": "2000-10-10 12:27:00"
            },
            {
                "user": {
                    "id_str": "444",
                    "screen_name": "te4k",
                    "name": "Bob"
                },
                "id_str": "2222",
                "text": "lol",
                "created_at": "2000-10-10 12:26:00"
            },
            {
                "user": {
                    "id_str": "555",
                    "screen_name": "cat",
                    "name": "Alice"
                },
                "id_str": "1111",
                "text": "Nyan",
                "created_at": "2000-10-10 12:25:00"
            }
        ]');
        $actual = $this->getBot()->collect('search/tweets', 10, ['count' => 2]);
        $this->assertEquals($expected, $actual);
    }

    public function testCollectAsyncSearchTweets()
    {Co::wait(function () {
        $expected = json_decode('[
            {
                "user": {
                    "id_str": "111",
                    "screen_name": "re4k",
                    "name": "omfg (at)mpyw"
                },
                "id_str": "5555",
                "text": "<This is holy & shit dummy text>",
                "created_at": "2000-10-10 12:29:00"
            },
            {
                "user": {
                    "id_str": "222",
                    "screen_name": "0xk",
                    "name": "omfg @mpywwwwwwwwwwwwwwwwwwwwwww"
                },
                "id_str": "4444",
                "text": "Hi",
                "created_at": "2000-10-10 12:28:00"
            },
            {
                "user": {
                    "id_str": "333",
                    "screen_name": "ce4k",
                    "name": "John"
                },
                "id_str": "3333",
                "text": "Hello",
                "created_at": "2000-10-10 12:27:00"
            },
            {
                "user": {
                    "id_str": "444",
                    "screen_name": "te4k",
                    "name": "Bob"
                },
                "id_str": "2222",
                "text": "lol",
                "created_at": "2000-10-10 12:26:00"
            },
            {
                "user": {
                    "id_str": "555",
                    "screen_name": "cat",
                    "name": "Alice"
                },
                "id_str": "1111",
                "text": "Nyan",
                "created_at": "2000-10-10 12:25:00"
            }
        ]');
        $actual = yield $this->getBot()->collectAsync('search/tweets', 10, ['count' => 2]);
        $this->assertEquals($expected, $actual);
    });}

    public function testCollectFollowersIds()
    {
        $expected = ['1', '2', '3', '4', '5', '6', '7'];
        $actual = $this->getBot()->collect('followers/ids', 10, ['count' => 2]);
        $this->assertEquals($expected, $actual);
    }

    public function testCollectAsyncFollowersIds()
    {Co::wait(function () {
        $expected = ['1', '2', '3', '4', '5', '6', '7'];
        $actual = yield $this->getBot()->collectAsync('followers/ids', 10, ['count' => 2]);
        $this->assertEquals($expected, $actual);
    });}

    public function testIncompatibleEndpoint()
    {
        $this->setExpectedException(\BadMethodCallException::class, 'Incompatible endpoint.');
        $this->getBot()->collect('account/verify_credentials', 0);
    }
}
