<?php

namespace mpyw\HardBotter\Traits;

use mpyw\Co\Co;

/**
 * @method mixed post($endpoint, array $params = [])
 * @method \Generator postAsync($endpoint, array $params = [])
 */
trait TweetManagerTrait
{
    abstract protected static function out($msg);
    abstract public function __call($method, array $args);

    public function reply($text, \stdClass $original_status, $prepend_screen_name = true)
    {
        $result = $this->post('statuses/update', [
            'status' => $prepend_screen_name ? "@{$original_status->user->screen_name} $text" : $text,
            'in_reply_to_status_id' => $original_status->id_str,
        ]);
        if ($result !== false) {
            self::out('REPLIED: ' . $result->text);
        }
        return $result;
    }

    public function replyAsync($text, \stdClass $original_status, $prepend_screen_name = true)
    {
        $result = (yield $this->postAsync('statuses/update', [
            'status' => $prepend_screen_name ? "@{$original_status->user->screen_name} $text" : $text,
            'in_reply_to_status_id' => $original_status->id_str,
        ]));
        if ($result !== false) {
            self::out('REPLIED: ' . $result->text);
        }
        yield Co::RETURN_WITH => $result;
    }

    public function tweet($text)
    {
        $result = $this->post('statuses/update', ['status' => $text]);
        if ($result !== false) {
            self::out('TWEETED: ' . $result->text);
        }
        return $result;
    }

    public function tweetAsync($text)
    {
        $result = (yield $this->postAsync('statuses/update', ['status' => $text]));
        if ($result !== false) {
            self::out('TWEETED: ' . $result->text);
        }
        yield Co::RETURN_WITH => $result;
    }

    public function favorite(\stdClass $status)
    {
        $result = $this->post('favorites/create', ['id' => $status->id_str]);
        if ($result !== false) {
            self::out('FAVORITED: ' . $result->text);
        }
        return $result;
    }

    public function favoriteAsync(\stdClass $status)
    {
        $result = (yield $this->postAsync('favorites/create', ['id' => $status->id_str]));
        if ($result !== false) {
            self::out('FAVORITED: ' . $result->text);
        }
        yield Co::RETURN_WITH => $result;
    }

    public function retweet(\stdClass $status)
    {
        $result = $this->post('statuses/retweet', ['id' => $status->id_str]);
        if ($result !== false) {
            self::out('RETWEETED: ' . $status->text);
        }
        return $result;
    }

    public function retweetAsync(\stdClass $status)
    {
        $result = (yield $this->postAsync('statuses/retweet', ['id' => $status->id_str]));
        if ($result !== false) {
            self::out('RETWEETED: ' . $status->text);
        }
        yield Co::RETURN_WITH => $result;
    }
}
