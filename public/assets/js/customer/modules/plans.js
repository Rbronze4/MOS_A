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
        planTaxRate,
        taxIncludedPrice,
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
        showToast,
        planIdFromActiveCustomerPlan
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

    /**
     * 有効なプラン情報から表示用のコース名を作る。
     * 例）「プレミアムプラン（180分）」。単品など該当が無ければ空文字。
     */
    function planLabelFrom(activePlan) {
        if (!activePlan) {
            return '';
        }

        const planId = typeof planIdFromActiveCustomerPlan === 'function'
            ? planIdFromActiveCustomerPlan(activePlan)
            : null;
        const plan = planId ? findPlan(planId) : null;
        const name = plan ? plan.name : 'コース';
        const minutes = Number(activePlan.time_limit_minutes);

        return minutes > 0 ? `${name}（${minutes}分）` : name;
    }

    /**
     * すでに別のコースが確定していることを、画面上部のバナーで知らせる。
     * QRを複数端末で読み、他端末が先にコースを確定していた場合に呼ぶ。
     * OKを押すまで残す（自動では消さない）ことで、コースの取り違えに気づかせる。
     */
    function showPlanConflictBanner(activePlan) {
        const banner = document.getElementById('planConflictBanner');
        const message = document.getElementById('planConflictMessage');

        if (!banner || !message) {
            return;
        }

        const label = planLabelFrom(activePlan);

        message.textContent = label !== ''
            ? `すでにコースは選択されています。現在のコースは「${label}」です。`
            : 'すでにコースは選択されています。';

        banner.classList.add('show');
        banner.setAttribute('aria-hidden', 'false');
    }

    function hidePlanConflictBanner() {
        const banner = document.getElementById('planConflictBanner');

        if (!banner) {
            return;
        }

        banner.classList.remove('show');
        banner.setAttribute('aria-hidden', 'true');
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

        // DBのplans.priceは税抜。客に見せる金額は必ず税込にする。
        // レジもAPIのtaxRateで税を上乗せするため、税抜のまま表示すると
        // 客が見た額より実際の請求額が高くなってしまう。
        const netUnitPrice = unitPriceFor(plan.id, selectedMinutes);
        const available = netUnitPrice !== null;

        const unitPrice = available ? taxIncludedPrice(netUnitPrice, planTaxRate) : 0;
        // 合計も「税抜合計×税率」で求める。単価を税込にしてから人数を掛けると
        // 端数処理が先に入り、税抜合計へ課税するレジ側の計算とずれることがある。
        const totalPrice = available
            ? taxIncludedPrice(netUnitPrice * peopleCount, planTaxRate)
            : 0;

        const priceElement = document.getElementById('modalPlanPrice');
        const detailsElement = document.getElementById('modalPlanDetails');
        const confirmButton = document.getElementById('planConfirmButton');

        // 利用できない組み合わせでは金額を出さない（¥0と誤解させないため）
        priceElement.textContent = available ? formatYen(totalPrice) : '—';

        const details = [...plan.details];

        if (available && unitPrice > 0) {
            details.push(`${formatYen(unitPrice)}/人（税込）`);
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

        const planConflictOkButton = document.getElementById('planConflictOkButton');
        if (planConflictOkButton) {
            planConflictOkButton.addEventListener('click', hidePlanConflictBanner);
        }

        document.getElementById('planConfirmButton').addEventListener('click', async event => {
            if (state.hasActiveCustomerPlan) {
                // この端末は既にコース確定済み。取り違え防止にコース名を明示して知らせる。
                showPlanConflictBanner(state.activeCustomerPlan);
                closePlanModal();
                showScreen('menuScreen');
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

                // サーバーは「先に確定したコース」を返す（先勝ち）。
                // 自分が選んだコースと食い違う場合は、他端末が先に別コースを確定していたということ。
                // その場合はバナーで知らせ、実際に有効なコースの方でメニューを表示する。
                const serverPlanId = typeof planIdFromActiveCustomerPlan === 'function'
                    ? planIdFromActiveCustomerPlan(result.active_customer_plan)
                    : null;
                const planConflict = serverPlanId !== null && serverPlanId !== planId;

                const effectivePlanId = serverPlanId || planId;
                const effectiveMinutes = result.active_customer_plan
                    ? Number(result.active_customer_plan.time_limit_minutes)
                    : minutes;

                state.hasActiveCustomerPlan = true;
                state.selectedPlanId = effectivePlanId;
                state.planMinutes = effectiveMinutes;

                const url = new URL(window.location.href);
                url.searchParams.set('customer_id', String(result.customer_id));
                url.searchParams.set('session_id', String(result.session_id));
                window.history.replaceState(null, '', url.toString());

                closePlanModal();

                if (planConflict) {
                    showPlanConflictBanner(result.active_customer_plan);
                }

                state.activeCategory = categories.length > 0 ? categoryId(categories[0]) : '';

                // タイマー開始・卓番号/残り時間表示の更新（app.js 側）。実際に有効なコースで動かす。
                if (typeof onPlanConfirmed === 'function') {
                    onPlanConfirmed(effectivePlanId, effectiveMinutes);
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
