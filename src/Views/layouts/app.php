<?php
declare(strict_types=1);

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
$jsFile = $jsFile ?? '';
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title><?= h($title) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php if ($cssFile !== ''): ?>
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
            menus: <?= json_encode($menus, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
        };
    </script>
<?php endif; ?>

<?php if (isset($orders, $products)): ?>
    <script>
        window.STAFF_DATA = {
            orders: <?= json_encode($orders, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            products: <?= json_encode($products, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
        };
    </script>
<?php endif; ?>

<?php if ($jsFile !== ''): ?>
    <script src="<?= h($jsFile) ?>"></script>
<?php endif; ?>

<!-- 確認用：動作確認が終わったら削除してOK -->
<div style="
    position:fixed;
    left:8px;
    bottom:8px;
    background:#fff;
    color:#c00;
    border:1px solid #c00;
    padding:6px;
    font-size:12px;
    z-index:9999;
">
    CSS: <?= h($cssFile ?: '未設定') ?><br>
    JS: <?= h($jsFile ?: '未設定') ?>
</div>

</body>
</html>