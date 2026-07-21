# レジ連携 API `/api/orders` 設計書

> 作成日: 2026-07-14
> 対象: MOS ⇔ レジ（POS）システム間の注文連携 API
> 目的: 契約・マッピング・リスク・決定事項をチームで共有する
> ステータス: **実装済み**（`src/Controllers/ApiOrderController.php` / `src/Models/ApiOrderModel.php`）

## 前提

> 以下は**着手前の状況**。現在はすべて解消済み（対応内容は各章に記載）。
> 経緯を残すため消さずに置く。

- 着手前、MOS 側には `/api/orders` のルートが存在せず、レジからの入口がゼロだった。
  → **実装済み**（`src/Routes/web.php` / `ApiOrderController` / `ApiOrderModel`）。
- 一方で DB は既にレジ寄りに整備済みだった。`customers.billing_status` は `tinyint`（ビットマスク）、
  `customers.order_hash` `varchar(64)` の列も存在する。
  → `order_hash` は着手前は全件未使用だったが、`updateStatus` 成功時に書き込むようになった（第 6 章）。
- レジ側の契約は推測ではなく、レジ本体の実装と契約テストから確定させた（出典は第 0 章）。

---

## 0. システムの立ち位置（最重要）

### 0-1. MOS とレジは「別企業・別システム・API 連携のみ」

| システム | 作る主体 | 役割 |
| --- | --- | --- |
| **MOS_A** | 自チーム | 居酒屋モバイルオーダー（客側・スタッフ側）。PHP + バニラ JS + XAMPP |
| **レジ（regi）** | **他社** | 会計・レジ。MOS に注文を問い合わせ、会計状況を更新する |

両者は **DB もコードも共有しない**。接点は **HTTP 越しの `POST /api/orders` だけ**。

```
【本番の姿】
   店舗サーバ                        別ホスト（他社）
  ┌────────────┐    HTTP     ┌────────────┐
  │    MOS     │ ←─────────→ │    レジ     │
  │  MOS の DB  │  /api/orders │  レジの DB   │
  └────────────┘             └────────────┘
        ↑ DB もソースも一切共有しない（疎結合）
```

### 0-2. `参照用/regi/` の位置づけ（読むためのコピー。実行対象ではない）

本リポジトリの `参照用/regi/` にはレジ一式のソースが入っているが、これは
**「仕様の裏取りのために中身を読むためのコピー」** であり、**MOS の一部でも実行対象でもない**。
レジの実体は `c:\xampp\htdocs\regi\` に別途配置されている。

```
c:\xampp\htdocs\
  ├─ MOS_A\          ← 自チーム（git 管理）  http://localhost/MOS_A/public
  │   └─ 参照用\regi\ ← 仕様を読むための参照コピー（実行しない）
  └─ regi\           ← レジの実体（MOS の外）
```

**守るべき境界ルール（ここが本質）**

- ✅ MOS は **API 仕様だけを根拠に** `/api/orders` を実装する。
- ✅ MOS のコードは **レジのファイル・DB・相対パスに一切触れない**。
  現状も依存はゼロで、参照はコメント 1 行のみ（`src/Views/staff/screens/qr_print.php`）。
- ✅ 動作検証は **`参照用/api_test_tool` の契約テスト**で行い、レジ実体に依存したテストにしない。
- ❌ 「同じマシンだから相手の DB を直接見る」は本番で不可能。絶対にやらない。

> ソースが手元にあること自体は問題ない。**MOS のコードがそこに依存しないこと**だけが重要。

### 0-3. 契約の出典

レジのソース（`参照用/regi/`）と、MOS 用の契約テストツール（`参照用/api_test_tool/`）から確定させた。

| 出典 | 内容 |
| --- | --- |
| `参照用/regi/src/Lib/MosOrdersApi.php` | レジが投げるリクエストと期待するレスポンス |
| `参照用/regi/src/Lib/MosApiClient.php` | 通信方法（JSON POST） |
| `参照用/regi/src/Config/mos.php` | レジが叩く MOS の base_url |
| `参照用/api_test_tool/mos_test/validators.py` | レスポンスの型契約 |
| `参照用/api_test_tool/mos_test/hash_util.py` | ハッシュ生成規則 |

---

## 1. 通信の前提（レジ側の設定変更は不要）

レジ側 `参照用/regi/src/Config/mos.php` の `base_url` は `http://localhost:8080`。
レジは `base_url + "/api/orders"` を叩く。

