<?php

namespace mpyw\HardBotterTest;

use mpyw\Cowitter\Client;

class PseudoClient extends Client
{
    public function __construct(array $credentials, array $options = [])
    {
        parent::__construct(['ck' => 1, 'cs' => 2], $options);
    }

    protected static function statuses()
    {
        return json_decode('
            [
                {
                    "user": {
                        "id_str": "111",
                        "screen_name": "re4k",
                        "name": "omfg @mpyw"
                    },
                    "id_str": "5555",
                    "text": "&lt;This is holy &amp; shit dummy text&gt;",
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
        ');
    }

    public function get($endpoint, array $params = [], $return_response_object = false)
    {
        if (isset($GLOBALS['HARDBOTTER-ERROR-COUNTER']) && $GLOBALS['HARDBOTTER-ERROR-COUNTER']-- < 1) {
            throw new \RuntimeException('Error');
        }
        $method = 'GET_' . str_replace(['_', '/'], ['', '_'], ucwords($endpoint, '_'));
        return $this->$method($params);
    }

    public function getAsync($endpoint, array $params = [], $return_response_object = false)
    {
        yield;
        if (isset($GLOBALS['HARDBOTTER-ERROR-COUNTER']) && $GLOBALS['HARDBOTTER-ERROR-COUNTER']-- < 1) {
            throw new \RuntimeException('Error');
        }
        $method = 'GET_' . str_replace(['_', '/'], ['', '_'], ucwords($endpoint, '_'));
        return $this->$method($params);
    }

    public function post($endpoint, array $params = [], $return_response_object = false)
    {
        if (isset($GLOBALS['HARDBOTTER-ERROR-COUNTER']) && $GLOBALS['HARDBOTTER-ERROR-COUNTER']-- < 1) {
            throw new \RuntimeException('Error');
        }
        $method = 'POST_' . str_replace(['_', '/'], ['', '_'], ucwords($endpoint, '_'));
        return $this->$method($params);
    }

    public function postAsync($endpoint, array $params = [], $return_response_object = false)
    {
        yield;
        if (isset($GLOBALS['HARDBOTTER-ERROR-COUNTER']) && $GLOBALS['HARDBOTTER-ERROR-COUNTER']-- < 1) {
            throw new \RuntimeException('Error');
        }
        $method = 'POST_' . str_replace(['_', '/'], ['', '_'], ucwords($endpoint, '_'));
        return $this->$method($params);
    }

    protected function GET_Statuses_HomeTimeline(array $params)
    {
        $statuses = static::statuses();
        $params += ['max_id' => '114514', 'count' => '114514'];
        return array_slice(array_filter($statuses, function (\stdClass $status) use ($params) {
            return (int)$status->id_str <= (int)$params['max_id'];
        }), 0, $params['count']);
    }

    protected function GET_Search_Tweets(array $params)
    {
        $result = (object)['statuses' => static::statuses()];
        $params += ['max_id' => '114514', 'count' => '114514'];
        $result->statuses = array_slice(array_filter($result->statuses, function (\stdClass $status) use ($params) {
            return (int)$status->id_str <= (int)$params['max_id'];
        }), 0, $params['count']);
        return $result;
    }

    protected function GET_Followers_Ids(array $params)
    {
        $sets = [
            -1 => (object)[
                'next_cursor_str' => '1',
                'ids' => ['1', '2', '3'],
            ],
            1 => (object)[
                'next_cursor_str' => '2',
                'ids' => ['4', '5', '6'],
            ],
            2 => (object)[
                'next_cursor_str' => '0',
                'ids' => ['7'],
            ],
        ];
        return $sets[$params['cursor']];
    }

    protected function GET_Friends_Ids(array $params)
    {
        $sets = [
            -1 => (object)[
                'next_cursor_str' => '1',
                'ids' => ['4', '5', '6'],
            ],
            1 => (object)[
                'next_cursor_str' => '0',
                'ids' => ['11'],
            ]
        ];
        return $sets[$params['cursor']];
    }

    protected function GET_Users_Lookup(array $params)
    {
        return json_decode('[{
            "id_str": "111",
            "screen_name": "re4k",
            "name": "omfg @mpyw"
        }]');
    }

    protected function GET_Account_VerifyCredentials(array $params)
    {
        return json_decode('{
            "id_str": "111",
            "screen_name": "re4k",
            "name": "omfg @mpyw"
        }');
    }

    protected function POST_Statuses_Update(array $params)
    {
        return (object)[
            'id_str' => (string)mt_rand(),
            'text' => htmlspecialchars($params['status'], ENT_QUOTES, 'UTF-8'),
            'creatd_at' => '2000-10-10 12:30:00',
        ];
    }

    protected function POST_Friendships_create(array $params)
    {
        return (object)['id_str' => $params['user_id']];
    }

    protected function POST_Friendships_destroy(array $params)
    {
        return (object)['id_str' => $params['user_id']];
    }

    public function oauthForRequestToken($oauth_callback = null)
    {
        throw new \RuntimeException('This method always fails');
    }
}
