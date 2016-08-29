<?php

namespace mpyw\HardBotterTest\CollectorTraitTest;

use mpyw\Co\Co;

function trigger_error($message, $level)
{
    $GLOBALS[__NAMESPACE__ . '-TRIGGER-ERROR-LOG'][] = [$message, $level];
}

/**
 * @requires PHP 7.0
 */
class CollectorTraitTest extends \Codeception\TestCase\Test
{
    use \Codeception\Specify;

    public function _before()
    {
        $GLOBALS[__NAMESPACE__ . '-TRIGGER-ERROR-LOG'] = [];
        $GLOBALS[__NAMESPACE__ . '-ERROR-COUNTER'] = PHP_INT_MAX;
    }

    public function _after()
    {
    }

    public function getBot($get_error_mode = 2)
    {
        return new class($get_error_mode)
        {
            use \mpyw\HardBotter\Traits\CollectorTrait;

            const ERRMODE_SILENT    = 0;
            const ERRMODE_WARNING   = 1;
            const ERRMODE_EXCEPTION = 2;

            protected $get_error_mode;

            public function __construct($get_error_mode)
            {
                $this->get_error_mode = $get_error_mode;
            }

            public function __call($method, array $args)
            {
                $method .= 'Impl';
                return $this->$method(...$args);
            }

            protected function getImpl($endpoint, array $params)
            {
                try {
                    $method = 'GET_' . str_replace(['_', '/'], ['', '_'], ucwords($endpoint, '_'));
                    return $this->$method($params);
                } catch (\RuntimeException $e) {
                    if ($this->get_error_mode === static::ERRMODE_WARNING) {
                        trigger_error($e->getMessage(), E_USER_WARNING);
                    }
                    if ($this->get_error_mode === static::ERRMODE_EXCEPTION) {
                        throw $e;
                    }
                    return false;
                }
            }

            protected function getAsyncImpl($endpoint, array $params)
            {
                yield;
                try {
                    $method = 'GET_' . str_replace(['_', '/'], ['', '_'], ucwords($endpoint, '_'));
                    return $this->$method($params);
                } catch (\RuntimeException $e) {
                    if ($this->get_error_mode === static::ERRMODE_WARNING) {
                        trigger_error($e->getMessage(), E_USER_WARNING);
                    }
                    if ($this->get_error_mode === static::ERRMODE_EXCEPTION) {
                        throw $e;
                    }
                    return false;
                }
            }

            protected function GET_Statuses_HomeTimeline(array $params)
            {
                if ($GLOBALS[__NAMESPACE__ . '-ERROR-COUNTER']-- < 1) {
                    throw new \RuntimeException('Error');
                }
                $statuses = [
                    (object)['id_str' => '10'],
                    (object)['id_str' => '9'],
                    (object)['id_str' => '8'],
                    (object)['id_str' => '7'],
                    (object)['id_str' => '6'],
                    (object)['id_str' => '5'],
                    (object)['id_str' => '4'],
                ];
                $params += ['max_id' => '114514'];
                return array_slice(array_filter($statuses, function (\stdClass $status) use ($params) {
                    return (int)$status->id_str <= (int)$params['max_id'];
                }), 0, 3);
            }

            protected function GET_Search_Tweets(array $params)
            {
                if ($GLOBALS[__NAMESPACE__ . '-ERROR-COUNTER']-- < 1) {
                    throw new \RuntimeException('Error');
                }
                $result = (object)[
                    'statuses' => [
                        (object)['id_str' => '10'],
                        (object)['id_str' => '9'],
                        (object)['id_str' => '8'],
                        (object)['id_str' => '7'],
                        (object)['id_str' => '6'],
                        (object)['id_str' => '5'],
                        (object)['id_str' => '4'],
                    ]
                ];
                $params += ['max_id' => '114514'];
                $result->statuses = array_slice(array_filter($result->statuses, function (\stdClass $status) use ($params) {
                    return (int)$status->id_str <= (int)$params['max_id'];
                }), 0, 3);
                return $result;
            }

            protected function GET_Followers_Ids(array $params)
            {
                if ($GLOBALS[__NAMESPACE__ . '-ERROR-COUNTER']-- < 1) {
                    throw new \RuntimeException('Error');
                }
                $sets = [
                    -1 => (object)[
                        'next_cursor_str' => '1',
                        'ids' => ['10', '9', '8'],
                    ],
                    1 => (object)[
                        'next_cursor_str' => '2',
                        'ids' => ['7', '6', '5'],
                    ],
                    2 => (object)[
                        'next_cursor_str' => '0',
                        'ids' => ['4'],
                    ],
                ];
                return $sets[$params['cursor']];
            }

            protected function GET_Account_VerifyCredentials(array $params)
            {
                return (object)[
                    'id_str' => '114514',
                    'name' => 'Homo',
                ];
            }
        };
    }