**結論: レジ側の設定変更は不要。** Apache が `Listen 80` と `Listen 8080` の両方を待ち受けており、
`http://localhost:8080/api/orders` と `http://localhost/MOS_A/public/api/orders` は
同一のレスポンスを返す（実測で確認済み）。レジはデフォルト設定のまま MOS に到達できる。

セットアップ手順は README を参照（README が環境構築の正）。

> **経緯**
>
> 当初この章では「MOS は `/MOS_A/public` 配下にあるため `:8080` では届かない。
> レジ側の `base_url` を `http://localhost/MOS_A/public` へ変更してもらう必要がある」と記載していた。
>
> その後、本番でレジが「MOS 専用のアドレス」を叩く構成（第 0 章）に寄せるため、
> MOS を `:8080` でも配信する方針が採られ、**レジ側の変更は不要になった**。
>
> `base_url` を見て「宛先が違うのでは」と思ったら、まずこの章を読むこと。変更してはいけない。

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

## 3. 追加したファイル（既存画面には影響しない）

| ファイル | 役割 |
| --- | --- |
| `src/Routes/web.php` | `POST /api/orders` を 1 ルート追加 |
| `src/Controllers/ApiOrderController.php` | JSON の受け取り・検証・エラー整形。**スタッフセッション認証は通さない**（レジはブラウザではないため） |
| `src/Models/ApiOrderModel.php` | SQL・階層の平坦化・ハッシュ計算 |
| `public/index.php` | `/api/orders` のときだけ `session_start()` を行わないよう分岐（第 8-3 章） |

スタッフ側・顧客側の既存画面には一切手を入れていない。

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
| `storeId` | **`customers.store_id`** | そのまま（2 文字） |
| `customerId` | `customers.customer_id` | **7 桁ゼロ埋めの文字列** |
| `entryTime` | **`customers.created_at`**（QR 発行 ＝ 来店受付時刻） | ISO8601 `Y-m-d\TH:i:s` |
| `billStatus` | `customers.billing_status` | int（既にビットマスク） |
| `hash` | — | 第 5 章の規則で計算 |
| `items[].orderTime` | `order_details.ordered_at` | ISO8601 |
| `items[].menuName` | `order_details.ordered_product_name` | 注文時スナップショット済み |
| `items[].unitPrice` | `order_details.ordered_unit_price` | int・**税抜** |
| `items[].taxRate` | `products.tax_rate`（JOIN） | **`decimal(5,2)` → int に変換（後述）** |
| `items[].orderQty` | `order_details.quantity` | int |
| `items[].offerQty` | `order_details.provided_quantity` | int |
| `items[].categoryName` | `product_categories.category_name` | null 可 |

### コース（飲み放題プラン）料金は `customer_plans` から合成して先頭に足す

**上の表は `order_details` 由来の明細だけを示している。`items` にはもう 1 種類、
DB の明細として存在しない「コース料金」の行が含まれる。**

飲み放題プランの料金は `order_details` ではなく `customer_plans` にある。
一方レジは **`items[]` に入っているものしか会計できない**ため、コース料金を
**先頭の明細として合成**して返す。これをしないと、レジの請求額から
コース代が丸ごと抜け落ちる。

```
customer_plans cp
  INNER JOIN plans p ON p.plan_id = cp.plan_id
  WHERE cp.ended_at IS NULL          ← 利用中のコースのみ
```

