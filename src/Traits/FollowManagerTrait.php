<?php

namespace mpyw\HardBotter\Traits;

use mpyw\Cowitter\Client;
use mpyw\Co\Co;

trait FollowManagerTrait
{
    abstract public static function out($msg);
    abstract public function __call($method, array $args);
    abstract public function collect($endpoint, $followable_page_count, array $params = []);
    abstract public function collectAsync($endpoint, $followable_page_count, array $params = []);

    public function forceMutuals($followable_page_count = INF)
    {
        list($friends, $followers) = [
            $this->collect('friends/ids', $followable_page_count, ['stringify_ids' => true]),
            $this->collect('followers/ids', $followable_page_count, ['stringify_ids' => true]),
        ];
        list($friends_only, $followers_only) = static::getFriendsOnlyAndFollowersOnly($friends, $followers);
        $results = array_merge(
            array_map([$this, 'unfollow'], $friends_only),
            array_map([$this, 'follow'], $followers_only)
        );
        return !in_array(false, $results, true);
    }

    public function forceMutualsAsync($followable_page_count = INF)
    {
        list($friends, $followers) = (yield [
            $this->collectAsync('friends/ids', $followable_page_count, ['stringify_ids' => true]),
            $this->collectAsync('followers/ids', $followable_page_count, ['stringify_ids' => true]),
        ]);
        list($friends_only, $followers_only) = static::getFriendsOnlyAndFollowersOnly($friends, $followers);
        $results = (yield array_merge(
            array_map([$this, 'unfollowAsync'], $friends_only),
            array_map([$this, 'followAsync'], $followers_only)
        ));
        yield Co::RETURN_WITH => !in_array(false, $results, true);
    }

    protected static function getFriendsOnlyAndFollowersOnly(array $friends, array $followers)
    {
        $fr_flip = array_flip($friends);
        $fo_flip = array_flip($followers);
        $fr_only_flip = array_diff_key($fr_flip, $fo_flip);
        $fo_only_flip = array_diff_key($fo_flip, $fr_flip);
        $fr_only = array_keys($fr_only_flip);
        $fo_only = array_keys($fo_only_flip);
        return [$fr_only, $fo_only];
    }

    public function follow($user_id)
    {
        $result = $this->post('friendships/create', ['user_id' => $user_id]);
        if ($result !== false) {
            static::out('FOLLOWED: @' . $result->screen_name);
        }
        return $result;
    }

    public function followAsync($user_id)
    {
        $result = (yield $this->postAsync('friendships/create', ['user_id' => $user_id]));
        if ($result !== false) {
            static::out('FOLLOWED: @' . $result->screen_name);
        }
        yield Co::RETURN_WITH => $result;
    }

    public function unfollow($user_id)
    {
        $result = $this->post('friendships/destroy', ['user_id' => $user_id]);
        if ($result !== false) {
            static::out('UNFOLLOWED: @' . $result->screen_name);
        }
        return $result;
    }

    public function unfollowAsync($user_id)
    {
        $result = (yield $this->postAsync('friendships/destroy', ['user_id' => $user_id]));
        if ($result !== false) {
            static::out('UNFOLLOWED: @' . $result->screen_name);
        }
        yield Co::RETURN_WITH => $result;
    }
}
