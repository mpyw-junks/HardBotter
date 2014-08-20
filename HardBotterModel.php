<?php

/**
 * HardBotterModel Version 0.1.1
 * 
 * この抽象クラスを継承して利用します。
 */
abstract class HardBotterModel {
    
    private $to;
    private $file;
    private $lastIdDefault = '0';
    private $lastIds = array();
    private $marked = array();
    
    /**
     * この抽象メソッドを実装してください。
     * TwistOAuthのインスタンスを返します。
     * 何回もコールされるので、この部分にxAuth認証などは用いないでください。
     *
     * @return TwistOAuthオブジェクト。
     */
    abstract protected function getTwistOAuth();
    
    /**
     * この抽象メソッドを実装してください。
     * run() をコールしたときに呼び出されます。
     */
    abstract protected function action();
    
    /**
     * ボットデータの記録ファイルを指定して実行します。
     * ファイルが存在しないときには初期化されます。
     * 
     * @param $filename ボットデータを記録したファイル。
     */
    final public static function run($filename) {
        header('Content-Type: text/plain; charset=utf-8');
        set_time_limit(0);
        while (ob_get_level()) {
            ob_end_clean();
        }
        $self = new static;
        $self->to = $self->getTwistOAuth();
        if (!($self->to instanceof TwistOAuth)) {
            trigger_error('getTwistOAuth() must return an instance of TwistOAuth.', E_USER_ERROR);
        }
        $exists = is_file($filename);
        $file = new SplFileObject($filename, 'a+b');
        if (!$file->flock(LOCK_EX | LOCK_NB)) {
            trigger_error('Failed to lock file.', E_USER_ERROR);
        }
        $self->file = $file;
        if (!$exists) {
            $params = array(
                'status' => '@tos Operation started. ' . md5(mt_rand())
            );
            $self->lastIdDefault = $self->to->post('statuses/update', $params)->id_str;
        } else {
            ob_start(); 
            $file->fpassthru();
            $d = json_decode(ob_get_clean(), true);
            if (!isset($d['lastIdDefault'], $d['lastIds'])) {
                trigger_error('Serial was broken.', E_USER_ERROR);
            }
            $self->lastIdDefault = $d['lastIdDefault'];
            $self->lastIds = $d['lastIds'];
        }
        $self->action();
    }
    
    /**
     * PCRE正規表現でツイート内容を順番にマッチさせていき、
     * マッチした時点でそのコールバック関数の返り値を返します。
     * 先頭のものほど優先されます。
     * 
     * @param $status ステータスオブジェクト。
     * @param $pairs  「正規表現 => コールバック」の形の連想配列。
     *                マッチングにはpreg_matchを用います。
     *                コールバック関数は以下のような形です。
     *                   function ($status, $matches) { ... }
     * @return        コールバック関数の返り値を返します。
     *                マッチするものが何もなかったときはNULLを返します。
     */
    final protected static function match(stdClass $status, array $pairs) {
        foreach ($pairs as $pattern => $function) {
            if (preg_match($pattern, $status->text, $matches)) {
                return $function($status, $matches);
            }
        }
    }
    
    /**
     * 多重反応を防ぐための反応済みリストへの追加を行います。
     * 一度ここにセットされたものは、次に他のエンドポイントから
     * 取得したときに除外されます。
     * 
     * @param status 記録対象のステータスオブジェクト。
     */
    final protected function mark(stdClass $status) {
        $this->marked[$status->id_str] = true;
    }
    
    /**
     * 過去のツイート・反応済みリストにあるものを除外してホームを取得します。
     * textプロパティに含まれるHTML特殊文字はデコードされます。
     * 失敗時には空配列がセットされ、エラーログに記録されます。
     * 
     * @return ホーム200件のうち過去のツイートを除外したもの。
     */
    final protected function getLatestHome() {
        try {
            if ($statuses = $this->filter(
                'home',
                $this->getTwistOAuth()->get('statuses/home_timeline', array('count' => 200))
            )) {
                $this->lastIds['home'] = reset($statuses)->id_str;
            }
            return $statuses;
        } catch (Exception $e) {
            trigger_error(
                'getLatestHome(): ' . $e->getMessage(),
                E_USER_WARNING
            );
            return array();
        }
    }
    