| レジの項目 | 値 | 備考 |
| --- | --- | --- |
| `orderTime` | `customer_plans.started_at` | null なら `customers.created_at`（来店時刻）で代替 |
| `menuName` | `スタンダードプラン(120分)` のような文字列 | `plans.plan_type_id`（1=スタンダード / 2=プレミアム）＋ `time_limit_minutes` から生成。想定外の `plan_type_id` は「コース」表記 |
| `unitPrice` | `customer_plans.unit_price` | **1 人あたり**の単価（プラン確定時のスナップショット） |
| `taxRate` | `ApiOrderModel::COURSE_TAX_RATE`（現在 10） | `PlanModel::COURSE_TAX_RATE` と同じ値にすること |
| `orderQty` | `customers.people_count` | 0 や未設定でも会計が壊れないよう最低 1 として扱う |
| `offerQty` | `orderQty` と同値 | コースに「提供済み数」の概念がないため |
| `categoryName` | `'コース'` | 固定値 |

**注意点**

- 単品プラン（`customer_plans` に有効な行が無い顧客）では、**この行は追加されない**。
- コース代の合計は `unitPrice × orderQty`（＝単価 × 人数）としてレジ側で計算される。
- 1 顧客につき有効なコースは 1 件の想定。万一複数あっても最初の 1 件を採用する。
- **この合成明細もハッシュの計算対象に含まれる**（第 5 章）。`items` 全体が対象のため、
  仕様書だけを見て `order_details` からハッシュを再現しようとすると必ず食い違う。

### taxRate の int 変換（必須）

契約は `taxRate` が **int 必須**（`validators.py` の `assert isinstance(item["taxRate"], int)`）。
一方 MOS の `products.tax_rate` は `decimal(5,2)` で、PDO は文字列 `"10.00"` として返す。
**そのまま返すと契約違反でレジ側の検証に落ちる**ため、`10.00 → 10` / `8.00 → 8` と int に変換して出力する。

> 制約: 契約が int である以上、`8.5%` のような小数税率は表現できない。現状は 10% / 8% のみなので実害はないが、将来小数税率を扱う場合はレジ側と契約の再調整が必要。

### 店舗スコープ：全店舗を返す（契約どおり）

MOS の DB には **10 店舗**（深江橋・本町・今里・京橋・緑橋本店 …）が登録されている。
一方、レジの `getOrders` のリクエストには **`storeId` パラメータが存在しない**（`customerId` / `billStatus` / `fromTime` / `toTime` の 4 つのみ）。

**方針: MOS 側で店舗を絞らず、契約どおり全店舗の注文を返す。**
レスポンスの各注文には `storeId`（`sessions.store_id`）が含まれるため、**どの店舗の注文を扱うかの仕分けはレジ側の責任**とする。

> ⚠️ **この方針に伴うリスク（レジ側と合意が必要）**
>
> - レジは **他店舗の注文も参照できる**状態になる。
> - `updateStatus` も `customerId` だけで通るため、**他店舗の客の会計状況を更新できてしまう**。
>
> MOS 側で防ぐなら「担当店舗 ID を設定で持ち、その店舗にスコープする」という選択肢があるが、
> 今回は契約に忠実な実装を優先し、店舗の仕分けはレジ側に委ねる。

---

## 5. ハッシュの計算規則

`参照用/api_test_tool/mos_test/hash_util.py` に準拠する。対象は以下の 4 項目で、
**`categoryName` はハッシュに含まれない**点に注意。

> `items` は `getOrders` が返すものそのもの。つまり
> **`customer_plans` から合成したコース明細（第 4 章）も対象に含まれる**。
> `order_details` だけからハッシュを再現しようとすると値が合わない。

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

### 8-1. 税率・カテゴリは `products` から都度 JOIN する（DB スキーマ変更なし）

- 採用理由: DB 変更が不要で、共有 DB を触る他メンバーへの影響がない。
- **代償（運用ルールが必要）**:
  **営業中に商品マスタの税率・カテゴリを変更してはいけない。**
  変更すると過去注文のハッシュが変わり、レジが会計時に送り返したハッシュと一致せず、
  `ORDER_NOT_FOUND` となって **会計できなくなる**。

  商品名（`ordered_product_name`）と単価（`ordered_unit_price`）は `order_details` に
  注文時点でスナップショット済みなので安全。**税率とカテゴリだけがマスタ直結**という非対称がある。

