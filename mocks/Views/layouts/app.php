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

<?php
/*
 * mocks 専用：変更日時と「試している機能/レイアウト」を表示する情報パネル。
 * 本体(public/src)には存在しない確認用UI。データは mocks/_changelog.php。
 * 右下のボタンで開閉。$mockArea（各エントリPHPで設定）に一致する項目を上部に強調表示する。
 */
$mockChangelogFile = __DIR__ . '/../../_changelog.php';
$mockChanges = is_file($mockChangelogFile) ? require $mockChangelogFile : [];
$mockArea = $mockArea ?? null;
if (!empty($mockChanges)):
?>
<style>
    .mock-info { position: fixed; right: 14px; bottom: 14px; z-index: 99999; font-family: sans-serif; }
    .mock-info-toggle {
        border: none; border-radius: 999px; padding: 9px 14px; cursor: pointer;
        background: #1f2937; color: #fff; font-size: 13px; font-weight: 700;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.35);
    }
    .mock-info-panel {
        display: none; position: absolute; right: 0; bottom: 46px; width: 320px; max-height: 60vh;
        overflow-y: auto; background: #fff; color: #222; border-radius: 10px; padding: 12px 14px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3); border: 1px solid #e5e7eb;
    }
    .mock-info.open .mock-info-panel { display: block; }
    .mock-info-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 8px; }
    .mock-info-head strong { font-size: 14px; }
    .mock-info-area { font-size: 11px; color: #2563eb; font-weight: 700; }
    .mock-info-list { list-style: none; margin: 0; padding: 0; }
    .mock-info-list li { padding: 8px 0; border-top: 1px solid #eee; }
    .mock-info-list li.is-current { background: #fffbeb; border-radius: 6px; padding: 8px; }
    .mock-info-date { font-size: 11px; color: #666; margin-right: 6px; }
    .mock-info-tag {
        font-size: 10px; font-weight: 700; color: #374151; background: #e5e7eb;
        border-radius: 999px; padding: 1px 8px;
    }
    .mock-info-title { display: block; margin-top: 4px; font-size: 13px; font-weight: 700; }
    .mock-info-detail { margin: 4px 0 0; font-size: 12px; color: #555; line-height: 1.5; }
</style>
<div id="mockInfo" class="mock-info">
    <button type="button" class="mock-info-toggle"
            onclick="document.getElementById('mockInfo').classList.toggle('open')">
        🛠 mock情報
    </button>
    <div class="mock-info-panel">
        <div class="mock-info-head">
            <strong>mocks 変更ログ</strong>
            <?php if ($mockArea): ?><span class="mock-info-area">この画面: <?= h($mockArea) ?></span><?php endif; ?>
        </div>
        <ul class="mock-info-list">
            <?php foreach ($mockChanges as $c): ?>
                <li class="<?= ($mockArea && ($c['area'] ?? '') === $mockArea) ? 'is-current' : '' ?>">
                    <span class="mock-info-date"><?= h($c['date'] ?? '') ?></span>
                    <span class="mock-info-tag"><?= h($c['area'] ?? '') ?></span>
                    <span class="mock-info-title"><?= h($c['title'] ?? '') ?></span>
                    <?php if (!empty($c['detail'])): ?>
                        <p class="mock-info-detail"><?= h($c['detail']) ?></p>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

</body>
</html>
