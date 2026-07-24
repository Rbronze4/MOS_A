# MOS 環境構築ガイド

このシステムを初めて触る人が、自分のパソコンで MOS を動かせるようになるための手順書です。
**上から順番に実行すれば動きます。** 専門知識は不要です。

> 所要時間の目安：30〜60分（ダウンロード時間を含む）
> 対象OS：Windows

---

## MOS とは（30秒で理解する）

居酒屋向けの **モバイルオーダーシステム** です。3つの画面があります。

| 画面 | 誰が使う | 何をする |
| --- | --- | --- |
| **客側画面** | お客さん（スマホ） | QRを読んで、コースを選んで、料理を注文する |
| **スタッフ側画面** | 店員（タブレット） | 注文一覧の確認、提供、QR発行、代理注文 |
| **レジ連携API** | レジシステム（他社製） | MOSから注文を取得し、会計状況を書き戻す |

技術的には **PHP + JavaScript + MariaDB** で動く、フレームワークを使わないWebアプリです。

---

## 全体の流れ

```
STEP 1  XAMPP を入れる          … Webサーバー(Apache)とデータベース(MariaDB)を用意する
STEP 2  MOS のファイルを置く     … zipを解凍して htdocs に配置する
STEP 3  データベースを作る       … 空のDBを作り、SQLファイルを取り込む
STEP 4  PHPの設定を直す          … 時刻の設定（これを忘れると時間表示が狂います）
STEP 5  動作確認                … 画面が開けばゴール
```

（レジ連携も試す場合は、最後の「STEP 6（任意）」へ進んでください）

---

## STEP 1　XAMPP を入れる

XAMPP は、Webサーバーとデータベースがセットになった開発ツールです。

1. https://www.apachefriends.org/jp/ から **XAMPP for Windows** をダウンロードする
   - **PHP 8.2 以上**のものを選ぶ（このシステムは PHP 8.0 以上が必須です）
2. インストーラを実行する（設定はすべて初期値のままでOK）
3. インストール先を覚えておく（例：`C:\xampp`）
   - 以降この手順書では、この場所を **`XAMPPフォルダ`** と呼びます

### 起動する

1. **XAMPP Control Panel** を起動する
2. **Apache** の「Start」を押す
3. **MySQL** の「Start」を押す

両方の名前が **緑色** になれば成功です。

> **うまくいかないとき**
> - Apache が起動しない → 他のソフト（Skype、IISなど）がポート80を使っています。そちらを終了してください。
> - MySQL が起動しない → 別のMySQLがインストールされている可能性があります。Windowsのサービスから停止してください。

---

## STEP 2　MOS のファイルを置く

### 置き場所（重要）

MOS は **`XAMPPフォルダ\htdocs\MOS_A`** に置く必要があります。
`MOS_A` という名前と場所は、プログラム内で使われているため**変更できません**。

```
C:\xampp\htdocs\MOS_A\     ← ここに置く
├── SETUP.md               ← この手順書
├── README.md              ← 開発するときのルール
├── public\                ← ブラウザから見える入口
├── src\                   ← プログラム本体
├── DB\developing\         ← データベースのファイル
└── docs\                  ← その他の資料
```

### 2-1. zip を解凍する

配布された **`MOS_A.zip`** をダウンロードし、解凍します。

1. zipファイルを右クリック →**「すべて展開」**を選ぶ
2. 展開先はどこでもかまいません（デスクトップなど）

### 2-2. htdocs へ移動する

解凍してできたフォルダを、**`XAMPPフォルダ\htdocs`** の中へ移動します。

> ⚠️ **フォルダ名は必ず `MOS_A` にしてください。**
> 解凍ソフトによっては `MOS_A` の中にもう1つ `MOS_A` ができる（二重フォルダ）ことがあります。
> その場合は**内側のフォルダを取り出して**、`htdocs` の直下に置いてください。

正しく置けていれば、次のパスにファイルが存在します。

```
C:\xampp\htdocs\MOS_A\SETUP.md      ← このファイル
C:\xampp\htdocs\MOS_A\public\index.php
```

**確認方法**: エクスプローラーのアドレス欄に `C:\xampp\htdocs\MOS_A` と入力して開き、
`public` `src` `DB` フォルダが並んでいればOKです。

> 二重フォルダになっていると `C:\xampp\htdocs\MOS_A\MOS_A\public` のようになります。
> この状態では画面が開かず **404 Not Found** になります。

---

## STEP 3　データベースを作る

### 3-1. 空のデータベースを作る