将来この制約を外す場合は、`order_details` に `tax_rate` / `category_name` のスナップショット列を
追加すれば、他項目と同じ設計思想に揃い、マスタ変更に影響されなくなる。

### 8-2. 店舗は絞らず、契約どおり全店舗を返す

第 4 章のとおり、店舗の仕分けはレジ側の責任とする。
**代償**として、レジは他店舗の注文を参照でき、他店舗の会計状況も更新できてしまう。
これはレジ側と合意しておく必要がある。

### 8-3. `/api/orders` はステートレスにする

レジはブラウザではないため、**`session_start()` も Cookie も使わない**。
スタッフ／顧客画面のセッション認証は一切通さず、JSON だけで完結させる。

---

## 9. 決定事項と、調整が必要な事項

### 決定済み（実装に反映）

| # | 論点 | 決定 |
| --- | --- | --- |
| 1 | キャンセル済み明細（`detail_status = CANCELLED`）を `items` に含めるか | **除外**（会計対象外のため） |
| 2 | 注文ゼロの顧客（QR 発行しただけ）を返すか | **`items: []` で含める**。現在 54 名中 46 名がセッション未開始のため、`storeId` / `entryTime` は `sessions` ではなく `customers` から取る |
| 3 | `fromTime` / `toTime` を何のカラムで絞るか | **`entryTime`（＝ `customers.created_at`）基準** |
| 4 | API に認証を付けるか | **付けない**。契約に API キー等が無いため無認証で実装した |

### レジ担当者との調整が必要

| # | 論点 |
| --- | --- |
| ~~5~~ | ~~レジ側 `base_url` を変更~~ → **解消**。MOS を `:8080` でも配信するため変更不要（第 1 章） |
| 6 | 全店舗を返す方針（第 8-2 章）をレジ側が受け入れるか |

### 認証について（将来の検討事項）

契約に API キー等が一切ないため、現状は **無認証**。本番はホストが分かれる（第 0 章）ため、
同一 LAN の第三者が全注文を読み、会計済みに更新できてしまう。
必要になった時点で、Apache の IP 制限（`Require ip`）が現実的な防衛線になる。

---

## 10. 検証方法と結果

### pytest（実施済み — 18 件全通過）

`参照用/api_test_tool` の契約テストを実行し、**18 件すべて通過**した（2026-07-21）。

```
cd 参照用/api_test_tool
MOS_BASE_URL=http://localhost/MOS_A/public python -m pytest -v
```

| ファイル | 件数 | 内容 |
| --- | --- | --- |
| `tests/test_smoke.py` | 6 | `smoke_cases.json` のケース。エラーコード 5 種＋正常系 |
| `tests/test_contract_updateStatus.py` | 2 | `updateStatus` の正常系（空ボディ）とハッシュ改ざん時 |
| `tests/test_hash_property.py` | 10 | ハッシュ規則そのものの性質検証（MOS を呼ばずローカル計算のみ） |

**実行時の注意**

- 既定の `MOS_BASE_URL` は `http://localhost:8080`。MOS のパスに合わせて環境変数で上書きする
  （`:8080` でも到達できるため、既定のままでも通る）。
- `pytest` と `requests` が必要（`python -m pip install pytest requests`）。
- **`test_update_status_success_body_empty_or_null_json` は DB を書き換える。**
  一覧先頭の顧客の `billing_status` を 8、`order_hash` に値を入れる。
  実行後は DB を再インポートして戻すこと。

> **重要: `hash` を必須化してはいけない**
>
> このテストは `hash=None` で `updateStatus` が HTTP 200 を返すことを要求している。
> 「ハッシュ省略で整合性チェックを回避できる」のは実装の抜けではなく**契約の仕様**。
> 必須化すると契約テストが落ちる（第 6 章 / 第 11-4 章）。

### 実施済みの検証（実 DB ＋ 実エンドポイントに対して確認）

pytest（上記）とは別に、curl でも同じケースを再現して確認した。
件数はテストデータの増減で変わるため、下表の値は確認時点のもの。