    public function testCollectHomeTimeline()
    {
        $expected = [
            (object)['id_str' => '10'],
            (object)['id_str' => '9'],
            (object)['id_str' => '8'],
        ];
        $actual = $this->getBot()->collect('statuses/home_timeline', 0);
        $this->assertEquals($expected, $actual);

        $expected = [
            (object)['id_str' => '10'],
            (object)['id_str' => '9'],
            (object)['id_str' => '8'],
            (object)['id_str' => '7'],
            (object)['id_str' => '6'],
            (object)['id_str' => '5'],
        ];
        $actual = $this->getBot()->collect('statuses/home_timeline', 1);
        $this->assertEquals($expected, $actual);

        $expected = [
            (object)['id_str' => '10'],
            (object)['id_str' => '9'],
            (object)['id_str' => '8'],
            (object)['id_str' => '7'],
            (object)['id_str' => '6'],
            (object)['id_str' => '5'],
            (object)['id_str' => '4'],
        ];
        $actual = $this->getBot()->collect('statuses/home_timeline', 2);
        $this->assertEquals($expected, $actual);
        $actual = $this->getBot()->collect('statuses/home_timeline', 3);
        $this->assertEquals($expected, $actual);
    }

    public function testCollectHomeTimelineErrorSlilent()
    {
        $GLOBALS[__NAMESPACE__ . '-ERROR-COUNTER'] = 0;
        $actual = $this->getBot(0)->collect('statuses/home_timeline', 10);
        $this->assertFalse($actual);
        $this->assertEmpty($GLOBALS[__NAMESPACE__ . '-TRIGGER-ERROR-LOG']);

        $GLOBALS[__NAMESPACE__ . '-ERROR-COUNTER'] = 1;
        $expected = [
            (object)['id_str' => '10'],
            (object)['id_str' => '9'],
            (object)['id_str' => '8'],
        ];
        $actual = $this->getBot(0)->collect('statuses/home_timeline', 10);
        $this->assertEquals($expected, $actual);
        $this->assertEmpty($GLOBALS[__NAMESPACE__ . '-TRIGGER-ERROR-LOG']);
    }

    public function testCollectHomeTimelineErrorWarning()
    {
        $GLOBALS[__NAMESPACE__ . '-ERROR-COUNTER'] = 0;
        $actual = $this->getBot(1)->collect('statuses/home_timeline', 10);
        $this->assertFalse($actual);
        $this->assertEquals([['Error', E_USER_WARNING]], $GLOBALS[__NAMESPACE__ . '-TRIGGER-ERROR-LOG']);

        $GLOBALS[__NAMESPACE__ . '-ERROR-COUNTER'] = 1;
        $expected = [
            (object)['id_str' => '10'],
            (object)['id_str' => '9'],
            (object)['id_str' => '8'],
        ];
        $actual = $this->getBot(0)->collect('statuses/home_timeline', 10);
        $this->assertEquals($expected, $actual);
        $this->assertEquals([['Error', E_USER_WARNING]], $GLOBALS[__NAMESPACE__ . '-TRIGGER-ERROR-LOG']);
    }

    public function testCollectHomeTimelineErrorException()
    {
        $this->setExpectedException(\RuntimeException::class, 'Error');
        $GLOBALS[__NAMESPACE__ . '-ERROR-COUNTER'] = 1;
        $this->getBot(2)->collect('statuses/home_timeline', 10);
    }

    public function testCollectAsyncHomeTimeline()
    {Co::wait(function () {
        $expected = [
            (object)['id_str' => '10'],
            (object)['id_str' => '9'],
            (object)['id_str' => '8'],
        ];
        $actual = yield $this->getBot()->collectAsync('statuses/home_timeline', 0);
        $this->assertEquals($expected, $actual);

        $expected = [
            (object)['id_str' => '10'],
            (object)['id_str' => '9'],
            (object)['id_str' => '8'],
            (object)['id_str' => '7'],
            (object)['id_str' => '6'],
            (object)['id_str' => '5'],
        ];
        $actual = yield $this->getBot()->collectAsync('statuses/home_timeline', 1);
        $this->assertEquals($expected, $actual);

        $expected = [
            (object)['id_str' => '10'],
            (object)['id_str' => '9'],
            (object)['id_str' => '8'],
            (object)['id_str' => '7'],
            (object)['id_str' => '6'],
            (object)['id_str' => '5'],
            (object)['id_str' => '4'],
        ];
        $actual = yield $this->getBot()->collectAsync('statuses/home_timeline', 2);
        $this->assertEquals($expected, $actual);
        $actual = yield $this->getBot()->collectAsync('statuses/home_timeline', 3);
        $this->assertEquals($expected, $actual);
    });}

