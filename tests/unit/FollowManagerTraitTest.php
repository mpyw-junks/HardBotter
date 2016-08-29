<?php

namespace mpyw\HardBotter\Traits;

require_once __DIR__ . '/PseudoClient.php';
require_once __DIR__ . '/PseudoFunctions.php';

use mpyw\Co\Co;
use mpyw\HardBotterTest\PseudoClient;

/**
 * @requires PHP 7.0
 */
class FollowManagerTraitTest extends \Codeception\TestCase\Test
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
            use FollowManagerTrait;

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
            }

            protected static function out($msg) {
                $GLOBALS['HARDBOTTER-BUFFER-OUTS'][] = trim($msg);
            }
        };
    }

    public function testForceMutuals()
    {
        $this->assertEquals(true, $this->getBot()->forceMutuals());
        $this->assertEquals($GLOBALS['HARDBOTTER-BUFFER-OUTS'], [
            'UNFOLLOWED: @11',
            'FOLLOWED: @1',
            'FOLLOWED: @2',
            'FOLLOWED: @3',
            'FOLLOWED: @7',
        ]);
    }

    public function testCollectAsyncFollowersIds()
    {Co::wait(function () {
        $this->assertEquals(true, yield $this->getBot()->forceMutualsAsync());
        $this->assertEquals($GLOBALS['HARDBOTTER-BUFFER-OUTS'], [
            'UNFOLLOWED: @11',
            'FOLLOWED: @1',
            'FOLLOWED: @2',
            'FOLLOWED: @3',
            'FOLLOWED: @7',
        ]);
    });}
}
