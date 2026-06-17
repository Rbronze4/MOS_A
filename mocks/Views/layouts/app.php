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

<?php if (!empty($jsFiles) && is_array($jsFiles)): ?>
    <?php foreach ($jsFiles as $file): ?>
        <script src="<?= h($file) ?>"></script>
    <?php endforeach; ?>
<?php elseif ($jsFile !== ''): ?>
    <script src="<?= h($jsFile) ?>"></script>
<?php endif; ?>

</body>
</html>
