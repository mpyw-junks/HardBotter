<?php

namespace mpyw\HardBotter\Traits;

trait SharedUtilityTrait
{
    /**
     * パターンマッチング
     */
    public static function match($text, array $pairs)
    {
        static $callback;

        if (!$callback) {
            // 文字列で指定された場合に置換フォーマットをパースして処理するクロージャ
            $callback = function ($m) use (&$matches) {
                $key = isset($m[2]) ? $m[2] : $m[1];
                return isset($matches[$key]) ? $matches[$key] : '';
            };
        }

        // 連想配列の先頭からチェック
        foreach ($pairs as $pattern => $value) {
            if (!preg_match($pattern, $text, $matches)) {
                // パターンにマッチしなかった場合は次へ
                continue;
            }
            if (is_scalar($value)) {
                // 文字列で指定された場合
                return preg_replace_callback('/\$(?:\{([^}]*+)}|(\d++))/', $callback, $value);
            }
            if (is_callable($value)) {
                // クロージャで指定された場合
                return $value($matches);
            }
            // マッチしたのに指定がなかった場合はNULL
            return;
        }
    }

    /**
     * $past + $interval と現在を比較して期限が過ぎているかどうかをチェック
     */
    protected static function expired($past, $interval) {
        $past = new \DateTimeImmutable($past, new \DateTimeZone('UTC'));
        $future = $past->add(new \DateInterval("PT{$interval}S"));
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        return $future <= $now;
    }

    /**
     * 結果出力用
     */
    protected static function out($msg)
    {
        if (PHP_SAPI === 'cli') {
            echo $msg . PHP_EOL;
        } else {
            echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '<br>' . PHP_EOL;
        }
    }
}
