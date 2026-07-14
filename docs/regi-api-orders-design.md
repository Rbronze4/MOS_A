# レジ連携 API `/api/orders` 設計書

> 作成日: 2026-07-14
> 対象: MOS ⇔ レジ（POS）システム間の注文連携 API
> 目的: 実装前に契約・マッピング・リスク・未決定事項をチームで共有する
> ステータス: **設計のみ（未実装）**

## 前提

- レジ側の契約は、参照用リポジトリの実装から確定させた。
  - `参照用/regi/src/Lib/MosOrdersApi.php` — レジが投げるリクエストと期待するレスポンス
  - `参照用/regi/src/Lib/MosApiClient.php` — 通信方法（JSON POST）
  - `参照用/api_test_tool/mos_test/validators.py` — レスポンスの型契約
  - `参照用/api_test_tool/mos_test/hash_util.py` — ハッシュ生成規則
- MOS 側には **`/api/orders` のルートが存在しない**。レジからの入口がゼロなので、連携は現状まったく成立していない。
- 一方で DB は既にレジ寄りに整備済み。`customers.billing_status` は `tinyint`（ビットマスク）、`customers.order_hash` `varchar(64)` の列も存在する（ただし現状は全件未使用）。

---

## 1. 通信の前提（設定のみで直る問題）

レジ側 `参照用/regi/src/Config/mos.php` の `base_url` が `http://localhost:8080` になっている。
MOS は XAMPP の `/MOS_A/public` 配下で動作しており、レジは `base_url + "/api/orders"` を叩く。

したがって **レジ側の設定を下記に変更する必要がある**。Apache 側の設定変更は不要で、既存ルータにそのまま届く。

```php
'base_url' => 'http://localhost/MOS_A/public',
```

---

## 2. 契約サマリ

`POST /api/orders`（`Content-Type: application/json`）に対し、`method` で 2 種類を受け付ける。

| method | リクエスト | 正常レスポンス |
| --- | --- | --- |
| `getOrders` | `customerId`(7桁/null), `billStatus`(1〜15/null), `fromTime`, `toTime` | 注文オブジェクトの **JSON 配列** |
| `updateStatus` | `customerId`, `hash`(null可), `billStatus` | **HTTP 200 ＋ 空ボディ** |

### billStatus（ビットマスク）

| 値 | 意味 |
| --- | --- |
| 1 | 受付中 |
| 2 | 会計済み |
| 4 | 未収金 |
| 8 | 会計中 |

`getOrders` では複合指定（例: 受付中＋会計中 = `9`）でフィルタ、`updateStatus` では単一状態の設定に使う。

### hash の意味

単なるチェックサムではなく、**「注文内容が変わっていないか」を判定する楽観ロック**として機能する。

1. レジが `getOrders` で注文と `hash` を受け取る
2. レジが会計画面を表示している間に、客が追加注文するかもしれない
3. レジが `updateStatus` で受け取った `hash` を送り返す
4. MOS が現在の DB から `hash` を再計算し、**一致しなければ `ORDER_NOT_FOUND` を返して会計を弾く**

---

## 3. 追加するファイル（既存画面には影響しない）

| ファイル | 役割 |
| --- | --- |
| `src/Routes/web.php` | `POST /api/orders` を 1 ルート追加 |
| `src/Controllers/ApiOrderController.php` | JSON の受け取り・検証・エラー整形。**スタッフセッション認証は通さない**（レジはブラウザではないため） |
| `src/Models/ApiOrderModel.php` | SQL・階層の平坦化・ハッシュ計算 |

スタッフ側・顧客側の既存画面には一切手を入れない。

---

## 4. getOrders のデータマッピング

MOS は `顧客 → セッション → 注文 → 明細` の 4 階層を持つ（例: 顧客 1000001 は注文 4 件・明細 12 件）。
一方レジは **1 顧客 = 1 オブジェクト + `items[]`** を期待するため、平坦化が必要。

```
customers c
  JOIN sessions s                 ON s.customer_id  = c.customer_id
  JOIN orders o                   ON o.session_id   = s.session_id
  JOIN order_details od           ON od.order_id    = o.order_id
  JOIN products p                 ON p.product_id   = od.product_id
  LEFT JOIN product_categories pc ON pc.category_id = p.category_id
```

| レジの項目 | MOS の取得元 | 変換 |
| --- | --- | --- |
| `storeId` | `sessions.store_id` | そのまま（2 文字） |
| `customerId` | `customers.customer_id` | **7 桁ゼロ埋めの文字列** |
| `entryTime` | `sessions.started_at` の最小値 | ISO8601 `Y-m-d\TH:i:s` |
| `billStatus` | `customers.billing_status` | int（既にビットマスク） |
| `hash` | — | 第 5 章の規則で計算 |
| `items[].orderTime` | `order_details.ordered_at` | ISO8601 |
| `items[].menuName` | `order_details.ordered_product_name` | 注文時スナップショット済み |
| `items[].unitPrice` | `order_details.ordered_unit_price` | int・**税抜** |
| `items[].taxRate` | `products.tax_rate`（JOIN） | **`decimal(5,2)` → int に変換（後述）** |
| `items[].orderQty` | `order_details.quantity` | int |
| `items[].offerQty` | `order_details.provided_quantity` | int |
| `items[].categoryName` | `product_categories.category_name` | null 可 |

### taxRate の int 変換（必須）

