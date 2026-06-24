<?php /**
 * 客側：プラン選択画面。
 * スタンダード/プレミアムの画像バナー（飲み放題）と単品注文ボタンを表示。
 * バナータップで plans.js がプラン確認モーダルを開く。
 */ ?>
<section id="planScreen" class="screen">
    <h1 class="page-title">プランを選択してください</h1>

    <div class="plan-list">
        <?php foreach ($plans as $plan): ?>
            <?php if ($plan['id'] !== 'single'): ?>
                <button
                    class="plan-banner"
                    data-plan-id="<?= h($plan['id']) ?>"
                    type="button"
                    style="position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center; width: 100%; border-radius: 8px; border: none; padding: 0;"
                >
                    <div class="plan-image-frame" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1; pointer-events: none;">
                        <img src="/MOS_A/public/assets/images/common/<?= h($plan['id']) ?>.png" alt="<?= h($plan['name']) ?>" style="width: 100%; height: 100%; object-fit: cover; opacity: 1;">
                    </div>

                    </button>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <button id="singleOrderButton" class="single-order-button" data-plan-id="single" type="button">
        単品注文
    </button>
</section>