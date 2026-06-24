/**
 * 客側モジュール：プラン選択。
 * プラン確認モーダルの表示、選択プランの確定（state.selectedPlanId 設定）、
 * 確定後のメニュー画面への遷移を担当する。app.js から context を受け取り生成される。
 * （制限時間機能では、確定処理にタイマー開始フックを追加する予定）
 */
window.MOS = window.MOS || {};
window.MOS.customer = window.MOS.customer || {};

window.MOS.customer.createPlanModule = function createPlanModule(context) {
    const {
        plans,
        state,
        categories,
        formatYen,
        findPlan,
        showScreen,
        renderMenu,
        refreshCategoryScrollButtons
    } = context;

    function openPlanModal(planId) {
        const plan = findPlan(planId);

        if (!plan) {
            return;
        }

        document.getElementById('modalPlanName').textContent = plan.name;
        document.getElementById('modalPlanPrice').textContent = formatYen(plan.price);
        document.getElementById('modalPlanDetails').innerHTML = plan.details
            .map(detail => `<li>${detail}</li>`)
            .join('');

        document.getElementById('planConfirmButton').dataset.planId = plan.id;
        document.getElementById('planModal').classList.add('show');
    }

    function closePlanModal() {
        document.getElementById('planModal').classList.remove('show');
    }

    function bindPlanEvents() {
        document.querySelectorAll('.plan-banner').forEach(button => {
            button.addEventListener('click', () => {
                openPlanModal(button.dataset.planId);
            });
        });

        document.getElementById('singleOrderButton').addEventListener('click', () => {
            openPlanModal('single');
        });

        document.getElementById('closePlanModalButton').addEventListener('click', () => {
            closePlanModal();
        });

        document.getElementById('planConfirmButton').addEventListener('click', event => {
            const planId = event.currentTarget.dataset.planId;

            state.selectedPlanId = planId;
            closePlanModal();

            state.activeCategory = planId === 'single'
                ? categories[0]
                : categories[0];

            renderMenu();
            showScreen('menuScreen');
            requestAnimationFrame(refreshCategoryScrollButtons);
        });
    }

    return {
        bindPlanEvents,
        openPlanModal,
        closePlanModal
    };
};