契約は `taxRate` が **int 必須**（`validators.py` の `assert isinstance(item["taxRate"], int)`）。
一方 MOS の `products.tax_rate` は `decimal(5,2)` で、PDO は文字列 `"10.00"` として返す。
**そのまま返すと契約違反でレジ側の検証に落ちる**ため、`10.00 → 10` / `8.00 → 8` と int に変換して出力する。

> 制約: 契約が int である以上、`8.5%` のような小数税率は表現できない。現状は 10% / 8% のみなので実害はないが、将来小数税率を扱う場合はレジ側と契約の再調整が必要。

---

## 5. ハッシュの計算規則

`参照用/api_test_tool/mos_test/hash_util.py` に準拠する。対象は以下の 4 項目で、
**`categoryName` はハッシュに含まれない**点に注意。

```
{
  "customerId": ...,
  "entryTime":  ...,
  "items": [
    { "menuName": ..., "offerQty": ..., "orderQty": ...,
      "orderTime": ..., "taxRate": ..., "unitPrice": ... }
  ],
  "storeId": ...
}
```

これを正規化 JSON にして SHA-256（16 進小文字）。PHP 側で Python の
`json.dumps(ensure_ascii=False, separators=(",", ":"), sort_keys=True)` と同等にするには:

- `JSON_UNESCAPED_UNICODE` … `ensure_ascii=False` 相当
- `JSON_UNESCAPED_SLASHES` … Python は `/` をエスケープしないため必須
- **キーをアルファベット順に並べる** … `sort_keys=True` 相当
- 区切り文字は PHP のデフォルトが既に `,` `:`（スペースなし）で一致するため対応不要

> なお `hash_util.py` には「MOSのハッシュと一致することを保証しない」と明記されており、
> 厳密には **MOS 内で自己一貫していれば動作する**。ただし将来レジ側で照合される可能性を考え、
> 上記規則に合わせておく。

---

## 6. updateStatus の処理フロー

1. `customerId` で顧客を引く。存在しなければ `ORDER_NOT_FOUND`
2. `hash` が null でなければ、**現在の DB から再計算して比較**。不一致なら `ORDER_NOT_FOUND`
3. 一致したら `customers.billing_status` を更新し、確定したハッシュを
   **既存の `customers.order_hash` 列に保存**（現在は全件空で未使用のため、ここで初めて使い道が生まれる）
4. **HTTP 200 ＋ 空ボディ**を返す

---

## 7. エラー応答（すべて HTTP 400）

ボディは `{"errorCode": "...", "message": "..."}` の形式で返す。

| 状況 | errorCode |
| --- | --- |
| JSON として壊れている | `INVALID_JSON_FORMAT` |
| `method` が無い / 未知の値 | `INVALID_REQUEST` |
| customerId が 7 桁数字でない、時刻が ISO8601 でない | `INVALID_PARAMETER` |
| billStatus が 1〜15 の範囲外 | `INVALID_BILL_STATUS` |
| 顧客が見つからない / hash 不一致 | `ORDER_NOT_FOUND` |

---

## 8. 採用した方針と、その代償

税率・カテゴリ名の持ち方は **「`products` から都度 JOIN する（DB スキーマ変更なし）」** を採用した。

- 採用理由: DB 変更が不要で、共有 DB を触る他メンバーへの影響がない。
- **代償（運用ルールが必要）**:
  **営業中に商品マスタの税率・カテゴリを変更してはいけない。**
  変更すると過去注文のハッシュが変わり、レジが会計時に送り返したハッシュと一致せず、
  `ORDER_NOT_FOUND` となって **会計できなくなる**。

  商品名（`ordered_product_name`）と単価（`ordered_unit_price`）は `order_details` に
  注文時点でスナップショット済みなので安全。**税率とカテゴリだけがマスタ直結**という非対称がある。

将来この制約を外す場合は、`order_details` に `tax_rate` / `category_name` のスナップショット列を
追加すれば、他項目と同じ設計思想に揃い、マスタ変更に影響されなくなる。

---

## 9. 未決定事項（実装前に確定させたい）

| # | 論点 | 推奨案 |
| --- | --- | --- |
| 1 | キャンセル済み明細（`detail_status = CANCELLED`）を `items` に含めるか | **除外**（会計対象外のため） |
| 2 | 注文ゼロの顧客（QR 発行しただけ）を返すか | **`items: []` で含める**（レジが「受付中の卓一覧」を見たいはずのため） |
| 3 | `fromTime` / `toTime` を何のカラムで絞るか | **`entryTime`（来店時刻）基準** |
| 4 | API に認証を付けるか | 契約に API キー等が一切ないため、**無認証（店内 LAN 前提）**になる。可否の確認が必要 |
| 5 | レジ側 `base_url` の変更（第 1 章） | レジ担当者との調整が必要 |

---

## 10. 検証方法

`参照用/api_test_tool` に pytest の契約テストが用意されている。実装後はこれで契約適合を確認する。

```
MOS_BASE_URL=http://localhost/MOS_A/public pytest -m contract
```

主な検証内容:

- `getOrders` のレスポンスが配列で、必須キーと型（特に `taxRate` が int）を満たすか
- `updateStatus` の正常系レスポンスボディが空か
- ハッシュを 1 文字改ざんした `updateStatus` が HTTP 400 ＋ `ORDER_NOT_FOUND` を返すか
