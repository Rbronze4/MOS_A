<?php
declare(strict_types=1);

/**
 * QRコード印刷ページ（単独HTML・共通レイアウト不使用）。
 *
 * regiの領収書画面（参照用/regi/src/Views/print/invoice.php）と同じ
 * 「別タブで開いてブラウザの印刷機能で印刷する」方式。
 *
 * スタッフ画面はタブレット利用を想定しているため、
 * - 自動印刷はせず、大きめの「印刷」ボタンをタップしてもらう
 * - プレビューは可変幅（max-width）でタブレット縦持ちでも収まるようにする
 *
 * StaffController::qrPrint() から渡される変数:
 * @var string $storeName  ログイン中の店舗名
 * @var array  $customer   顧客情報（customer_id / people_count / created_at など）
 * @var string $orderUrl   客側注文画面のURL（QRコードの中身）
 * @var bool   $isReissue  再発行かどうか
 */

if (!function_exists('qr_print_h')) {
    function qr_print_h(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$printCustomerId = (string)$customer['customer_id'];
$printPeopleCount = (int)$customer['people_count'];
$printIssuedAt = (string)($customer['created_at'] ?? '');
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ご注文用QRコード</title>

<style>
body {
    margin: 0;
    padding: 0;
    background: #fff;
    color: #000;
    font-family: "Yu Gothic", "Hiragino Kaku Gothic ProN", "Meiryo", sans-serif;
}

/* タブレット縦持ち（768px前後）でも収まるように可変幅にする */
.qr-ticket {
    width: 100%;
    max-width: 700px;
    margin: 24px auto;
    padding: 32px;
    border: 1px solid #000;
    box-sizing: border-box;
    text-align: center;
}

.title {
    font-size: 26px;
    font-weight: bold;
    margin-bottom: 16px;
    letter-spacing: 0.14em;
}

.reissue-label {
    width: fit-content;
    margin: 0 auto 16px;
    padding: 4px 16px;
    border: 2px solid #000;
    font-size: 15px;
    font-weight: bold;
    letter-spacing: 0.12em;
}

.store-name {
    font-size: 18px;
    margin-bottom: 24px;
}

.customer-label {
    font-size: 14px;
    color: #333;
}

.customer-number {
    font-size: 36px;
    font-weight: bold;
    letter-spacing: 0.08em;
    margin: 4px 0 16px;
}

.qr-area {
    margin: 8px 0 16px;
}

.guide-text {
    font-size: 15px;
    line-height: 1.8;
    margin-bottom: 16px;
}

.meta {
    font-size: 13px;
    color: #333;
    line-height: 1.8;
}

.order-url {
    font-size: 11px;
    color: #666;
    word-break: break-all;
    margin-top: 12px;
}

.no-print {
    text-align: center;
    margin: 16px 0 32px;
}

/* タブレットのタップ操作を想定して、ボタンは大きめ（高さ48px以上）にする */
.no-print button {
    margin: 0 8px;
    padding: 14px 32px;
    font-size: 16px;
    min-height: 48px;
    cursor: pointer;
}

@media print {
    @page {
        margin: 12mm;
    }

    .no-print {
        display: none;
    }

    .qr-ticket {
        border: none;
        margin: 0 auto;
    }
}
</style>
</head>

<body>

<div class="qr-ticket">

    <div class="title">
        ご注文用QRコード
    </div>

    <?php if ($isReissue): ?>
        <div class="reissue-label">
            再発行
        </div>
    <?php endif; ?>

    <?php if ($storeName !== ''): ?>
        <div class="store-name">
            <?= qr_print_h($storeName) ?>
        </div>
    <?php endif; ?>

    <div class="customer-label">
        顧客番号
    </div>

    <div class="customer-number">
        <?= qr_print_h($printCustomerId) ?>
    </div>

    <div class="qr-area">
        <canvas id="qrPrintCanvas"></canvas>
    </div>

    <div class="guide-text">
        お手元のスマートフォンでQRコードを読み取り、<br>
        従業員から案内された卓番号を入力してご注文ください。
    </div>

    <div class="meta">
        <div>ご利用人数: <?= qr_print_h($printPeopleCount) ?>名</div>

        <?php if ($printIssuedAt !== ''): ?>
            <div>発行日時: <?= qr_print_h($printIssuedAt) ?></div>
        <?php endif; ?>
    </div>

    <div class="order-url">
        アクセスURL: <?= qr_print_h($orderUrl) ?>
    </div>

</div>

<div class="no-print">

    <button type="button" id="qrPrintButton" disabled>
        印刷
    </button>

    <button type="button" id="qrPrintCloseButton">
        閉じる
    </button>

</div>

<script>
(() => {
    const orderUrl = <?= json_encode(
        $orderUrl,
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
        | JSON_HEX_TAG
        | JSON_HEX_AMP
        | JSON_HEX_APOS
        | JSON_HEX_QUOT
    ) ?>;

    const printButton = document.getElementById('qrPrintButton');
    const closeButton = document.getElementById('qrPrintCloseButton');

    // QRコード生成ライブラリ（QRious）を読み込む。qr.jsのモーダルと同じライブラリを使う。
    // QRの描画が終わる前に印刷すると白紙のQRが印刷されてしまうため、
    // 描画完了までは印刷ボタンを無効にしておき、完了後に有効化する。
    const script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js';

    script.onload = () => {
        new QRious({
            element: document.getElementById('qrPrintCanvas'),
            value: orderUrl,
            size: 220,
            level: 'H'
        });

        printButton.disabled = false;
    };

    script.onerror = () => {
        // オフライン等でライブラリが読めない場合、白紙QRの印刷を防ぐためボタンは無効のままにする
        document.querySelector('.guide-text').textContent
            = 'QRコードを表示できませんでした。通信環境を確認して再読み込みしてください。';
    };

    document.head.appendChild(script);

    printButton.addEventListener('click', () => {
        window.print();
    });

    closeButton.addEventListener('click', () => {
        // モーダルの「印刷」ボタン（window.open）から開かれた場合はタブを閉じる
        if (window.opener && !window.opener.closed) {
            window.close();
            return;
        }

        // URL直接アクセスなどで開かれた場合は直前のページへ戻る
        if (document.referrer) {
            window.location.href = document.referrer;
            return;
        }

        window.history.back();
    });
})();
</script>

</body>
</html>
