<?php

namespace mpyw\HardBotter\Traits;

require_once __DIR__ . '/PseudoClient.php';
require_once __DIR__ . '/PseudoFunctions.php';

use mpyw\Co\Co;
use mpyw\HardBotterTest\PseudoClient;

/**
 * @requires PHP 7.0
 */
class InterceptorTraitTest extends \Codeception\TestCase\Test
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

    public function getBot()
    {
        return new class
        {
            const ERRMODE_SILENT    = 0;
            const ERRMODE_WARNING   = 1;
            const ERRMODE_EXCEPTION = 2;

            public $get_error_mode = 2;
            public $post_error_mode = 1;
            public $back_limit = 3600;
            public $marked = [];

            use InterceptorTrait;

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

    public function testGetHomeTimeline()
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
        $actual = $this->getBot()->get('statuses/home_timeline', ['count' => 5]);
        $this->assertEquals($expected, $actual);
    }

    public function testGetHomeTimelineErrorSlilent()
    {
        $GLOBALS['HARDBOTTER-ERROR-COUNTER'] = 0;
        $bot = $this->getBot();
        $bot->get_error_mode = 0;
        $actual = $bot->get('statuses/home_timeline');
        $this->assertFalse($actual);
        $this->assertEmpty($GLOBALS['HARDBOTTER-TRIGGER-ERROR-LOG']);
    }

    public function testGetHomeTimelineErrorWarning()
    {
        $GLOBALS['HARDBOTTER-ERROR-COUNTER'] = 0;
        $bot = $this->getBot();
        $bot->get_error_mode = 1;
        $actual = $bot->get('statuses/home_timeline');
        $this->assertFalse($actual);
        $this->assertEquals([['Error', E_USER_WARNING]], $GLOBALS['HARDBOTTER-TRIGGER-ERROR-LOG']);
    }

    public function testGetHomeTimelineErrorException()
    {
        $this->setExpectedException(\RuntimeException::class, 'Error');
        $GLOBALS['HARDBOTTER-ERROR-COUNTER'] = 0;
        $this->getBot()->get('statuses/home_timeline');
    }

    public function testGetAsyncHomeTimeline()
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
        $actual = yield $this->getBot()->getAsync('statuses/home_timeline', ['count' => 5]);
        $this->assertEquals($expected, $actual);
    });}

    public function testGetAsyncHomeTimelineErrorSlilent()
    {Co::wait(function () {
        $GLOBALS['HARDBOTTER-ERROR-COUNTER'] = 0;
        $bot = $this->getBot();
        $bot->get_error_mode = 0;
        $actual = yield $bot->getAsync('statuses/home_timeline');
        $this->assertFalse($actual);
        $this->assertEmpty($GLOBALS['HARDBOTTER-TRIGGER-ERROR-LOG']);
    });}

    public function testGetAsyncHomeTimelineErrorWarning()
    {Co::wait(function () {
        $GLOBALS['HARDBOTTER-ERROR-COUNTER'] = 0;
        $bot = $this->getBot();
        $bot->get_error_mode = 1;
        $actual = yield $bot->getAsync('statuses/home_timeline');
        $this->assertFalse($actual);
        $this->assertEquals([['Error', E_USER_WARNING]], $GLOBALS['HARDBOTTER-TRIGGER-ERROR-LOG']);
    });}

    public function testGetAsyncHomeTimelineErrorException()
    {Co::wait(function () {
        $this->setExpectedException(\RuntimeException::class, 'Error');
        $GLOBALS['HARDBOTTER-ERROR-COUNTER'] = 0;
        yield $this->getBot()->getAsync('statuses/home_timeline');
    });}

    public function testPostUpdate()
    {
        $expected_text = '<This is holy & shit dummy text>';
        $actual = $this->getBot()->post('statuses/update', ['status' => $expected_text]);
        $this->assertEquals($expected_text, $actual->text);
    }

    public function testPostUpdateErrorSlilent()
    {
        $GLOBALS['HARDBOTTER-ERROR-COUNTER'] = 0;
        $bot = $this->getBot();
        $bot->post_error_mode = 0;
        $actual = $bot->post('statuses/update', ['status' => 'dummy']);
        $this->assertFalse($actual);
        $this->assertEmpty($GLOBALS['HARDBOTTER-TRIGGER-ERROR-LOG']);
    }

    public function testPostUpdateErrorWarning()
    {
        $GLOBALS['HARDBOTTER-ERROR-COUNTER'] = 0;
        $bot = $this->getBot();
        $bot->post_error_mode = 1;
        $actual = $bot->post('statuses/update', ['status' => 'dummy']);
        $this->assertFalse($actual);
        $this->assertEquals([['Error', E_USER_WARNING]], $GLOBALS['HARDBOTTER-TRIGGER-ERROR-LOG']);
    }

    public function testPostUpdateErrorException()
    {
        $this->setExpectedException(\RuntimeException::class, 'Error');
        $GLOBALS['HARDBOTTER-ERROR-COUNTER'] = 0;
        $bot = $this->getBot();
        $bot->post_error_mode = 2;
        $actual = $bot->post('statuses/update', ['status' => 'dummy']);
    }

    public function testGetSearches()
    {
        $expected = json_decode('{
            "statuses": [
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
            ]
        }');
        $actual = $this->getBot()->get('search/tweets', ['count' => 5]);
        $this->assertEquals($expected, $actual);
    }

    public function testGetUsersLookUp()
    {
        $expected = json_decode('[{
            "id_str": "111",
            "screen_name": "re4k",
            "name": "omfg (at)mpyw"
        }]');
        $actual = $this->getBot()->get('users/lookup');
        $this->assertEquals($expected, $actual);
    }

    public function testGetFollowersIds()
    {
        $expected = json_decode('{
            "next_cursor_str": "1",
            "ids": ["1", "2", "3"]
        }');
        $actual = $this->getBot()->get('followers/ids', ['cursor' => '-1']);
        $this->assertEquals($expected, $actual);
    }

    public function testMarkedOrExpiredStatusesExcluded()
    {
        $expected = json_decode('[
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
        $bot = $this->getBot();
        $bot->marked['5555'] = true;
        $bot->marked['4444'] = true;
        $bot->back_limit = 250;
        $actual = $bot->get('statuses/home_timeline');
        $this->assertEquals($expected, $actual);
    }

    public function testOtherMethodSuccess()
    {
        $bot = $this->getBot()->withOptions([CURLOPT_USERAGENT => '&lt;&gt;']);
        $this->assertEquals('&lt;&gt;', $bot->getOptions()[CURLOPT_USERAGENT]);
    }

    public function testOtherMethodFailure()
    {
        $this->setExpectedException(\RuntimeException::class, 'This method always fails');
        $this->getBot()->oauthForRequestToken();
    }

    public function testInvalidMethod()
    {
        $this->setExpectedException(\BadMethodCallException::class, "Call to undefined method mpyw\Cowitter\Client::undefinedMethod()");
        $this->getBot()->undefinedMethod();
    }
}
