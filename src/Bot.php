<?php

namespace mpyw\HardBotter;

use mpyw\Cowitter\ClientInterface;

class Bot implements IBotEssential, IBotHelper
{
    private $client;
    private $file;
    private $prev;
    private $marked = [];
    private $mark_limit = 10000;
    private $back_limit = 3600;
    private $get_error_mode = self::ERRMODE_EXCEPTION;
    private $post_error_mode = self::ERRMODE_WARNING;

    use Traits\CollectorTrait;
    use Traits\FollowManagerTrait;
    use Traits\TweetManagerTrait;
    use Traits\InterceptorTrait;
    use Traits\SharedUtilityTrait;

    /**
     * コンストラクタ
     * 副作用が大量にあるので注意
     */
    public function __construct(
        ClientInterface $client, $filename = 'stamp.json',
        $span = 0, $mark_limit = 10000, $back_limit = 3600
    ) {
        // ヘッダの送出
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }

        // エラー表示の設定
        error_reporting(-1);
        ini_set('log_errors', PHP_SAPI === 'cli');
        ini_set('display_errors', PHP_SAPI !== 'cli');

        // 重複起動防止
        $file = new \SplFileObject($filename, 'a+b');
        if (!$file->flock(LOCK_EX | LOCK_UN)) {
            throw new \RuntimeException('Failed to lock file.');
        }

        // JSONとして保存してあるデータを取得
        $json = $file->getSize() > 0
            ? json_decode($file->fread($file->getSize()), true)
            : [];

        // JSONに前回実行時刻が保存されていた時
        if (isset($json['prev'])) {
            // 十分に時間が空いたかどうかをチェック
            if (!static::expired($json['prev'], $span)) {
                throw new \RuntimeException('Execution span is not enough.');
            }
        }

        // JSONにマーク済みステータス一覧が記録されていたとき復元する
        if (isset($json['marked'])) {
            $this->marked = array_map('filter_var', (array)$json['marked']);
        }

        $this->client = $client;
        $this->file = $file;
        $this->prev = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('r');
        $this->mark_limit = $mark_limit;
        $this->back_limit = $back_limit;
    }

    /**
     * デストラクタ
     */
    public function __destruct()
    {
        $this->file->ftruncate(0);
        $this->file->fwrite(json_encode([
            'prev' => $this->prev,
            // 収まりきらない古い情報は破棄する
            'marked' => array_slice($this->marked, -$this->mark_limit, $this->mark_limit, true),
        ]));
        $this->file->flock(LOCK_UN);
    }

    /**
     * マーク
     */
    public function mark(\stdClass $status) {
        $this->marked[$status->id_str] = true;
    }

    /**
     * セッター・ゲッター
     */
    public function setGetErrorMode($mode) {
        $this->get_error_mode = $mode;
    }
    public function setPostErrorMode($mode) {
        $this->post_error_mode = $mode;
    }
    public function getGetErrorMode()
    {
        return $this->get_error_mode;
    }
    public function getPostErrorMode()
    {
        return $this->post_error_mode;
    }
    public function getMarkedStatusIds()
    {
        return $this->marked;
    }
    public function getBackLimitSeconds()
    {
        return $this->back_limit;
    }
    public function getMarkLimitCounts()
    {
        return $this->mark_limit;
    }
    public function getClient()
    {
        return $this->client;
    }

    /**
     * clone および serialize 対策
     */
    final public function __sleep()
    {
        throw new \BadMethodCallException('Instances are not serializable.');
    }
    final public function __clone()
    {
        throw new \BadMethodCallException('Instances are not clonable.');
    }
}
