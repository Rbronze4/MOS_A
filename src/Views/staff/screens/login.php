<section class="screen active">
    <h1 class="login-title">ログイン</h1>

    <?php if (!empty($error)): ?>
        <p class="error-text">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form method="post" action="/MOS_A/public/login" class="login-form">
        <label>
            店舗
            <select name="store_id" required>
                <option value="" disabled selected hidden>店舗を選択してください</option>

                <?php foreach ($stores as $store): ?>
                    <option value="<?= htmlspecialchars($store['store_id'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($store['store_name'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            パスワード
            <input type="password" name="password" required>
        </label>

        <div style="text-align:center;">
            <button type="submit" class="white-button">ログイン</button>
        </div>
    </form>
</section>