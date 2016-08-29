<?php

namespace mpyw\HardBotter\Traits;

use mpyw\Co\Co;

trait InterceptorTrait
{
    abstract public function getClient();
    abstract public function getGetErrorMode();
    abstract public function getPostErrorMode();
    abstract public function getMarkedStatusIds();
    abstract public function getBackLimitSeconds();
    abstract protected static function expired($past, $interval);

    /**
     * 間接的に mpyw\Cowitter\Client のメソッドをコールするメソッド
     */
    public function __call($method, array $args)
    {
        $client = $this->getClient();
        $callback = [$client, $method];
        if (!is_callable($callback)) {
            throw new \BadMethodCallException("Call to undefined method mpyw\Cowitter\Client::$method()");
        }
        return ((new \ReflectionMethod($client, $method))->isGenerator())
            ? $this->callAsync($method, $callback, $args)
            : $this->call($method, $callback, $args);
    }

    /**
     * 同期処理を __call() から呼ぶ場合
     */
    protected function call($method, $callback, $args)
    {
        try {
            return $this->filter($method, call_user_func_array($callback, $args));
        } catch (\RuntimeException $e) {
            return $this->handleException($method, $e);
        }
    }

    /**
     * 非同期処理を __call() から呼ぶ場合
     */
    protected function callAsync($method, $callback, $args)
    {
        try {
            yield Co::RETURN_WITH => $this->filter($method, (yield call_user_func_array($callback, $args)));
        } catch (\RuntimeException $e) {
            yield Co::RETURN_WITH => $this->handleException($method, $e);
        }
        // @codeCoverageIgnoreStart
    }
    // @codeCoverageIgnoreEnd

    /**
     * 結果をフィルタリングするメソッド
     */
    protected function filter($method, $variable)
    {
        // get, post, postMultipart 以外のメソッドは処理しない
        if (!preg_match('/^(?:get2?|post|postMultipart)(?:Async)?$/i', $method)) {
            return $variable;
        }

        // 一度連想配列に変換した後再帰的にフィルタリング処理をかけ，もとに戻す
        $variable = (array)json_decode(json_encode($variable), true);
        array_walk_recursive($variable, [get_class(), 'recursiveFilterCallback']);
        $variable = json_decode(json_encode($variable));

        // get 以外のメソッドはこれ以上処理しない
        if (!preg_match('/^get2?(?:Async)?$/i', $method)) {
            return $variable;
        }

        // 取得してきたものがステータス配列あるいはステータス配列を含むオブジェクトの場合，
        // その配列を処理対象にする
        if (is_array($variable)) {
            $list = &$variable;
        } elseif (isset($variable->statuses) && is_array($variable->statuses)) {
            $list = &$variable->statuses;
        } else {
            return $variable;
        }

        $marked = $this->getMarkedStatusIds();
        $limit = $this->getBackLimitSeconds();
        foreach ($list as $i => $status) {
            // GET users/lookup などは無視
            if (!isset($status->text, $status->id_str, $status->created_at)) {
                continue;
            }
            // マークされているツイートと期限切れのツイートを除外
            if (isset($marked[$status->id_str]) || static::expired($status->created_at, $limit)) {
                unset($list[$i]);
            }
        }
        // キーを振り直す
        $list = array_values($list);

        return $variable;
    }

    /**
     * 例外ハンドラ
     */
    private function handleException($method, \RuntimeException $e)
    {
        $get_error_mode = $this->getGetErrorMode();
        $post_error_mode = $this->getPostErrorMode();

        if (preg_match('/^get2?(?:Out)?(?:Async)?$/i', $method)) {
            // getのとき
            if ($get_error_mode & self::ERRMODE_WARNING) {
                trigger_error($e->getMessage(), E_USER_WARNING);
            }
            if ($get_error_mode & self::ERRMODE_EXCEPTION) {
                throw $e;
            }
            return false;
        }

        if (preg_match('/^(?:(?:post|postMultipart)(?:Out)?|(?:upload(?:|Image|AnimeGIF|Video)))(?:Async)?$/i', $method)) {
            // postのとき
            if ($post_error_mode & self::ERRMODE_WARNING) {
                trigger_error($e->getMessage(), E_USER_WARNING);
            }
            if ($post_error_mode & self::ERRMODE_EXCEPTION) {
                throw $e;
            }
            return false;
        }

        // それ以外
        throw $e;
    }

    /**
     * array_walk_recursive 専用
     */
    protected static function recursiveFilterCallback(&$value, $key)
    {
        if ($key === 'text') {
            // ツイート本文のHTML特殊文字をデコードする
            $value = htmlspecialchars_decode($value, ENT_NOQUOTES);
        } elseif ($key === 'name' || $key === 'description') {
            // プロフィールの名前および詳細に含まれるスクリーンネームの「@」を「(at)」に置換する
            $value = preg_replace('/@(?=\w{1,15}+(?!\w))/', '(at)', $value);
        }
    }
}
