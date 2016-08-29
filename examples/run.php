<?php

// クラス名のインポート
use mpyw\HardBotter\Bot;
use mpyw\Cowitter\Client;
use mpyw\Co\Co;
use Dotenv\Dotenv;

// オートローダを読み込む
// (最初に「composer install」の実行が必要)
require __DIR__ . '/../vendor/autoload.php';

// .envファイルからの環境変数の読み込み
$dotenv = new Dotenv(__DIR__);
$dotenv->load();
$dotenv->required([
    'CONSUMER_KEY',
    'CONSUMER_SECRET',
    'ACCESS_TOKEN',
    'ACCESS_TOKEN_SECRET',
])->notEmpty();

// Botインスタンスを生成
$client = new Client([
    $_SERVER['CONSUMER_KEY'],
    $_SERVER['CONSUMER_SECRET'],
    $_SERVER['ACCESS_TOKEN'],
    $_SERVER['ACCESS_TOKEN_SECRET'],
]);
$bot = new Bot($client, __DIR__ . '/stamp.json', 2);

// 今回はすべて非同期APIに統一して書きます
Co::wait(function () use ($bot) {

    $tasks = [];

    // メンションを取得
    foreach ((yield $bot->getAsync('statuses/mentions_timeline')) as $status) {

        // パターンマッチングを行い，適合した処理を選択する
        if (null !== $task = Bot::match($status->text, [

            '/おはよう|こんにちは|こんばんは/' => function ($m) use ($bot, $status) {
                return $bot->replyAsync("{$m[0]}！", $status);
            },

            '/何時/' => function ($m) use ($bot, $status) {
                $date = new DateTime('now', new DateTimeZone(getenv('TIMEZONE') ?: 'Asia/Tokyo'));
                return $bot->replyAsync($date->format('H時i分だよー'), $status);
            },

            '/占い|おみくじ/' => function ($m) use ($bot, $status) {
                $list = [
                    '大吉',
                    '吉', '吉',
                    '中吉', '中吉', '中吉',
                    '小吉', '小吉', '小吉',
                    '末吉', '末吉',
                    '凶',
                ];
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
