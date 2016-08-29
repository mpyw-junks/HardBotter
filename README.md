# HardBotter

「PHPが書けないと作れない」Cron系のTwitterボット作成支援ライブラリです．自由な書き方ができ，カスタマイズ性が高いのが特長です．

- **PHP 5.5 以降** で動作します．
- Twitterクライアントとしては，[TwistOAuth](https://github.com/mpyw/TwistOAuth) の後継である **[Cowitter](https://github.com/mpyw/cowitter)** を利用します．Generatorを活用して非同期処理をバリバリ書けます．
- 「PHPが書けなくても作れる」と称する[EasyBotter](http://pha22.net/twitterbot/)とは何の関係もありません．  

# インストール

`composer require mpyw/hardbotter:^1.0`

# 主なソースファイル

## src/Bot.php

`Bot` クラスが記述されたファイルです．

- このファイルを読む必要はありません．
- このクラスは以下に示す2つのインタフェースを実装しています．

## [src/IBotEssential.php](https://github.com/mpyw/HardBotter/blob/master/src/IBotEssential.php)

`Bot` クラスが実装している必須メソッド群です．

- 説明書となるのでソースを読んでください．

## [src/IBotHelper.php](https://github.com/mpyw/HardBotter/blob/master/src/IBotHelper.php)

`Bot` クラスが実装しているヘルパーメソッド群です．

- 説明書となるのでソースを読んでください．
- 必須ではありませんが，使用頻度が高いものも多く含みます．

# サンプル

サンプルが `examples/run.php` にあります．設定ファイルには [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv) を利用しています．

`examples/.env` を生成して

```
CONSUMER_KEY="****"
CONSUMER_SECRET="****"
ACCESS_TOKEN="****"
ACCESS_TOKEN_SECRET="****"
TIMEZONE="Asia/Tokyo"
```

のように編集した上で， `examples/run.php` を定期実行してください．  

# 備考

このライブラリはcronで定期的に動作させるBot向けのものですが，ストリーミングAPIを利用したリアルタイム系のBotにも対応できます．
