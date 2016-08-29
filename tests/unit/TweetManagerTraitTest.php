<?php

namespace mpyw\HardBotter\Traits;

require_once __DIR__ . '/PseudoClient.php';
require_once __DIR__ . '/PseudoFunctions.php';

use mpyw\Co\Co;
use mpyw\HardBotterTest\PseudoClient;

/**
 * @requires PHP 7.0
 */
class TweetManagerTraitTest extends \Codeception\TestCase\Test
{
    use \Codeception\Specify;

    public function _before()
    {
        $GLOBALS['HARDBOTTER-TRIGGER-ERROR-LOG'] = [];
        $GLOBALS['HARDBOTTER-BUFFER-OUTS'] = [];
        $GLOBALS['HARDBOTTER-ERROR-COUNTER'] = PHP_INT_MAX;
    }

    public function _after()
    {
    }

    public function getBot($post_error_mode = 1)
    {
        return new class($post_error_mode)
        {
            const ERRMODE_SILENT    = 0;
            const ERRMODE_WARNING   = 1;
            const ERRMODE_EXCEPTION = 2;

            public $get_error_mode = 2;
            public $post_error_mode = 1;
            public $back_limit = 3600;
            public $marked = [];

            use InterceptorTrait;
            use TweetManagerTrait;

            public function __construct($post_error_mode) {
                $this->post_error_mode = $post_error_mode;
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
            }

            protected static function out($msg) {
                $GLOBALS['HARDBOTTER-BUFFER-OUTS'][] = trim($msg);
            }
        };
    }

    public function testTweet()
    {
        $this->getBot()->tweet('<aiueo>');
        $this->assertEquals([
            'TWEETED: <aiueo>',
        ], $GLOBALS['HARDBOTTER-BUFFER-OUTS']);
    }

    public function testTweetAsync()
    {Co::wait(function () {
        yield $this->getBot()->tweetAsync('<aiueo>');
        $this->assertEquals([
            'TWEETED: <aiueo>',
        ], $GLOBALS['HARDBOTTER-BUFFER-OUTS']);
    });}

    public function testRetweet()
    {
        $this->getBot()->retweet((object)[
            'id_str' => '114514',
        ]);
        $this->assertEquals([
            'RETWEETED @mpyw: <lol>',
        ], $GLOBALS['HARDBOTTER-BUFFER-OUTS']);
    }

    public function testRetweetAsync()
    {Co::wait(function () {
        yield $this->getBot()->retweetAsync((object)[
            'id_str' => '114514',
        ]);
        $this->assertEquals([
            'RETWEETED @mpyw: <lol>',
        ], $GLOBALS['HARDBOTTER-BUFFER-OUTS']);
    });}

    public function testFavorite()
    {
        $this->getBot()->favorite((object)[
            'id_str' => '114514',
        ]);
        $this->assertEquals([
            'FAVORITED @mpyw: <lol>',
        ], $GLOBALS['HARDBOTTER-BUFFER-OUTS']);
    }

    public function testFavoriteAsync()
    {Co::wait(function () {
        yield $this->getBot()->favoriteAsync((object)[
            'id_str' => '114514',
        ]);
        $this->assertEquals([
            'FAVORITED @mpyw: <lol>',
        ], $GLOBALS['HARDBOTTER-BUFFER-OUTS']);
    });}

    public function testReply()
    {
        $this->getBot()->reply('yo', (object)[
            'id_str' => '114514',
            'user' => (object)[
                'screen_name' => 'mpyw',
            ],
        ]);
        $this->assertEquals([
            'REPLIED: @mpyw yo',
        ], $GLOBALS['HARDBOTTER-BUFFER-OUTS']);
    }

    public function testReplyAsync()
    {Co::wait(function () {
        yield $this->getBot()->replyAsync('yeah', (object)[
            'id_str' => '114514',
            'user' => (object)[
                'screen_name' => 'mpyw',
            ],
        ]);
        $this->assertEquals([
            'REPLIED: @mpyw yeah',
        ], $GLOBALS['HARDBOTTER-BUFFER-OUTS']);
    });}
}