| ケース | 結果 |
| --- | --- |
| `getOrders`（全件） | HTTP 200・全件返却。`validators.py` 相当の型チェックで違反ゼロ（`taxRate` は int の `10`） |
| 壊れた JSON `{` | HTTP 400 `INVALID_JSON_FORMAT` |
| `{"method":"xxx"}` | HTTP 400 `INVALID_REQUEST` |
| `getOrders` に `customerId: "ABC"` | HTTP 400 `INVALID_PARAMETER` |
| `updateStatus` に `billStatus: 999` | HTTP 400 `INVALID_BILL_STATUS` |
| ハッシュを 1 文字改ざんした `updateStatus` | HTTP 400 `ORDER_NOT_FOUND` |
| 正しいハッシュの `updateStatus` | HTTP 200 ＋ 空ボディ。`billing_status` と `order_hash` が更新される |
| `billStatus` によるフィルタ | ビットマスクで正しく絞り込める |
| 既存画面（スタッフ／顧客） | セッション Cookie が従来どおり発行される（リグレッションなし） |
| `/api/orders` | セッション Cookie を発行しない（ステートレス） |

なお検証で書き換えたテストデータ（顧客 1000001）は、元の状態（`billing_status = 1` / `order_hash = NULL`）へ復元済み。

---

## 11. 申し送り事項（ここだけ読めば分かるまとめ）

### 11-1. レジ担当者へ依頼すること

| # | 依頼内容 | 理由 |
| --- | --- | --- |
| 1 | **全店舗の注文が返る**ことを前提に、レジ側で自店舗を仕分けてもらう | 契約に `storeId` パラメータが無いため。合意が取れない場合は MOS 側に「担当店舗 ID」設定を追加する必要がある（第 8-2 章） |

> かつて「`base_url` を `http://localhost/MOS_A/public` へ変更してもらう」という依頼を挙げていたが、
> MOS を `:8080` でも配信する方針になったため **不要になった**（第 1 章）。レジ側の設定は変更しない。

### 11-2. MOS チーム内で守ること

| # | ルール | 理由 |
| --- | --- | --- |
| 1 | **営業中に商品マスタの税率・カテゴリを変更しない** | 税率とカテゴリだけは `products` から都度 JOIN しており、変更すると過去注文のハッシュが変わる。レジが会計時に送り返したハッシュと一致せず、`ORDER_NOT_FOUND` で **会計できなくなる**（第 8-1 章） |
| 2 | `/api/orders` で `session_start()` を呼ばない | レジは Cookie を持たないため、呼ぶとリクエストのたびにセッションファイルが増え続ける（第 8-3 章） |
| 3 | MOS のコードから `参照用/regi/` を参照しない | 実運用でレジのソースは手元に無い。API 仕様だけで動くことを保証する（第 0-2 章） |

### 11-3. 環境面

| # | 内容 |
| --- | --- |
| 1 | pytest 契約テストは **実行可能**（Python 3.14.5 / pytest 9.1.1 で 18 件全通過。第 10 章）。初回は `python -m pip install pytest requests` が必要 |
| 2 | 契約テストの一部は **DB を書き換える**。実行後は DB を再インポートすること（第 10 章） |

### 11-4. 将来の検討事項

| # | 内容 |
| --- | --- |
| 1 | **API は無認証**。本番はホストが分かれるため、同一 LAN の第三者が全注文を読み、会計済みに更新できてしまう。必要になった時点で Apache の IP 制限（`Require ip`）を検討する（第 9 章）<br>なお **`hash` の必須化では塞げない**。契約上 `hash` は省略可（第 10 章）であり、仮に必須化しても `getOrders` が無認証な以上、ハッシュを取得してから `updateStatus` を投げれば通る。レジは認証情報を一切送らない（`Content-Type` と `Accept` のみ）ため、**MOS 側でヘッダ認証を要求する設計も取れない**。防衛線はネットワーク層に置くしかない |
| 2 | 税率・カテゴリのマスタ変更リスクを根本的に外すなら、`order_details` に `tax_rate` / `category_name` のスナップショット列を追加する（第 8-1 章） |
| 3 | `taxRate` は契約が int のため、`8.5%` のような小数税率は表現できない。扱う必要が出たらレジ側と契約の再調整が必要（第 4 章） |
