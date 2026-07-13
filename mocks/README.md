# mocks — レイアウト確認用サンドボックス

本体（`public/` `src/`）に影響を与えずに、画面レイアウト（CSS / HTML構造）を
試すための作業用フォルダです。XAMPP 経由でブラウザ表示して確認します。

---

## アクセス方法

XAMPP（Apache）を起動した状態で、以下のURLにアクセスします。

| 画面 | URL |
|---|---|
| 客側画面 | `http://localhost/MOS_A/mocks/customer.php` |
| スタッフ ダッシュボード | `http://localhost/MOS_A/mocks/staff.php` |
| スタッフ注文（卓番号入力） | `http://localhost/MOS_A/mocks/staff-order.php` |
| スタッフ注文（メニュー） | `http://localhost/MOS_A/mocks/staff-order.php?screen=menu&tableNo=5&plan=standard` |

> CSSを編集しても反映されない場合は `Ctrl + F5`（スーパーリロード）でキャッシュを更新してください。

---

## フォルダ構成

```
mocks/
├── README.md          … このファイル
├── _data.php          … 共通ダミーデータ（本体Modelが返す構造と同じ形）
├── customer.php       … 客側の入口
├── staff.php          … スタッフ ダッシュボードの入口
├── staff-order.php    … スタッフ注文の入口
├── css/               … 編集対象（public/assets/css のコピー）
└── Views/             … 編集対象（src/Views のコピー）
```

---

## 仕組み

各入口PHP（`customer.php` など）は、本体コントローラーの代わりに以下を行います。

1. `_data.php` のダミーデータを読み込む（**DBには接続しない**）
2. **CSS は `mocks/css/...` を参照**（＝ここを編集すると即反映）
3. **JS は本体の `public/assets/js/...` を流用**
4. **画像も本体の `public/assets/images/...` を流用**
5. `mocks/Views/layouts/app.php` を読み込んで描画

---

## ルール

### やってよいこと（本体に影響なし）
- `mocks/css/` 配下のCSS編集 … 色・余白・フォント・レスポンシブなど見た目全般
- `mocks/Views/` 配下のPHP/HTML編集 … 要素の配置・枠・ヘッダー・テーブル見出しなど構造

### 注意が必要なこと
- **JS は本体と共有**しています。`public/assets/js/` を編集すると **本体にも影響します**。
  mocks 上でJSの挙動まで安全に試したい場合は、別途JSもコピーして参照先を切り替える必要があります。
- **一覧の行やカード本体のHTML構造はJSで動的生成**されています（下表）。
  これらの「タグの組み換え」は mocks のPHP編集では変わりません（CSSによる見た目調整は効きます）。

| JSで生成される主なレイアウト | 生成元 |
|---|---|
| 客側メニューカード / カテゴリタブ | `public/assets/js/customer/modules/menu.js` |
| 客側カート・履歴の行 | `public/assets/js/customer/modules/cart-history.js` |
| 客側プラン確認モーダルの金額・内容 | `public/assets/js/customer/modules/plans.js` |
| スタッフ注文一覧テーブルの行 | `public/assets/js/staff/dashboard/orders.js` |
| スタッフ商品管理テーブルの行 | `public/assets/js/staff/dashboard/products.js` |
| スタッフ注文カートの行 | `public/assets/js/staff/order-menu.js` |

### 本体への反映
- mocks で確定したCSS/HTMLは、**手動で本体（`public/assets/css/` `src/Views/`）へ反映**してください。
- mocks と本体は自動同期されません。

---

## 本体と同期するとき

本体（`public/assets/css/` `src/Views/`）が更新されたら、mocksへコピーして最新化します。

```bash
# プロジェクトルートで実行
cp -r public/assets/css/. mocks/css/
cp -r src/Views/. mocks/Views/
```

コピー後は `_data.php` と各入口PHPの確認が必要です。
**本体のModelが返すデータ構造や、Controllerがビューへ渡す変数が変わっている場合**、
mocks側もそれに合わせないと画面が正しく動きません。

| mocks | 本体 | 合わせるもの |
|---|---|---|
| `mocks/css/` | `public/assets/css/` | ファイルごとコピー |
| `mocks/Views/` | `src/Views/` | ファイルごとコピー |
| `_data.php` | 各Model（`MenuModel` / `StaffOrderModel` / `PlanModel` など） | **返り値のキー**を合わせる |
| `customer.php` / `staff.php` / `staff-order.php` | 各Controller | **ビューへ渡す変数**を合わせる |

### 動作確認

同期後は、各画面をブラウザで開いてPHPのエラーや警告が出ないことを確認してください。
