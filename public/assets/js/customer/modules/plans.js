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
        planUnitPrices,
        state,
        categories,
        formatYen,
        findPlan,
        showScreen,
        renderCategoryTabs,
        renderMenu,
        refreshCategoryScrollButtons,
        onPlanConfirmed,
        startCustomerSession,
        syncMenuData,
        showToast
    } = context;

    // 制限時間の選択値（飲み放題プラン用）。既定は120分。
    const DEFAULT_MINUTES = 120;

    // 画面で選べる制限時間。プラン確認モーダルの時間トグルと対応させること。
    const SELECTABLE_MINUTES = [120, 180];

    let selectedMinutes = DEFAULT_MINUTES;

    // 現在モーダルに表示しているプラン。時間切替時の再計算に使う。
    let currentPlanId = null;

    function categoryId(category) {
        return typeof category === 'object' && category !== null
            ? String(category.id)
            : String(category);
    }

    // 実際の利用人数。DBのpeople_countがstate.peopleCountに入っている。
    // 万一未設定でも表示が崩れないよう、最低1人として扱う。
    function currentPeopleCount() {
        return Math.max(1, Number(state.peopleCount) || 1);
    }

    /**
     * 店舗別・制限時間別の1人あたり単価をDB由来のデータから取得する。
     * 価格は店舗と制限時間で変わるため、画面側でハードコードしない。
     *
     * DBに有効な価格（is_active=1の行）が無い場合はnullを返す。
     * 「単価0円」と「利用不可」を区別するため、0ではなくnullにしている点に注意。
     * 単品は価格を持たないプランなので、利用可能かつ0円として扱う。
     */
    function unitPriceFor(planId, minutes) {
        if (planId === 'single') {
            return 0;
        }

        const pricesByMinutes = planUnitPrices?.[planId];

        if (!pricesByMinutes) {
            return null;
        }

        // MOS_DATAはJSON経由のため、分数キーは文字列（"120"）になる
        const price = Number(pricesByMinutes[String(minutes)]);

        // is_active=0や未登録の時間は、そもそもキーが存在しない
        if (!Number.isFinite(price) || price <= 0) {
            return null;
        }

        return price;
    }

    // その制限時間でプランを選べるか（DBに有効な価格があるか）
    function isMinutesAvailable(planId, minutes) {
        return unitPriceFor(planId, minutes) !== null;
    }

    // プラン自体を選べるか。飲み放題は、どれか1つでも選べる制限時間があれば選択可能。
    function isPlanAvailable(planId) {
        if (planId === 'single') {
            return true;
        }

        return SELECTABLE_MINUTES.some(minutes => isMinutesAvailable(planId, minutes));
    }

    /**
     * モーダルの合計金額とプラン内容を、選択中のプラン・制限時間・人数で描画する。
     * 制限時間を切り替えると単価が変わるため、切替のたびに呼び直す必要がある。
     *
     * 選択中の制限時間がDBに登録されていない（利用不可の）場合は、
     * ¥0で確定できてしまわないよう、確定ボタンを無効化して理由を表示する。
     */
    function renderPlanPriceAndDetails(plan) {
        const peopleCount = currentPeopleCount();
        const unitPrice = unitPriceFor(plan.id, selectedMinutes);
        const available = unitPrice !== null;
        const totalPrice = available ? unitPrice * peopleCount : 0;

        const priceElement = document.getElementById('modalPlanPrice');
        const detailsElement = document.getElementById('modalPlanDetails');
        const confirmButton = document.getElementById('planConfirmButton');

        // 利用できない組み合わせでは金額を出さない（¥0と誤解させないため）
        priceElement.textContent = available ? formatYen(totalPrice) : '—';

        const details = [...plan.details];

        if (available && unitPrice > 0) {
            details.push(`${formatYen(unitPrice)}/人`);
            details.push(`大人${peopleCount}人`);
        }

        if (!available) {
            details.push('このプランは現在ご利用いただけません');
        }

        detailsElement.innerHTML = details
            .map(detail => `<li>${detail}</li>`)
            .join('');

        // 利用不可のまま確定させると、サーバー側で
        // 「選択されたプランがこの店舗で利用できません」となり客が理由を理解できない。
        // そのため確定ボタン自体を押せなくする。
        confirmButton.disabled = !available;
        confirmButton.classList.toggle('is-disabled', !available);
    }

    // 時間トグルの選択状態を指定の分数に合わせて更新し、価格・内容を再計算する
    function setSelectedMinutes(minutes) {
        selectedMinutes = minutes;

        document.querySelectorAll('#modalTimeSelect .time-option').forEach(button => {
            const buttonMinutes = Number(button.dataset.minutes);
            const available = isMinutesAvailable(currentPlanId, buttonMinutes);

            button.classList.toggle('is-active', buttonMinutes === minutes);

            // DBに価格が無い制限時間は選ばせない（例: 180分だけ未登録の店舗）
            button.disabled = !available;
            button.classList.toggle('is-disabled', !available);
        });

        // 制限時間によって単価が変わるため、表示中のプランの金額と内容を再描画する
        const plan = findPlan(currentPlanId);

        if (plan) {
            renderPlanPriceAndDetails(plan);
        }
    }

    function openPlanModal(planId) {
        const plan = findPlan(planId);

        if (!plan) {
            return;
        }

        // 全ての制限時間が利用不可のプランは、そもそもモーダルを開かない
        if (!isPlanAvailable(planId)) {
            showToast('このプランは現在ご利用いただけません');
            return;
        }

        currentPlanId = plan.id;

        document.getElementById('modalPlanName').textContent = plan.name;

        // 制限時間トグル：飲み放題プランのみ表示し、単品は非表示。
        const timeSelect = document.getElementById('modalTimeSelect');
        if (timeSelect) {
            timeSelect.style.display = planId === 'single' ? 'none' : '';
        }

        // 既定(120分)が利用不可の店舗もあるため、選択可能な制限時間を初期値にする。
        // setSelectedMinutes の中で金額と内容も描画される。
        const initialMinutes = planId === 'single'
            ? DEFAULT_MINUTES
            : (SELECTABLE_MINUTES.find(minutes => isMinutesAvailable(planId, minutes)) ?? DEFAULT_MINUTES);

        setSelectedMinutes(initialMinutes);

        document.getElementById('planConfirmButton').dataset.planId = plan.id;
        document.getElementById('planModal').classList.add('show');
    }

    /**
     * 利用できないプランのバナーを押せないようにする。
     * バナーはPHPが静的に出力しているため、DBで無効化されていても表示自体は残る。
     * 何も表示しないと客が「消えた」と混乱するため、押せない状態にして理由を伝える。
     */
    function applyPlanAvailabilityToBanners() {
        document.querySelectorAll('.plan-banner').forEach(button => {
            const available = isPlanAvailable(button.dataset.planId);

            button.disabled = !available;
            button.classList.toggle('is-disabled', !available);

            if (!available) {
                button.setAttribute('title', 'このプランは現在ご利用いただけません');
            }
        });
    }

    function closePlanModal() {
        document.getElementById('planModal').classList.remove('show');
    }

    function bindPlanEvents() {
        // DBで利用できないプランのバナーは、初期表示の時点で押せなくする
        applyPlanAvailabilityToBanners();

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

        document.getElementById('planConfirmButton').addEventListener('click', async event => {
            if (state.hasActiveCustomerPlan) {
                showToast('このQRコードではすでにプランが選択されています');
                closePlanModal();
                showScreen('tableScreen');
                return;
            }

            const planId = event.currentTarget.dataset.planId;

            // 単品は制限時間なし。飲み放題プランは選択した分数を採用。
            const minutes = planId === 'single' ? null : selectedMinutes;

            // ボタンのdisabledだけに頼らず、送信直前にも利用可否を確認する。
            // 利用不可のまま送るとサーバー側で失敗し、客に理由が伝わらないため。
            if (minutes !== null && !isMinutesAvailable(planId, minutes)) {
                showToast('このプランは現在ご利用いただけません');
                return;
            }

            if (!state.customerId) {
                showToast('顧客情報が見つかりません');
                return;
            }

            if (!state.tableNumber) {
                showToast('卓番号を入力してください');
                closePlanModal();
                showScreen('tableScreen');
                return;
            }

            try {
                const result = await startCustomerSession(planId, minutes);

                state.customerId = result.customer_id;
                state.storeId = result.store_id;
                state.sessionId = result.session_id;
                state.cart = result.cart_items || [];
                state.activeCustomerPlan = result.active_customer_plan || state.activeCustomerPlan;
                state.peopleCount = Number(result.people_count || state.peopleCount || 2);
                if (typeof syncMenuData === 'function') {
                    syncMenuData(result);
                }
                state.hasActiveCustomerPlan = true;
                state.selectedPlanId = planId;
                state.planMinutes = minutes;

                const url = new URL(window.location.href);
                url.searchParams.set('customer_id', String(result.customer_id));
                url.searchParams.set('session_id', String(result.session_id));
                window.history.replaceState(null, '', url.toString());

                closePlanModal();

                state.activeCategory = categories.length > 0 ? categoryId(categories[0]) : '';

                // タイマー開始・卓番号/残り時間表示の更新（app.js 側）
                if (typeof onPlanConfirmed === 'function') {
                    onPlanConfirmed(planId, minutes);
                }

                renderMenu();
                if (typeof renderCategoryTabs === 'function') {
                    renderCategoryTabs();
                }
                showScreen('menuScreen');
                requestAnimationFrame(refreshCategoryScrollButtons);
            } catch (error) {
                showToast(error.message || 'セッション作成に失敗しました');
            }
        });
    }

    return {
        bindPlanEvents,
        openPlanModal,
        closePlanModal
    };
};
