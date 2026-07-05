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