    /**
     * 過去のツイート・反応済みリストにあるものを除外してメンションを取得します。
     * textプロパティに含まれるHTML特殊文字はデコードされます。
     * 失敗時には空配列がセットされ、エラーログに記録されます。
     * 
     * @return メンション200件のうち過去のツイートを除外したもの。
     */
    final protected function getLatestMentions() {
        try {
            if ($statuses = $this->filter(
                'mentions',
                $this->getTwistOAuth()->get('statuses/mentions_timeline', array('count' => 200))
            )) {
                $this->lastIds['mentions'] = reset($statuses)->id_str;
            }
            return $statuses;
        } catch (Exception $e) {
            trigger_error(
                'getLatestMentions(): ' . $e->getMessage(),
                E_USER_WARNING
            );
            return array();
        }
    }
    
    /**
     * 過去のツイート・反応済みリストにあるものを除外して検索結果を取得します。
     * ログは検索キーワードごとに管理されます。
     * textプロパティに含まれるHTML特殊文字はデコードされます。
     * 失敗時には空配列がセットされ、エラーログに記録されます。
     * 
     * @return 検索結果100件のうち過去のツイートを除外したもの。
     */
    final protected function getLatestSearch($q) {
        try {
            if ($statuses = $this->filter(
                'search - ' . $q,
                $this->getTwistOAuth()->get('search/tweets', array(
                    'q'     => $q,
                    'count' => 100,
                ))->statuses
            )) {
                $this->lastIds['search - ' . $q] = reset($statuses)->id_str;
            }
            return $statuses;
        } catch (Exception $e) {
            trigger_error(
                'getLatestSearch(): ' . $e->getMessage(),
                E_USER_WARNING
            );
            return array();
        }
    }
    
    /**
     * ツイートします。
     * textプロパティに含まれるHTML特殊文字はデコードされます。
     * 失敗時にはNULLがセットされ、エラーログに記録されます。
     * 
     * @param  $text                    ツイート内容。
     * @param  [$in_reply_to_status_id] 返信先のステータスID。
     * @return                          ツイートしたステータスオブジェクト。
     */
    final protected function tweet($text, $in_reply_to_status_id = null) {
        try {
            $status = $this->getTwistOAuth()->post('statuses/update', array(
                'status'                => $text,
                'in_reply_to_status_id' => $in_reply_to_status_id,
            ));
            echo 'tweet(): Success ' . $status->id_str . "\n";
            return self::filterSingle($status);
        } catch (Exception $e) {
            trigger_error(
                'postTweet(): ' . $e->getMessage(),
                E_USER_WARNING
            );
        }
    }
    
    /**
     * リプライを実行します。
     * tweet() のラッパーメソッドです。
     * textプロパティに含まれるHTML特殊文字はデコードされます。
     * 
     * @param  $status 返信先のステータスオブジェクト。
     * @param  $text   ツイート内容(@は書かなくていい)。
     * @return         ツイートしたステータスオブジェクト。
     */
    final protected function reply(stdClass $status, $text) {
        return $this->tweet(
            '@' . $status->user->screen_name . ' ' . $text,
            $status->id_str
        );
    }
    
    /**
     * 画像つきでツイートします。
     * textプロパティに含まれるHTML特殊文字はデコードされます。
     * 失敗時にはNULLがセットされ、エラーログに記録されます。
     * 
     * @param  $status                  ツイート内容。
     * @param  $media_path              画像ファイルへのパス(絶対パス推奨)。
     * @param  [$in_reply_to_status_id] 返信先のステータスID。
     * @return                          ツイートしたステータスオブジェクト。
     */
    final protected function tweetWithMedia($text, $media_path, $in_reply_to_status_id = null) {
        try {
            $statis = $this->getTwistOAuth()->postMultipart('statuses/update_with_media', array(
                'status'                => $text,
                '@media[]'              => $media_path,
                'in_reply_to_status_id' => $in_reply_to_status_id,
            ));
            echo 'tweetWithMedia(): Success ' . $status->id_str . "\n";
            return self::filterSingle($status);
        } catch (Exception $e) {
            trigger_error(
                'postTweetWithMedia(): ' . $e->getMessage(),
                E_USER_WARNING
            );
        }
    }
    
