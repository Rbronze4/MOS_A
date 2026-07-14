<?php
/**
 * スタッフログイン画面。
 *
 * 店舗選択とパスワードをPOSTし、Controller側でDB認証を行う。
 * 現状は1店舗につき1スタッフアカウント想定のため、ログインIDは入力しない。
 */
$errors = $errors ?? [];
$old = $old ?? [];
$stores = $stores ?? [];
$selectedStoreId = (string)($old['store_id'] ?? '');
?>
<main class="staff-app">
    <section id="loginScreen" class="screen active">
        <h1 class="login-title">居酒屋みどり亭</h1>
        <p class="login-subtitle">スタッフログイン</p>

        <form class="login-form" method="post" action="/MOS_A/public/staff/login" novalidate>
            <label for="storeId">
                <span>店舗選択</span>
                <select id="storeId" name="store_id" required>
                    <option value="" disabled <?= $selectedStoreId === '' ? 'selected' : '' ?>>
                        店舗を選択してください
                    </option>
                    <?php foreach ($stores as $storeId => $storeName): ?>
                        <option value="<?= h($storeId) ?>" <?= $selectedStoreId === (string)$storeId ? 'selected' : '' ?>>
                            <?= h($storeName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label for="loginPassword">
                <span>パスワード</span>
                <input
                    id="loginPassword"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    required
                >
            </label>

            <?php if ($errors !== []): ?>
                <div class="error-text" role="alert" aria-live="polite">
                    <?php foreach ($errors as $error): ?>
                        <p><?= h($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="error-text" aria-live="polite"></div>
            <?php endif; ?>

            <button class="white-button login-submit-button" type="submit">ログイン</button>
        </form>
    </section>
</main>

<script>
/**
 * ログインフォーム送信時の処理
 * * ログインボタンが押されたタイミングで、選択されている店舗IDをローカルストレージに保存します。
 * 【理由】
 * ログイン後のダッシュボード画面（JS側）において、現在ログイン中の店舗IDを即座に把握し、
 * 各店舗専用の客側オーダー用QRコード（URLパラメータに店舗IDを含むもの）を生成できるようにするためです。
 */
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.querySelector('.login-form');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function() {
            const storeSelect = document.getElementById('storeId');
            
            // 店舗が正しく選択されている場合のみ保存を実行する
            if (storeSelect && storeSelect.value) {
                // qr.js 側で 'mos_current_store_id' というキー名で取得するため、同名で保存
                localStorage.setItem('mos_current_store_id', storeSelect.value);
            }
        });
    }
});
</script>