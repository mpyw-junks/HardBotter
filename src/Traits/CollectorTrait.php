<?php

namespace mpyw\HardBotter\Traits;

use mpyw\Co\CoInterface;
use Aza\Components\Math\BigMath;

/**
 * @method mixed get($endpoint, array $params = [])
 * @method \Generator getAsync($endpoint, array $params = [])
 */
trait CollectorTrait
{
    abstract public function __call($method, array $args);

    public function collect($endpoint, $followable_page_count, array $params = [])
    {
        // カーソルに-1を指定する（不要な場合に余分なパラメータとしてつけても問題ない）
        $params += ['cursor' => '-1'];
        // 初回の結果を取得
        if (false === $result = $this->get($endpoint, $params)) {
            return false;
        }
        // 整形結果と次のリクエストに必要なパラメータを取得
        list($formatted, $next_params) = static::getFormattedResultAndNextParams($result, $params);
        // 次のリクエストが不必要であれば整形結果を返す
        if ($next_params === null || $followable_page_count < 1) {
            return $formatted;
        }
        // 次のリクエストを実行してマージした結果を返す
        $children = $this->collect($endpoint, $followable_page_count - 1, $next_params);
        return $children !== false ? array_merge($formatted, $children) : false;
    }

    public function collectAsync($endpoint, $followable_page_count, array $params = [])
    {
        // カーソルに-1を指定する（不要な場合に余分なパラメータとしてつけても問題ない）
        $params += ['cursor' => '-1'];
        // 初回の結果を取得
        if (false === $result = (yield $this->getAsync($endpoint, $params))) {
            yield CoInterface::RETURN_WITH => false;
        }
        // 整形結果と次のリクエストに必要なパラメータを取得
        list($formatted, $next_params) = static::getFormattedResultAndNextParams($result, $params);
        // 次のリクエストが不必要であれば整形結果を返す
        if ($next_params === null || $followable_page_count < 1) {
            yield CoInterface::RETURN_WITH => $formatted;
        }
        // 次のリクエストを実行してマージした結果を返す
        $children = (yield $this->collectAsync($endpoint, $followable_page_count - 1, $next_params));
        yield CoInterface::RETURN_WITH => $children !== false ? array_merge($formatted, $children) : false;
        // @codeCoverageIgnoreStart
    }
    // @codeCoverageIgnoreEnd

    protected static function getFormattedResultAndNextParams($result, array $params)
    {
        $is_statuses = is_array($result);
        $is_searches = isset($result->statuses) && is_array($result->statuses);
        $is_cursored = isset($result->next_cursor_str);

        // GET statuses/home_timeline や GET search/tweets など
        if ($is_statuses || $is_searches) {
            $formatted = $is_statuses ? $result : $result->statuses;
            $math = BigMath::createFromServerConfiguration();
            return [
                $formatted,
                $formatted
                    ? ['max_id' => $math->subtract(end($formatted)->id_str, 1)] + $params
                    : null
            ];
        }

        // GET followers/ids など
        if ($is_cursored) {
            // 配列であるプロパティの名前を求める
            $prop = key(array_filter((array)$result, 'is_array'));
            $formatted = $result->$prop;
            return [
                $formatted,
                $result->next_cursor_str
                    ? ['cursor' => $result->next_cursor_str] + $params
                    : null
            ];
        }

        // それ以外の場合はそもそもこのメソッドを使うべきではない
        throw new \BadMethodCallException('Incompatible endpoint.');
    }
}
