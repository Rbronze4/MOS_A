<?php
declare(strict_types=1);

/**
 * 共通レイアウト（全画面の外枠）。
 * 各コントローラーから次を受け取りHTML全体を組み立てる:
 *   $view      … 本文として描画するビューファイルのパス
 *   $title     … <title>
 *   $cssFiles / $jsFiles … 読み込むCSS/JS（配列）
 * 役割:
 *   - 共有ヘルパー関数 h()（HTMLエスケープ）・yen()（円表示）の定義場所
 *   - PHPのデータを window.MOS_DATA（客側: plans/categories/menus）や
 *     window.STAFF_DATA（スタッフ: orders/products）としてJSへ受け渡す
 */

if (!function_exists('h')) {
    function h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('yen')) {
    function yen(int $value): string
    {
        return '¥' . number_format($value);
    }
}

$title = $title ?? 'MOS';
$cssFile = $cssFile ?? '';
$cssFiles = $cssFiles ?? [];
$jsFile = $jsFile ?? '';
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title><?= h($title) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php if (!empty($cssFiles) && is_array($cssFiles)): ?>
        <?php foreach ($cssFiles as $file): ?>
            <link rel="stylesheet" href="<?= h($file) ?>">
        <?php endforeach; ?>
    <?php elseif ($cssFile !== ''): ?>
        <link rel="stylesheet" href="<?= h($cssFile) ?>">
    <?php endif; ?>
</head>
<body>

<?php require $view; ?>

<?php if (isset($plans, $categories, $menus)): ?>
    <script>
        window.MOS_DATA = {
            plans: <?= json_encode($plans, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            categories: <?= json_encode($categories, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            menus: <?= json_encode($menus, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            cartItems: <?= json_encode($cartItems ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            orderHistory: <?= json_encode($historyItems ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            customerId: <?= json_encode($customerId ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            sessionId: <?= json_encode($sessionId ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            storeId: <?= json_encode($storeId ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            peopleCount: <?= json_encode($peopleCount ?? 2, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            hasActiveCustomerPlan: <?= json_encode($hasActiveCustomerPlan ?? false, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            activeCustomerPlan: <?= json_encode($activeCustomerPlan ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            // 店舗別・制限時間別のプラン単価（DBのplans・税抜）。
            // 形: { standard: { "120": 2200, "180": 3000 }, premium: { ... } }
            planUnitPrices: <?= json_encode($planUnitPrices ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            // プラン単価は税抜のため、客側の表示はこの税率で税込にする。
            planTaxRate: <?= json_encode($planTaxRate ?? 10, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
        };
    </script>
<?php endif; ?>

<?php if (isset($orders, $products)): ?>
    <script>
        window.STAFF_DATA = {
            orders: <?= json_encode($orders, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            products: <?= json_encode($products, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            productCategories: <?= json_encode($productCategories ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            productPlanTypes: <?= json_encode($productPlanTypes ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
        };
    </script>
<?php endif; ?>

<?php if (!empty($jsFiles) && is_array($jsFiles)): ?>
    <?php foreach ($jsFiles as $file): ?>
        <script src="<?= h($file) ?>"></script>
    <?php endforeach; ?>
<?php elseif ($jsFile !== ''): ?>
    <script src="<?= h($jsFile) ?>"></script>
<?php endif; ?>

</body>
</html>