    /**
     * 画像つきでリプライを実行します。
     * tweetWithMedia() のラッパーメソッドです。
     * 
     * @param  $status     返信先のステータスオブジェクト。
     * @param  $text       ツイート内容(@は書かなくていい)。
     * @param  $media_path 画像ファイルへのパス(絶対パス推奨)。
     * @return             ステータスオブジェクト。
     */
    final protected function replyWithMedia(stdClass $status, $text, $media_path) {
        return $this->tweetWithMedia(
            '@' . $status->user->screen_name . ' ' . $text,
            $media_path,
            $status->id_str
        );
    }
    
    /**
     * ふぁぼります。
     * textプロパティに含まれるHTML特殊文字はデコードされます。
     * 失敗時にはNULLがセットされ、エラーログに記録されます。
     * 
     * @param  $status ふぁぼ対象のステータスオブジェクト。
     * @return         ふぁぼ対象のステータスオブジェクト。
     */
    final protected function favorite(stdClass $status) {
        try {
            $status = $this->getTwistOAuth()->post('favorites/create', array('id' => $status->id_str));
            echo 'favorite(): Success ' . $status->id_str . "\n";
            return self::filterSingle($status);
        } catch (Exception $e) {
            trigger_error(
                'favorite(): ' . $e->getMessage(),
                E_USER_WARNING
            );
        }
    }
    
    /**
     * リツイートします。
     * textプロパティに含まれるHTML特殊文字はデコードされます。
     * 失敗時にはNULLがセットされ、エラーログに記録されます。
     * 
     * @param  $status リツイート対象のステータスオブジェクト。
     * @return         リツイートしたステータスオブジェクト。
     */
    final protected function retweet(stdClass $status) {
        try {
            $status = $this->getTwistOAuth()->post("statuses/retweet/{$status->id_str}");
            echo 'retweet(): Success ' . $status->id_str . "\n";
            return self::filterSingle($status);
        } catch (Exception $e) {
            trigger_error(
                'retweet(): ' . $e->getMessage(),
                E_USER_WARNING
            );
        }
    }
    
    /**
     * ふぁぼ公します。
     * textプロパティに含まれるHTML特殊文字はデコードされます。
     * 失敗時にはNULLがセットされ、エラーログに記録されます。
     * 
     * @param  $status ふぁぼ公対象のステータスオブジェクト。
     * @return         リツイートしたステータスオブジェクト。
     */
    final protected function favrt(stdClass $status) {
        try {
            $statuses = TwistOAuth::curlMultiExec(array(
                $this->getTwistOAuth()->curlPost('favorites/create', array('id' => $id)),
                $this->getTwistOAuth()->curlPost("statuses/retweet/$id"),
            ), true);
            echo 'favrt(): Success ' . $statuses[1]->id_str . "\n";
            return self::filterSingle($statuses[1]);
        } catch (Exception $e) {
            trigger_error(
                'favrt(): ' . $e->getMessage(),
                E_USER_WARNING
            );
        }
    }
    
    final protected function __construct() { }
    
    final protected function __destruct() {
        if ($this->file instanceof SplFileObject) {
            $this->file->ftruncate(0);
            $this->file->fwrite(json_encode(array(
                'ck' => $this->to->ck,
                'cs' => $this->to->cs,
                'ot' => $this->to->ot,
                'os' => $this->to->os,
                'lastIdDefault' => $this->lastIdDefault,
                'lastIds'       => $this->lastIds,
            )));
            $this->file->flock(LOCK_UN);
        }
    }
    
    private function filter($name, $statuses) {
        $lastIdStr =
            isset($this->lastIds[$name])
            ? $this->lastIds[$name]
            : $this->lastIdDefault
        ;
        $filtered = array();
        foreach ($statuses as $status) {
            if (isset($status->retweeted_status)) {
                $status = $status->retweeted_status;
            }
            if (bccomp($status->id_str, $lastIdStr) <= 0) {
                break;
            }
            if (isset($this->marked[$status->id_str])) {
                continue;
            }
            $filtered[] = self::filterSingle($status);
        }
        if ($filtered) {
            $this->lastIds[$name] = $filtered[0]->id_str;
            if (bccomp($filtered[0]->id_str, $this->lastIdDefault) > 0) {
                $this->lastIdDefault = $filtered[0]->id_str;
            }
        }
        return $filtered;
    }
    
    private static function filterSingle($status) {
        $status->text       = htmlspecialchars_decode($status->text);
        $status->user->name = str_replace(array('@', '＠'), '(at)', $status->user->name);
        return $status;
    }
    
}