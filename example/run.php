<?php

// Botクラスを名前空間接頭辞無しで利用する
use mpyw\HardBotter\Bot;

// オートローダを読み込む
// (最初に「composer install」の実行が必要)
require __DIR__ . '/../vendor/autoload.php';

// Botインスタンスを生成
extract(parse_ini_file(__DIR__ . '/config.ini'));
$to = new TwistOAuth(
    $consumer_key,
    $consumer_secret,
    $oauth_token,
    $oauth_token_secret
);
$bot = new Bot($to, __DIR__ . '/stamp.json', 2);

// メンションを取得
foreach ($bot->get('statuses/mentions_timeline') as $status) {
    // パターンマッチングを行い、適合した処理を行う
    if (null !== Bot::match($status->text, array(
        '/おはよう|こんにちは|こんばんは/' => function ($m) use ($bot, $status) {
            return $bot->reply("{$m[0]}！", $status);
        },
        '/何時/' => function ($m) use ($timezone, $bot, $status) {
            $date = new DateTime('now', new DateTimeZone($timezone));
            return $bot->reply($date->format('H時i分だよー'), $status);
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
            return $bot->reply('あなたの運勢は' . $list[array_rand($list)] . 'です', $status);
        },
        '/ふぁぼ/' => function ($m) use ($status, $bot) {
            return $bot->favorite($status);
        },
        '/ホモ/' => function ($m) use ($status, $bot) {
            return $bot->tweet("{$status->user->name}はホモ");
        }
    ))) {
        // 返り値がNULL以外の場合、次回以降は反応しないようにマークする
        $bot->mark($status);
    }
}
