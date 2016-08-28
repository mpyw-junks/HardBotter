<?php

// クラス名のインポート
use mpyw\HardBotter\Bot;
use mpyw\Cowitter\Client;
use mpyw\Co\Co;

// オートローダを読み込む
// (最初に「composer install」の実行が必要)
require __DIR__ . '/../vendor/autoload.php';

// Botインスタンスを生成
extract(parse_ini_file(__DIR__ . '/config.ini'));
$client = new Client([$consumer_key, $consumer_secret, $access_token, $access_token_secret]);
$bot = new Bot($client, __DIR__ . '/stamp.json', 2);

// 今回はすべて非同期APIに統一して書きます
Co::wait(function () use ($bot, $timezone) {

    $tasks = [];

    // メンションを取得
    foreach ((yield $bot->getAsync('statuses/mentions_timeline')) as $status) {

        // パターンマッチングを行い，適合した処理を選択する
        if (null !== $task = Bot::match($status->text, [

            '/おはよう|こんにちは|こんばんは/' => function ($m) use ($bot, $status) {
                return $bot->replyAsync("{$m[0]}！", $status);
            },

            '/何時/' => function ($m) use ($timezone, $bot, $status) {
                $date = new DateTime('now', new DateTimeZone($timezone));
                return $bot->replyAsync($date->format('H時i分だよー'), $status);
            },

            '/占い|おみくじ/' => function ($m) use ($bot, $status) {
                $list = array(
                    '大吉',
                    '吉', '吉',
                    '中吉', '中吉', '中吉',
                    '小吉', '小吉', '小吉',
                    '末吉', '末吉',
                    '凶',
                );
                return $bot->replyAsync('あなたの運勢は' . $list[array_rand($list)] . 'です', $status);
            },

            '/ふぁぼ/' => function ($m) use ($status, $bot) {
                return $bot->favoriteAsync($status);
            },

            '/ホモ/' => function ($m) use ($status, $bot) {
                return $bot->tweetAsync("{$status->user->name}はホモ");
            },

        ])) {

            // 返り値がタスクの場合，次回以降は反応しないようにマークする
            $bot->mark($status);

            // タスクを配列に入れておく
            $tasks[] = $task;

        }

    }

    // 一気に実行
    yield $tasks;

});
