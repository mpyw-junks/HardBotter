<?php

require __DIR__ . '/TwistOAuth.php';
require __DIR__ . '/HardBotterModel.php';

// 実行します 
MyHardBotter::run('MyHardBotterLog.dat');

/**
 * ここに自由に実装してください。
 * 但し、 getTwistOAuth() と action() は HardBotterModel.php 内
 * で指示されている通りに必ず実装してください。
 */
class MyHardBotter extends HardBotterModel {
    
    /**
     * HardBotterModel.php 内で指示されている通りに必ず実装してください。
     */
    protected function getTwistOAuth() {
        // TwistOAuthオブジェクトを返します
        return new TwistOAuth(
            '***********************',
            '***********************',
            '***********************',
            '"**********************'
        );
    }
    
    /**
     * HardBotterModel.php 内で指示されている通りに必ず実装してください。
     */
    protected function action() {
        $this->checkMentions();
    }
    
    /**
     * メンションをチェックし、反応できるものがあればリプライで反応します。
     */
    protected function checkMentions() {
        foreach ($this->getLatestMentions() as $status) {
            // マッチングを行う(先頭のものほど優先される)
            $text = $this->match($status, array(
                '/おはよう|こんにちは|こんばんは/' => function ($s, $m) {
                    return $m[0] . '！';
                },
                '/何時/' => function ($s, $m) {
                    return date_create('now', new DateTimeZone('Asia/Tokyo'))
                           ->format('H時i分だよー');
                },
                '/占い/' => function ($s, $m) {
                    $list = array(
                        '大吉',
                        '吉', '吉',
                        '中吉', '中吉', '中吉',
                        '小吉', '小吉', '小吉', 
                        '末吉', '末吉',
                        '凶',
                    );
                    return $list[array_rand($list)];
                },
            ));
            // 結果が得られればそれを反応済みリストに追加してリプライを実行する
            if ($text !== null) {
                $this->mark($status);
                $this->reply($status, $text);
            }
        }
    }
    
}