1. XAMPP Control Panel の **MySQL** の行にある **「Admin」** を押す
   → ブラウザで **phpMyAdmin** が開きます
2. 左上の **「新規作成」** を押す
3. 以下を入力して **「作成」** を押す

   | 項目 | 入力する値 |
   | --- | --- |
   | データベース名 | `mos_a_system` |
   | 照合順序 | `utf8mb4_unicode_ci` |

> 名前は **必ず `mos_a_system`** にしてください。プログラムがこの名前で接続します。

### 3-2. データを取り込む

1. 左側の一覧から、作った **`mos_a_system`** をクリックする
2. 上部の **「インポート」** タブを押す
3. **「ファイルを選択」** を押し、以下のファイルを選ぶ

   ```
   C:\xampp\htdocs\MOS_A\DB\developing\ の中で一番新しい .sql ファイル
   （例：mos_a_system_20260721.sql）
   ```

4. 一番下の **「インポート」** を押す

「インポートは正常に終了しました」と出れば成功です。テーブルが19個できていることを確認してください。

> **必ず一番新しいファイルを選んでください。**
> 古いファイルを使うと、テーブルの構造が今のプログラムと合わず、
> 「カートに追加できない」などのエラーになります。

---

## STEP 4　PHPの設定を直す（重要・忘れやすい）

**この作業を飛ばすと、飲み放題の残り時間が数時間ずれて表示されます。**
XAMPPの初期設定では、PHPの時刻が日本時間になっていないためです。

1. `XAMPPフォルダ\php\php.ini` をメモ帳などで開く
2. `date.timezone` を検索する（ファイルの後半にあります）
3. 以下のように書き換えて保存する

   ```ini
   [Date]
   date.timezone=Asia/Tokyo
   ```

   > 初期値は `Europe/Berlin`（ドイツ時間）などになっています。

4. **XAMPP Control Panel で Apache を Stop → Start** する
   （php.ini はApache起動時に読み込まれるため、再起動しないと反映されません）

### 確認方法

`htdocs` に `tz.php` というファイルを作り、以下を書いて保存します。

```php
<?php echo date_default_timezone_get(), " / ", date('Y-m-d H:i:s');
```

ブラウザで `http://localhost/tz.php` を開き、
**`Asia/Tokyo`** と**今の日本時間**が表示されればOKです。確認できたらこのファイルは削除してください。

---

## STEP 5　動作確認

ブラウザで以下を開きます。

### 客側画面

```
http://localhost/MOS_A/public/customer?customer_id=1000001
```

メニュー画面が表示されればOKです。

> `customer_id` は「お客さん1組」を表す番号です。本番ではQRコードから渡されます。
> 動作確認では、上のように直接指定して開けます。

### スタッフ側画面

```
http://localhost/MOS_A/public/staff
```

ログイン画面が出ます。**店舗を選んでパスワードを入力**します。

| 項目 | 入力する値 |
| --- | --- |
| 店舗 | 一覧から選ぶ（例：緑橋本店） |
| パスワード | `password` |

> パスワードは全店舗共通で `password` です（開発用）。
> ログインIDの入力欄はありません。店舗を選ぶだけです。

ログインするとホーム画面が開きます。ここから注文一覧・QR発行などに進めます。

> ⚠️ **このパスワードは開発専用です。**
> 実際の店舗で使う場合は、必ず別のパスワードに変更してください。
> 変更方法は「補足：パスワードを変更する」を参照してください。

**ここまでできれば環境構築は完了です。**

---

## STEP 6（任意）　レジ連携も試す場合

レジシステム（他社製）と繋いで試したい場合のみ実施してください。
**MOS単体で開発するだけなら不要です。**

レジは MOS を `http://localhost:8080` に探しに来るため、MOSを8080番でも配信します。

### 6-1. Apache に 8080 番を追加する

`XAMPPフォルダ\apache\conf\httpd.conf` を開き、`Listen 80` の下に1行足します。

```apache
Listen 80
Listen 8080
```

### 6-2. 仮想ホストを追加する

`XAMPPフォルダ\apache\conf\extra\httpd-vhosts.conf` の末尾に以下を追加します。

> 🔴 **`★ここを自分のパスに★` の2箇所を、自分のXAMPPの場所に書き換えてください。**
> 例：`C:/xampp/htdocs/MOS_A/public`（区切りは `/` です。`\` ではありません）

```apache
<VirtualHost *:8080>
    DocumentRoot "★ここを自分のパスに★/MOS_A/public"

    <Directory "★ここを自分のパスに★/MOS_A/public">
        Options FollowSymLinks
        AllowOverride None
        Require all granted

        DirectoryIndex index.php

        RewriteEngine On
        RewriteBase /
        RewriteRule ^$ index.php [QSA,L]
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^ index.php [QSA,L]
    </Directory>
