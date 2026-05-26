<section id="planScreen" class="screen">
    <h1 class="page-title">プランを選択してください</h1>

    <div class="plan-list">
        <?php foreach ($plans as $plan): ?>
            <?php if ($plan['id'] !== 'single'): ?>
                <button
                    class="plan-banner"
                    data-plan-id="<?= h($plan['id']) ?>"
                    type="button"
                >
                    <div class="plan-image-frame">画像枠</div>
                    <div class="plan-text">
                        <?= h($plan['name']) ?>
                    </div>
                </button>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <button id="singleOrderButton" class="single-order-button" data-plan-id="single" type="button">
        <span class="icon-frame">icon</span>
        単品注文
    </button>
</section>