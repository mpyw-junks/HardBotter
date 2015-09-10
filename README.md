HardBotter
==========

PHPが書けないと作れないTwitterボット作成スクリプトです。  
**[EasyBotter](http://pha22.net/twitterbot/)**とは何の関係もありません。  
ある程度**[TwistOAuth](https://github.com/mpyw/TwistOAuth)**を使い慣れている人が対象です。

インストール
=========

`composer require mpyw/hardbotter`

ソースファイル
============

### src/Bot.php

`Bot` クラスが記述されたファイルです。

- このファイルを読む必要はありません。
- このクラスは以下に示す2つのインタフェースを実装しています。

### [src/IBotEssential.php](https://github.com/mpyw/HardBotter/blob/master/src/IBotEssential.php)

`Bot` クラスが実装している必須メソッド群です。

- 説明書となるのでソースを読んでください。

### [src/IBotHelper.php](https://github.com/mpyw/HardBotter/blob/master/src/IBotEssential.php)

`Bot` クラスが実装しているヘルパーメソッド群です。

- 説明書となるのでソースを読んでください。
- 必須ではありませんが、使用頻度が高いものも多く含みます。

サンプル
=======

`example/config.ini` を編集した上で `example/run.php` を定期実行してください。
なおこのライブラリはcronで定期的に動作させるBot向けのものですが、ストリーミングAPIを利用したリアルタイム系のBotにも対応できます。
