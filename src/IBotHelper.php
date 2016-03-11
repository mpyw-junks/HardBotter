<?php

namespace mpyw\HardBotter;

/**
 * 補助メソッド群
 */
interface IBotHelper {

    /**
     * テキストマッチング処理を補助する静的メソッド。
     * 連想配列の先頭から順番に検査され、マッチした時点でその処理を行って返します。
     *
     * @param string $text
     *          入力文字列。
     * @param array<string, string|Closure> $pairs
     *          キー(string): PCRE正規表現。
     *          値(string): preg_replace スタイルの置換フォーマット文字列。
     *          値(Closure): 第1引数にpreg_matchの参照引数 $matches を取るクロージャ。
     * @return mixed|null
     *          マッチし、処理に成功したとき: 文字列あるいはクロージャの返り値
     *          マッチするものが無かった: null
     */
    public static function match($text, array $pairs);

    /**
     * レスポンス自体が配列になるもしくはその子要素の何れかが配列になるエンドポイントに関して、
     * cursorあるいはmax_idの変更による追跡を補助します。返り値はマージされた配列になります。
     *
     * @param string $endpoint
     *          エンドポイント。"search/tweets", "statuses/home_timeline" "friends/ids" など。
     * @param int $followable_page_count
     *          追加の追跡上限とするページ数。
     * @param array<string, string> [$params = array()]
     * @return array<mixed>|bool
     *          失敗時、エラーモードが Bot::ERRMODE_WARNING に設定されている場合はFALSEを返します。
     * @throws TwistException
     *          失敗時、エラーモードが Bot::ERRMODE_EXCEPTION に設定されている場合にスローされます。
     */
    public function collect($endpoint, $followable_page_count, array $params = array());

    /**
     * リプライの補助。
     * 実行後に結果を標準出力します。
     *
     * @param string $text
     *          "@相手のスクリーンネーム {$text}" に相当する $text。
     * @param stdClass $original_status
     *          返信対象ツイートのステータスオブジェクト。
     * @param bool [$prepend_screen_name = true]
     *          先頭にスクリーンネームを付加するかどうか。
     * @return array<mixed>|bool
     *          失敗時、エラーモードが Bot::ERRMODE_WARNING に設定されている場合はFALSEを返します。
     * @throws TwistException
     *          失敗時、エラーモードが Bot::ERRMODE_EXCEPTION に設定されている場合にスローされます。
     */
    public function reply($text, \stdClass $original_status, $prepend_screen_name = true);

    /**
     * 片思いをアンフォローし、片思われをフォローします。
     * 実行中に結果を標準出力します。
     * 凍結されるリスクがあるので、採用の際はよく検討してください。
     *
     * @param int [$followable_page_count = INF]
     *          追加の追跡上限とするページ数。
     * @return bool
     *          全件成功時、Trueを返します。
     *          1件以上失敗時、エラーモードが Bot::ERRMODE_WARNING に設定されている場合はFALSEを返します。
     *                       このとき、失敗しても中断を行いません。
     * @throws TwistException
     *          1件以上失敗時、エラーモードが Bot::ERRMODE_EXCEPTION に設定されている場合にスローされます。
     *                       このとき、例外スローのタイミングで処理が中断されます。
     */
    public function forceMutuals($followable_page_count = INF);

    /**
     * その他の補助。実行後に結果を標準出力します。
     * (あったほうがいいメソッドがあれば編集してプルリクエストください)
     */
    public function tweet($text);
    public function favorite(\stdClass $status);
    public function retweet(\stdClass $status);
    public function follow($user_id);
    public function unfollow($user_id);
    public function mediaTweet($text, $path);

}