</VirtualHost>
```

### 6-3. 反映して確認する

1. Apache を Stop → Start する
2. コマンドプロンプトで以下を実行する

```bash
curl -X POST http://localhost:8080/api/orders -H "Content-Type: application/json" -d "{\"method\":\"getOrders\"}"
```

注文データのJSONが返ればOKです。

> レジ本体（`regi`）は他社のシステムのため、この配布物には含まれていません。
> **MOS単体でもこのSTEP 6まで含めて動作確認できます**（上のcurlで確認できます）。
> レジと繋いだ状態で動かすには、レジ側の配布物を別途入手して
> `htdocs\regi` に置いてください。仕様の詳細は `docs/regi-api-orders-design.md` にあります。

---

## 困ったときは

| 症状 | 原因と対処 |
| --- | --- |
| 画面が真っ白／500エラー | MySQLが起動していない。XAMPPで MySQL を Start する |
| 「商品がありません」と出る | STEP 3 のインポートができていない。phpMyAdmin でテーブルが19個あるか確認する |
| カートに追加できない | 取り込んだSQLファイルが古い。`DB/developing` の**一番新しい**ファイルで取り込み直す |
| 残り時間が数時間ずれる | STEP 4 をやっていない。php.ini を直して **Apacheを再起動** する |
| 404 Not Found | 置き場所が違う。`C:\xampp\htdocs\MOS_A\public\index.php` が存在するか確認する。`MOS_A\MOS_A\...` と二重になっていたら、内側のフォルダを取り出して置き直す（STEP 2-2） |
| ログインできない | パスワードは `password`。DBを入れ直した場合は元に戻っている。変更したい場合は「補足：パスワードを変更する」を参照 |
| 画面の見た目が崩れる | ブラウザが古いCSSを覚えている。**Ctrl + Shift + R** で強制再読み込みする |
| Apache が起動しない | ポート80が使われている。他のソフトを終了するか、XAMPPのポートを変更する |

---

## 補足：知っておくと良いこと

### データベースの接続設定

初期設定では以下で接続します（XAMPPの初期状態そのままです）。

| 項目 | 値 |
| --- | --- |
| ホスト | `localhost` |
| データベース名 | `mos_a_system` |
| ユーザー | `root` |
| パスワード | （なし） |

変更したい場合は、環境変数（`MOS_DB_HOST` `MOS_DB_NAME` `MOS_DB_USER` `MOS_DB_PASSWORD` など）で
上書きできます。ソースコードを書き換える必要はありません。

### フォルダの役割

| フォルダ | 中身 |
| --- | --- |
| `public/` | ブラウザからアクセスできる入口。画像・CSS・JavaScript |
| `src/Controllers/` | 画面ごとの処理の振り分け |
| `src/Models/` | データベースとのやり取り |
| `src/Views/` | 画面の見た目（HTML） |
| `src/Routes/web.php` | どのURLでどの処理を動かすかの一覧 |
| `DB/developing/` | データベースのバックアップファイル |
| `docs/` | 設計資料（レジ連携の仕様など） |

### パスワードを変更する

スタッフ画面のパスワードは、DBに**ハッシュ化して**保存されています。
そのため、DBに直接 `password` と書いても**ログインできません**。必ず以下の手順で変更してください。

**1. 新しいパスワードのハッシュを作る**

コマンドプロンプトで実行します（`mos1234` の部分を好きなパスワードに変えてください）。

```bash
C:\xampp\php\php.exe -r "echo password_hash('mos1234', PASSWORD_DEFAULT);"
```

`$2y$10$...` で始まる60文字の文字列が表示されます。これをコピーします。

**2. DBに反映する**

phpMyAdmin の「SQL」タブで、以下を実行します。

```sql
-- 全店舗のパスワードをまとめて変更する場合
UPDATE store_accounts SET password_hash = 'ここに1でコピーしたハッシュ';

-- 特定の店舗だけ変更する場合
UPDATE store_accounts SET password_hash = 'ここに1でコピーしたハッシュ' WHERE store_id = 'MH';
```

> ⚠️ ハッシュには `$` が含まれます。**必ずシングルクォート `'` で囲んでください。**
> ダブルクォートで囲むと `$` が別の意味になり、正しく保存されないことがあります。

### 開発するときのルール

チームで開発するためのルールが `README.md` にまとまっています。
**コードを書き始める前に必ず読んでください。**
