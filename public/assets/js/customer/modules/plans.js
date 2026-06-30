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
        refreshCategoryScrollButtons,
        onPlanConfirmed
    } = context;

    // 制限時間の選択値（飲み放題プラン用）。既定は120分。
    const DEFAULT_MINUTES = 120;
    let selectedMinutes = DEFAULT_MINUTES;

    // 時間トグルの選択状態を指定の分数に合わせて更新する
    function setSelectedMinutes(minutes) {
        selectedMinutes = minutes;

        document.querySelectorAll('#modalTimeSelect .time-option').forEach(button => {
            button.classList.toggle('is-active', Number(button.dataset.minutes) === minutes);
        });
    }

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

        // 制限時間トグル：飲み放題プランのみ表示し、既定(120分)にリセット。単品は非表示。
        const timeSelect = document.getElementById('modalTimeSelect');
        if (timeSelect) {
            if (planId === 'single') {
                timeSelect.style.display = 'none';
            } else {
                timeSelect.style.display = '';
                setSelectedMinutes(DEFAULT_MINUTES);
            }
        }

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

        // 制限時間トグル：クリックで選択を切り替える
        document.querySelectorAll('#modalTimeSelect .time-option').forEach(button => {
            button.addEventListener('click', () => {
                setSelectedMinutes(Number(button.dataset.minutes));
            });
        });

        document.getElementById('planConfirmButton').addEventListener('click', event => {
            const planId = event.currentTarget.dataset.planId;

            // 単品は制限時間なし。飲み放題プランは選択した分数を採用。
            const minutes = planId === 'single' ? null : selectedMinutes;

            state.selectedPlanId = planId;
            state.planMinutes = minutes;
            closePlanModal();

            state.activeCategory = categories[0];

            // タイマー開始・卓番号/残り時間表示の更新（app.js 側）
            if (typeof onPlanConfirmed === 'function') {
                onPlanConfirmed(planId, minutes);
            }

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