    public function testCollectAsyncHomeTimelineErrorSlilent()
    {Co::wait(function () {
        $GLOBALS[__NAMESPACE__ . '-ERROR-COUNTER'] = 0;
        $actual = yield $this->getBot(0)->collectAsync('statuses/home_timeline', 10);
        $this->assertFalse($actual);
        $this->assertEmpty($GLOBALS[__NAMESPACE__ . '-TRIGGER-ERROR-LOG']);

        $GLOBALS[__NAMESPACE__ . '-ERROR-COUNTER'] = 1;
        $expected = [
            (object)['id_str' => '10'],
            (object)['id_str' => '9'],
            (object)['id_str' => '8'],
        ];
        $actual = yield $this->getBot(0)->collectAsync('statuses/home_timeline', 10);
        $this->assertEquals($expected, $actual);
        $this->assertEmpty($GLOBALS[__NAMESPACE__ . '-TRIGGER-ERROR-LOG']);
    });}

    public function testCollectAsyncHomeTimelineErrorWarning()
    {Co::wait(function () {
        $GLOBALS[__NAMESPACE__ . '-ERROR-COUNTER'] = 0;
        $actual = yield $this->getBot(1)->collectAsync('statuses/home_timeline', 10);
        $this->assertFalse($actual);
        $this->assertEquals([['Error', E_USER_WARNING]], $GLOBALS[__NAMESPACE__ . '-TRIGGER-ERROR-LOG']);

        $GLOBALS[__NAMESPACE__ . '-ERROR-COUNTER'] = 1;
        $expected = [
            (object)['id_str' => '10'],
            (object)['id_str' => '9'],
            (object)['id_str' => '8'],
        ];
        $actual = yield $this->getBot(0)->collectAsync('statuses/home_timeline', 10);
        $this->assertEquals($expected, $actual);
        $this->assertEquals([['Error', E_USER_WARNING]], $GLOBALS[__NAMESPACE__ . '-TRIGGER-ERROR-LOG']);
    });}

    public function testCollectAsyncHomeTimelineErrorException()
    {Co::wait(function () {
        $this->setExpectedException(\RuntimeException::class, 'Error');
        $GLOBALS[__NAMESPACE__ . '-ERROR-COUNTER'] = 1;
        yield $this->getBot(2)->collectAsync('statuses/home_timeline', 10);
    });}

    public function testCollectSearchTweets()
    {
        $expected = [
            (object)['id_str' => '10'],
            (object)['id_str' => '9'],
            (object)['id_str' => '8'],
            (object)['id_str' => '7'],
            (object)['id_str' => '6'],
            (object)['id_str' => '5'],
            (object)['id_str' => '4'],
        ];
        $actual = $this->getBot()->collect('search/tweets', 10);
        $this->assertEquals($expected, $actual);
    }

    public function testCollectAsyncSearchTweets()
    {Co::wait(function () {
        $expected = [
            (object)['id_str' => '10'],
            (object)['id_str' => '9'],
            (object)['id_str' => '8'],
            (object)['id_str' => '7'],
            (object)['id_str' => '6'],
            (object)['id_str' => '5'],
            (object)['id_str' => '4'],
        ];
        $actual = (yield $this->getBot()->collectAsync('search/tweets', 10));
        $this->assertEquals($expected, $actual);
    });}

    public function testCollectFollowersIds()
    {
        $expected = ['10', '9', '8', '7', '6', '5', '4'];
        $actual = $this->getBot()->collect('followers/ids', 10);
        $this->assertEquals($expected, $actual);
    }

    public function testCollectAsyncFollowersIds()
    {Co::wait(function () {
        $expected = ['10', '9', '8', '7', '6', '5', '4'];
        $actual = $this->getBot()->collect('followers/ids', 10);
        $this->assertEquals($expected, $actual);
    });}

    public function testIncompatibleEndpoint()
    {
        $this->setExpectedException(\BadMethodCallException::class, 'Incompatible endpoint.');
        $this->getBot()->collect('account/verify_credentials', 0);
    }
}
