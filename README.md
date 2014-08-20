HardBotter
==========

PHPが書けないと作れないTwitterボット作成スクリプトです。  
**[EasyBotter](http://pha22.net/twitterbot/)**とは何の関係もありません。  
ある程度**[TwistOAuth](https://github.com/mpyw/TwistOAuth)**を使い慣れている人が対象です。

バージョン 0.1.1

動作環境
=========

**[TwistOAuth](https://github.com/mpyw/TwistOAuth)**が動作する環境。

ファイル一覧
===========

- **HardBotterModel.php**<br />HardBotterModelクラスが記述されたファイルです。<br /><ins>**抽象クラス** なので、このファイルを編集する必要はありません。</ins>
- **MyHardBotter.php**<br />具象クラスとしての実装例です。このファイルは必ず編集してください。<br />サンプルが書かれていますが、まっさらな状態から書き始めても構いません。
- **TwistOAuth.php**<br />**[TwistOAuth](https://github.com/mpyw/TwistOAuth)**ライブラリを同梱しました。最新版である保証はありません。

メソッド一覧
===========

HardBotterModelクラスについて記述します。



### abstract protected getTwistOAuth()

<ins>継承先のクラスで実装してください。</ins>  
このボットが使用するTwistOAuthオブジェクトを返します。

#### 返り値

TwistOAuthオブジェクト。



### abstract protected action()

<ins>継承先のクラスで実装してください。</ins>  
`run()` メソッドを実行したときに呼び出されます。



### final public static run()

ログファイルを読み込み、ボットスクリプトを実行します。<br />
<ins>**クラス外から呼び出すのはこのメソッドのみです。**</ins><br />
このメソッドは以下のことを行います。

- `Content-Type: text/plain; charset=utf-8` ヘッダを送出する。
- タイムアウト秒数を無制限に設定する。
- ログファイルからデータを読み出す。
- ログファイルが存在しないときは新規作成する。
- 新規作成時には、過去のツイートに対する暴走を防ぐためにテストツイートを行う。<br />他の人から見えないように `@tos` に対するリプライとする。
- ログファイルに最後にチェックしたツイートのステータスIDを記録する。

```php
(void) MyHardBotter::run($filename)
```

#### 引数

- (string) __*$filename*__<br />ログファイルの名前。



### final protected static match()

ツイート内容に対して正規表現でのマッチングを行い、コールバック関数から結果を得ます。<br />
他人のツイートをもとに自分のツイートの本文を生成したいときに利用できます。

```php
(mixed) MyHardBotter::match(stdClass $status, array $pairs)
(mixed) $this->match(stdClass $status, array $pairs)
```

#### 引数

- (string) __*$status*__<br />ステータスオブジェクト。
- (array) __*$pairs*__<br />**「正規表現 => コールバック関数」** の形の配列。<br />コールバック関数の第1引数は **ステータスオブジェクト** になります。<br />コールバック関数の第2引数は **マッチ結果の配列** になります。<br />以下に例を示します。

```php
$pairs = array(
    '/おはよう|こんにちは|こんばんは/' => function ($s, $m) {
        return $s->user->name . 'さん' . $m[0] . '！';
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
);
```

先頭にあるものほど優先されます。

#### 返り値

コールバック関数の返り値を返します。<br />
マッチするものが無かったときは `NULL` になります。


### final protected mark()

ツイートに対して反応済みであることを明示します。

```php
(void) $this->mark(stdClass $status)
```

#### 引数

- (string) __*$status*__<br />ステータスオブジェクト。


### final protected getLatestHome()<br />final protected getLatestMentions()<br />final protected getLatestSearch()

ホーム/メンション/検索タイムラインからツイートを読み込みます。

- 過去のツイートは除外されます。
- 反応済みであると明示したツイートは除外されます。
- ツイート本文のHTML特殊文字はデコードされます。
- ユーザー名に含まれる `@` `＠` は `(at)` に置換されます。

#### 引数

- (string) __*$q*__<br />検索クエリ。

```php
(array) $this->getLatestHome()
(array) $this->getLatestMentions()
(array) $this->getLatestSearch($q)
```

#### 返り値

ツイートしたステータスオブジェクトを返します。  
失敗したときには **WARNING** を発生し、 **空配列** を返します。


### final protected tweet()<br />final protected tweetWithMedia()

ツイートを実行します。

```php
(stdClass) $this->tweet($text, $in_reply_to_status_id = null)
(stdClass) $this->tweetWithMedia($text, $media_path, $in_reply_to_status_id = null)
```

#### 引数

- (string) __*$text*__<br />ツイート本文。
- (string) __*$media\_path*__<br />画像ファイルへのパス。絶対パスを推奨。
- (string) __*[$in\_reply\_to\_status\_id]*__<br />返信先のステータスID。

#### 返り値

ツイートしたステータスオブジェクトを返します。  
失敗したときには **WARNING** を発生し、 `NULL` を返します。


### final protected reply()<br />final protected replyWithMedia()

リプライを実行します。このメソッドは `tweet()` のラッパーメソッドです。

```php
(stdClass) $this->reply($status, $text)
(stdClass) $this->replyWithMedia($status, $text, $media_path)
```

#### 引数

- (stdClass) __*$status*__<br />返信先のステータスオブジェクト。
- (string) __*$text*__<br />ツイート本文。<br />**<ins>`@screen_name ` が自動的に先頭に付加されます。</ins>**
- (string) __*$media\_path*__<br />画像ファイルへのパス。絶対パスを推奨。

#### 返り値

ツイートしたステータスオブジェクトを返します。  
失敗したときには **WARNING** を発生し、 `NULL` を返します。


### final protected favorite()<br />final protected retweet()<br />final protected favrt()

ふぁぼ/公式リツイート/ふぁぼ公(並列リクエスト)を実行します。

```php
(stdClass) $this->favorite(stdClass $status)
(stdClass) $this->retweet(stdClass $status)
(stdClass) $this->favrt(stdClass $status)
```

#### 引数

- (stdClass) __*$status*__<br />アクション対象のステータスオブジェクト。

#### 返り値

以下のいずれかを返します。

- ふぁぼ時、ふぁぼ対象であったステータスオブジェクト。
- 公式リツイート時、<ins>リツイートで生成された</ins>ステータスオブジェクト。
- ふぁぼ公時、<ins>リツイートで生成された</ins>ステータスオブジェクト。

失敗したときには **WARNING** を発生し、 `NULL` を返します。