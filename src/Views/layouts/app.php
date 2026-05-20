<?php
declare(strict_types=1);

if (!function_exists('h')) {
    function h(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('yen')) {
    function yen(int $value): string
    {
        return '¥' . number_format($value);
    }
}
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title><?= h($title ?? 'MOS') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="<?= h($cssFile ?? '/MOS_A/public/assets/css/customer.css') ?>">
</head>
<body>

<?php require $view; ?>

<script>
    window.MOS_DATA = {
        plans: <?= json_encode($plans, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        categories: <?= json_encode($categories, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        menus: <?= json_encode($menus, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    };
</script>
<script src="<?= h($jsFile ?? '/MOS_A/public/assets/js/customer.js') ?>"></script>

</body>
</html>