/**
 * 客側アプリの中心スクリプト。
 * window.MOS_DATA（plans/categories/menus）を読み込み、共有 state（卓番号・選択プラン・
 * カート・履歴など）と共有関数（showScreen / formatYen / getDisplayPrice / openProduct 等）を定義。
 * これらを context として各モジュール（plans / menu / cart-history）に渡して画面機能を生成する。
 *
 * 主な関数:
 *   showScreen()        … .screen の active 切り替えで画面遷移
 *   getDisplayPrice()   … DBのplan_applied_flagに基づく表示価格
 *   openProduct()       … 商品詳細画面を開く
 */
document.addEventListener('DOMContentLoaded', () => {
    const plans = window.MOS_DATA.plans;
    const categories = window.MOS_DATA.categories;
    const menus = window.MOS_DATA.menus;
    const cartItems = window.MOS_DATA.cartItems || [];
    const orderHistory = window.MOS_DATA.orderHistory || [];
    const initialCustomerId = window.MOS_DATA.customerId || null;
    const initialSessionId = window.MOS_DATA.sessionId || null;
    const initialStoreId = window.MOS_DATA.storeId || null;
    const initialPeopleCount = Number(window.MOS_DATA.peopleCount || 2);
    const hasActiveCustomerPlan = window.MOS_DATA.hasActiveCustomerPlan === true;
    const activeCustomerPlan = window.MOS_DATA.activeCustomerPlan || null;

    // 店舗別・制限時間別のプラン単価（DBのplans由来・税抜）。
    // 形: { standard: { "120": 2200, "180": 3000 }, premium: { "120": 3200, "180": 4200 } }
    const planUnitPrices = window.MOS_DATA.planUnitPrices || {};

    // プラン単価は税抜のため、表示時はこの税率で税込にする。
    // レジもAPIのtaxRateで税を上乗せするため、客が見た額と請求額を一致させるのに必要。
    const planTaxRate = Number(window.MOS_DATA.planTaxRate ?? 10);

    function categoryId(category) {
        return typeof category === 'object' && category !== null
            ? String(category.id)
            : String(category);
    }

    function planIdFromActiveCustomerPlan(plan) {
        const planTypeId = Number(plan?.plan_type_id || 0);

        if (planTypeId === 1) {
            return 'standard';
        }

        if (planTypeId === 2) {
            return 'premium';
        }

        return null;
    }

    const state = {
        customerId: initialCustomerId,
        sessionId: initialSessionId,
        storeId: initialStoreId,
        peopleCount: initialPeopleCount,
        hasActiveCustomerPlan,
        activeCustomerPlan,
        tableNumber: '',
        selectedPlanId: planIdFromActiveCustomerPlan(activeCustomerPlan),
        activeCategory: categories.length > 0 ? categoryId(categories[0]) : '',
        selectedMenu: null,
        cart: cartItems,
        history: orderHistory,
        editingItem: null
    };

    const screenIds = [
        'tableScreen',
        'planScreen',
        'menuScreen',
        'productScreen',
        'cartScreen',
        'historyScreen'
    ];

    function showScreen(screenId) {
        screenIds.forEach(id => {
            const screen = document.getElementById(id);
            if (!screen) return;

            screen.classList.toggle('active', id === screenId);
        });
    }

    // 画像未設定時の代替画像。menu.js のメニュー一覧と同じものを使う。
    const NO_IMAGE_PATH = '/MOS_A/public/assets/images/menu/no_image.png';

    // innerHTMLへ差し込む値のエスケープ。商品名などDB由来の文字列は必ず通す。
    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
    }

    function formatYen(value) {
        return '¥' + Number(value || 0).toLocaleString('ja-JP');
    }

    function normalizeTableNumberInput(value) {
        return String(value || '').replace(/\D/g, '').slice(0, 2);
    }

    function showToast(message) {
        const toast = document.getElementById('toast');

        toast.textContent = message;
        toast.classList.add('show');

        setTimeout(() => {
            toast.classList.remove('show');
        }, 1100);
    }

    function findPlan(planId) {
        return plans.find(plan => String(plan.id) === String(planId));
    }

    function findMenu(menuId) {
        return menus.find(menu => String(menu.id) === String(menuId));
    }

    function getDisplayPrice(menu) {
        if (Number(menu.plan_applied_flag || 0) === 1) {
            return 0;
        }

        return Number(menu.display_price ?? menu.price ?? 0);
    }

    /**
     * 税抜価格へ税率を適用し、税込価格の1円未満を切り捨てる。
     * サーバー側(MenuModel / OrderModel / CartModel)のtaxIncludedPriceと同じ計算にすること。
     */
    function taxIncludedPrice(price, taxRate) {
        const basisPoints = Math.round(Number(taxRate || 0) * 100);

        return Math.floor((Number(price || 0) * (10000 + basisPoints)) / 10000);
    }

    /**
     * オプション込みの税込単価を求める。
     *
     * オプションの追加料金は税抜のため、商品の税抜価格と合算してから税を掛ける。
     * 個別に税込化して足すと端数処理が2回入り、税抜合計へ課税するレジ側の計算と
     * 1円ずれることがある。プラン対象商品は商品分が0円のため、オプション分だけに課税される。
     */
    function priceWithOptions(menu, additionalPrice) {
        const planApplied = Number(menu.plan_applied_flag || 0) === 1;
        const netUnitPrice = planApplied ? 0 : Number(menu.price ?? 0);

        return taxIncludedPrice(netUnitPrice + Number(additionalPrice || 0), menu.tax_rate);
    }

    async function postCartAction(url, values) {
        const body = new URLSearchParams();

        Object.entries(values).forEach(([key, value]) => {
            body.set(key, String(value));
        });

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body
        });

        let payload = null;

        try {
            payload = await response.json();
        } catch (error) {
            payload = null;
        }

        if (!response.ok || !payload || payload.ok !== true) {
            throw new Error(payload?.message || 'カート操作に失敗しました');
        }

        return payload;
    }

    /**
     * ラストオーダーを過ぎていたら注文させない。
     *
     * 実際の遮断はサーバー側で行うが、通信する前に理由を伝えて操作を止める。
     * 呼び出し元はcatchでerror.messageをトースト表示するため、例外で返す。
     */
    function assertWithinLastOrder() {
        if (isLastOrderOver()) {
            throw new Error('ラストオーダーの時間を過ぎています。スタッフをお呼びください。');
        }
    }

    function addCartToServer(productId, quantity, optionIds) {
        assertWithinLastOrder();

        return postCartAction('/MOS_A/public/customer/cart/add', {
            session_id: state.sessionId,
            product_id: productId,
            quantity,
            option_ids: JSON.stringify(optionIds)
        });
    }

    function updateCartOnServer(cartDetailId, quantity, optionIds) {
        return postCartAction('/MOS_A/public/customer/cart/update', {
            session_id: state.sessionId,
            cart_detail_id: cartDetailId,
            quantity,
            option_ids: JSON.stringify(optionIds)
        });
    }

    function deleteCartFromServer(cartDetailId) {
        return postCartAction('/MOS_A/public/customer/cart/delete', {
            session_id: state.sessionId,
            cart_detail_id: cartDetailId
        });
    }

    function submitOrderToServer() {
        assertWithinLastOrder();

        // 注文内容はサーバー側でDBのcart_detailsから取得する。
        // フロントから商品名・価格・数量一覧は送らない。
        return postCartAction('/MOS_A/public/customer/order/submit', {
            session_id: state.sessionId
        });
    }

    function startCustomerSession(planId, minutes) {
        // QR連携が完成するまでは、PHPから渡されたテスト用customer_idを使う。
        // セッション作成後に返るsession_idを、以後のカート操作と注文確定で送信する。
        return postCartAction('/MOS_A/public/customer/session/start', {
            customer_id: state.customerId,
            table_number: state.tableNumber,
            plan_key: planId || '',
            plan_minutes: minutes || ''
        });
    }

    function rememberSessionInUrl(result) {
        const url = new URL(window.location.href);
        url.searchParams.set('customer_id', String(result.customer_id));
        url.searchParams.set('session_id', String(result.session_id));
        window.history.replaceState(null, '', url.toString());
    }

    function applyStartedSession(result, planId = null, minutes = null) {
        state.customerId = result.customer_id;
        state.storeId = result.store_id;
        state.sessionId = result.session_id;
        state.cart = result.cart_items || [];
        state.activeCustomerPlan = result.active_customer_plan || state.activeCustomerPlan;
        state.peopleCount = Number(result.people_count || state.peopleCount || 2);
        syncMenuData(result);

        if (planId !== null) {
            state.selectedPlanId = planId;
            state.planMinutes = minutes;
        } else {
            state.selectedPlanId = planIdFromActiveCustomerPlan(state.activeCustomerPlan);
        }

        rememberSessionInUrl(result);
        updateTableNoDisplay();
        // QRを読み直してセッションを復元した場合も、残り時間を表示し直す。
        syncDrinkTimer();
        cartHistoryModule.renderCart();
        renderMenuAndShow();
    }

    function renderMenuAndShow() {
        state.activeCategory = categories.length > 0 ? categoryId(categories[0]) : '';
        menuModule.renderCategoryTabs();
        menuModule.renderMenu();
        showScreen('menuScreen');
        requestAnimationFrame(menuModule.refreshCategoryScrollButtons);
    }

    let cartHistoryModule;

    function syncMenuData(result) {
        if (Array.isArray(result.categories)) {
            categories.splice(0, categories.length, ...result.categories);
        }

        if (Array.isArray(result.menus)) {
            menus.splice(0, menus.length, ...result.menus);
        }
    }

    function openProduct(menu, quantity = 1, resetEditing = true) {
        state.selectedMenu = menu;

        if (resetEditing) {
            state.editingItem = null;
        }

        const imageFrame = document.getElementById('productImageFrame');
        // 商品名・画像パスはDB由来（スタッフが商品管理画面から登録する）。
        // innerHTMLへ差し込むため必ずエスケープする。
        const imageSrc = menu.image_path || NO_IMAGE_PATH;
        imageFrame.innerHTML = `<img src="${escapeHtml(imageSrc)}" alt="${escapeHtml(menu.name)}" style="width: 100%; height: 100%; object-fit: cover;">`;

        document.getElementById('productName').textContent = menu.name;
        document.getElementById('quantityInput').value = String(quantity);
        renderProductOptions(menu, resetEditing ? [] : (state.editingItem?.option_ids || []));

        showScreen('productScreen');
    }

    function selectedProductOptionIds() {
        return Array.from(document.querySelectorAll('#productOptions input:checked'))
            .map(input => Number(input.value))
            .filter(Number.isInteger);
    }

    function refreshProductPrice() {
        const menu = state.selectedMenu;

        if (!menu) return;

        const selectedIds = new Set(selectedProductOptionIds());
        const additionalPrice = (menu.option_groups || []).reduce((sum, group) => {
            return sum + (group.options || []).reduce((groupSum, option) => {
                return groupSum + (selectedIds.has(Number(option.option_id))
                    ? Number(option.additional_price || 0)
                    : 0);
            }, 0);
        }, 0);

        document.getElementById('productPrice').textContent = formatYen(priceWithOptions(menu, additionalPrice));
    }

    function renderProductOptions(menu, selectedOptionIds = []) {
        const container = document.getElementById('productOptions');
        const groups = Array.isArray(menu.option_groups) ? menu.option_groups : [];
        const selectedIds = new Set(selectedOptionIds.map(Number));

        container.innerHTML = groups.map(group => {
            const isMultiple = group.selection_type === 'MULTIPLE';
            const isRequired = Number(group.is_required) === 1;
            const inputType = isMultiple ? 'checkbox' : 'radio';
            const instruction = isMultiple
                ? (isRequired ? '1つ以上お選びください' : '複数選択できます')
                : (isRequired ? '1つお選びください' : '必要な場合にお選びください');

            return `
                <fieldset class="product-option-group" data-option-group-id="${Number(group.option_group_id)}">
                    <legend class="product-option-heading">
                        ${escapeHtml(group.group_name)}${isRequired ? '<span class="product-option-required">（必須）</span>' : ''}
                    </legend>
                    <p class="product-option-rule">${instruction}</p>
                    <div class="product-option-choices">
                        ${(group.options || []).map(option => {
                            // オプションの追加料金も税抜で保存されているため、
                            // 商品と同じ税率で税込にしてから見せる。
                            const additionalPrice = taxIncludedPrice(
                                Number(option.additional_price || 0),
                                menu.tax_rate
                            );
                            const optionId = Number(option.option_id);
                            return `
                                <label class="product-option-choice">
                                    <input
                                        type="${inputType}"
                                        name="product_option_${Number(group.option_group_id)}${isMultiple ? '[]' : ''}"
                                        value="${optionId}"
                                        ${selectedIds.has(optionId) ? 'checked' : ''}
                                    >
                                    <span>${escapeHtml(option.option_name)}</span>
                                    ${additionalPrice > 0 ? `<span class="product-option-price">+${formatYen(additionalPrice)}</span>` : ''}
                                </label>
                            `;
                        }).join('')}
                    </div>
                </fieldset>
            `;
        }).join('');

        container.querySelectorAll('input').forEach(input => {
            input.addEventListener('change', refreshProductPrice);
        });

        refreshProductPrice();
    }

    function validateSelectedOptions(menu, selectedOptionIds) {
        const selectedIds = new Set(selectedOptionIds.map(Number));

        for (const group of (menu.option_groups || [])) {
            const count = (group.options || []).filter(option => selectedIds.has(Number(option.option_id))).length;

            if (Number(group.is_required) === 1 && count === 0) {
                return `${group.group_name}を選択してください`;
            }

            if (group.selection_type === 'SINGLE' && count > 1) {
                return `${group.group_name}は1つだけ選択してください`;
            }
        }

        return null;
    }

    const menuModule = window.MOS.customer.createMenuModule({
        categories,
        menus,
        state,
        formatYen,
        findMenu,
        getDisplayPrice,
        openProduct
    });

    // ============================================================
    // 飲み放題タイマー
    //   - 終了予定時刻 = DBのコース開始時刻(customer_plans.started_at) + 制限時間
    //   - 注文画面 上部バー右に「ラストオーダー HH:MM（残り○分）」を表示（残りは分刻み）
    //   - ラストオーダー = コース終了の30分前。過ぎたら「ラストオーダー終了」
    //   - 単品プランはタイマーなし（非表示）
    //   ※端末に保存せず毎回DBの開始時刻から計算する。そうしないとタブを閉じたり
    //     別の端末でQRを読んだときに残り時間が出なくなるため。
    //   ※端末の時計ずれはサーバー時刻との差で補正する。
    // ============================================================
    // ラストオーダーはコース終了の何分前か。判定がずれないようサーバーの値を使う。
    const LAST_ORDER_BEFORE_MS = Number(window.MOS_DATA.lastOrderBeforeMinutes ?? 30) * 60 * 1000;
    let timerIntervalId = null;

    /*
        端末の時計とサーバー時刻の差（ミリ秒）。
        コース開始時刻はサーバー（DB）の時刻なので、端末の時計がずれていると
        残り時間まで狂う。読み込み時に差を求めておき、現在時刻を補正して使う。
    */
    const serverClockOffsetMs = (() => {
        const serverNow = parseServerDateTime(window.MOS_DATA.serverNow);

        return serverNow === null ? 0 : serverNow - Date.now();
    })();

    // サーバー時刻に合わせた「今」
    function nowOnServerClock() {
        return Date.now() + serverClockOffsetMs;
    }

    /**
     * DBの日時文字列（"2026-07-07 05:13:23"）をタイムスタンプに変換する。
     * 区切りが半角スペースのままだと解釈できない端末があるため、必ずTに置き換える。
     */
    function parseServerDateTime(value) {
        if (!value) {
            return null;
        }

        const timestamp = new Date(String(value).replace(' ', 'T')).getTime();

        return Number.isFinite(timestamp) ? timestamp : null;
    }

    /**
     * 現在有効なコースから、終了予定時刻を求める。
     *
     * 以前は「プラン確定時に sessionStorage へ保存した終了時刻」を見ていたため、
     * タブを閉じたり別の端末でQRを読むと残り時間が出なかった。
     * コース開始時刻はDBにあるので、そこから毎回計算すればどの端末でも同じ値になる。
     */
    function drinkTimerEndsAt() {
        const plan = state.activeCustomerPlan;

        if (!plan) {
            return null;
        }

        const minutes = Number(plan.time_limit_minutes || 0);
        const startedAt = parseServerDateTime(plan.started_at);

        if (minutes <= 0 || startedAt === null) {
            return null;
        }

        return startedAt + minutes * 60 * 1000;
    }

    /**
     * ラストオーダーを過ぎているか。
     *
     * 過ぎていれば客側からは注文できない（実際の遮断はサーバー側で行う）。
     * コースが無い場合（単品）は時間制限の対象外。
     */
    function isLastOrderOver() {
        const endsAt = drinkTimerEndsAt();

        if (endsAt === null) {
            return false;
        }

        return endsAt - LAST_ORDER_BEFORE_MS - nowOnServerClock() <= 0;
    }

    // タイムスタンプを「HH:MM」（24時間制）に整形する
    function formatClock(timestamp) {
        const date = new Date(timestamp);
        const pad = value => String(value).padStart(2, '0');
        return `${pad(date.getHours())}:${pad(date.getMinutes())}`;
    }

    // 上部バーの表示を現在時刻から再計算して更新する
    // 例: 「ラストオーダー 20:35（残り20分）」
    function renderRemainTime() {
        const el = document.getElementById('menuRemainTime');
        if (!el) return;

        const endsAt = drinkTimerEndsAt();
        if (endsAt === null) {
            el.style.display = 'none';
            return;
        }

        el.style.display = '';
        // ラストオーダー時刻 = コース終了予定の30分前
        const lastOrderAt = endsAt - LAST_ORDER_BEFORE_MS;
        const remainMs = lastOrderAt - nowOnServerClock();

        if (remainMs <= 0) {
            el.textContent = 'ラストオーダー終了';
            stopTimerInterval();
            return;
        }

        // 残りは分刻み（切り上げ）。L.O.の時刻は固定表示。
        const remainMin = Math.max(0, Math.ceil(remainMs / 60000));
        el.textContent = `ラストオーダー ${formatClock(lastOrderAt)}（残り${remainMin}分）`;
    }

    function stopTimerInterval() {
        if (timerIntervalId) {
            clearInterval(timerIntervalId);
            timerIntervalId = null;
        }
    }

    // カウントダウンを開始（毎秒更新）
    function startTimerInterval() {
        stopTimerInterval();
        renderRemainTime();
        timerIntervalId = setInterval(renderRemainTime, 1000);
    }

    /**
     * 残り時間の表示を、いまのコース状況に合わせ直す。
     *
     * 表示するかどうかは state.activeCustomerPlan だけで決まるため、
     * プランを確定した直後でも、QRを読み直して復元した直後でも、
     * この関数を呼べば同じ結果になる。
     */
    function syncDrinkTimer() {
        if (drinkTimerEndsAt() === null) {
            // 単品などコースが無い場合は表示しない
            stopTimerInterval();

            const el = document.getElementById('menuRemainTime');
            if (el) {
                el.style.display = 'none';
            }

            return;
        }

        startTimerInterval();
    }

    // 上部バー左の卓番号表示を更新する
    function updateTableNoDisplay() {
        const el = document.getElementById('menuTableNo');
        if (el) {
            el.textContent = state.tableNumber ? `卓 ${state.tableNumber}番` : '';
        }
    }

    // プラン確定時の処理（plans.js から呼ばれる）
    function onPlanConfirmed(planId, minutes) {
        updateTableNoDisplay();

        // 終了予定時刻はサーバーが返したコース情報から求めるので、
        // ここで確定したプランや分数を渡す必要はない。
        syncDrinkTimer();
    }

    const planModule = window.MOS.customer.createPlanModule({
        plans,
        planUnitPrices,
        planTaxRate,
        taxIncludedPrice,
        state,
        categories,
        formatYen,
        findPlan,
        showScreen,
        renderCategoryTabs: menuModule.renderCategoryTabs,
        renderMenu: menuModule.renderMenu,
        refreshCategoryScrollButtons: menuModule.refreshCategoryScrollButtons,
        onPlanConfirmed,
        startCustomerSession,
        syncMenuData,
        showToast,
        planIdFromActiveCustomerPlan
    });

    cartHistoryModule = window.MOS.customer.createCartHistoryModule({
        state,
        planTaxRate,
        taxIncludedPrice,
        formatYen,
        findMenu,
        findPlan,
        showScreen,
        showToast,
        getDisplayPrice,
        openProduct,
        refreshCategoryScrollButtons: menuModule.refreshCategoryScrollButtons,
        deleteCartFromServer,
        submitOrderToServer
    });

    document.getElementById('tableSubmitButton').addEventListener('click', async () => {
        const input = document.getElementById('tableNumberInput');
        const error = document.getElementById('tableError');
        // 「01」のような先頭ゼロは「1」に正規化してから検証する
        const value = normalizeTableNumberInput(input.value).replace(/^0+(?=\d)/, '');

        input.value = value;

        // 卓番号は1〜99のみ有効。「0」「00」は卓として存在しないため弾く
        if (!/^[1-9]\d?$/.test(value)) {
            error.textContent = '卓番号は1〜99の数字で入力してください';
            return;
        }

        state.tableNumber = value;
        error.textContent = '';

        if (state.hasActiveCustomerPlan) {
            try {
                const result = await startCustomerSession(null, null);
                applyStartedSession(result);
            } catch (error) {
                showToast(error.message || 'セッション作成に失敗しました');
            }

            return;
        }

        showScreen('planScreen');
    });

    const tableNumberInput = document.getElementById('tableNumberInput');
    tableNumberInput.addEventListener('input', () => {
        const normalizedValue = normalizeTableNumberInput(tableNumberInput.value);

        if (tableNumberInput.value !== normalizedValue) {
            tableNumberInput.value = normalizedValue;
        }
    });

    tableNumberInput.addEventListener('keydown', event => {
        if (event.ctrlKey || event.metaKey || event.altKey) {
            return;
        }

        if (event.key.length === 1 && !/\d/.test(event.key)) {
            event.preventDefault();
        }
    });

    document.getElementById('productBackButton').addEventListener('click', () => {
        state.editingItem = null;

        showScreen('menuScreen');
        requestAnimationFrame(menuModule.refreshCategoryScrollButtons);
    });

    document.getElementById('minusButton').addEventListener('click', () => {
        const input = document.getElementById('quantityInput');
        let current = Number(input.value);
        if (Number.isNaN(current)) current = 1;

        input.value = String(Math.max(1, current - 1));
    });

    document.getElementById('plusButton').addEventListener('click', () => {
        const input = document.getElementById('quantityInput');
        let current = Number(input.value);
        if (Number.isNaN(current)) current = 1;

        input.value = String(Math.min(99, current + 1));
    });

    document.getElementById('addCartButton').addEventListener('click', async () => {
        if (!state.selectedMenu) {
            showToast('商品が選択されていません');
            return;
        }

        const quantityInput = document.getElementById('quantityInput');
        let quantityValue = Number(quantityInput.value);
        if (Number.isNaN(quantityValue) || quantityValue < 1) {
            quantityValue = 1;
        }

        quantityValue = Math.min(99, Math.max(1, Math.floor(quantityValue)));
        quantityInput.value = String(quantityValue);
        const optionIds = selectedProductOptionIds();
        const optionError = validateSelectedOptions(state.selectedMenu, optionIds);

        if (optionError) {
            showToast(optionError);
            return;
        }

        if (!state.sessionId) {
            showToast('卓番号とプランを選択してください');
            showScreen(state.hasActiveCustomerPlan ? 'tableScreen' : 'planScreen');
            return;
        }

        try {
            const result = state.editingItem
                ? await updateCartOnServer(state.editingItem.cart_detail_id, quantityValue, optionIds)
                : await addCartToServer(state.selectedMenu.id, quantityValue, optionIds);

            state.editingItem = null;
            state.cart = result.cart_items || [];
            cartHistoryModule.renderCart();

            showToast(result.message || 'カートに商品を追加しました');
            showScreen('menuScreen');
            requestAnimationFrame(menuModule.refreshCategoryScrollButtons);
        } catch (error) {
            showToast(error.message || 'カート追加に失敗しました');
        }
    });

    planModule.bindPlanEvents();
    menuModule.bindCategoryScroll();
    cartHistoryModule.bindCartHistoryEvents();

    menuModule.renderCategoryTabs();
    menuModule.renderMenu();
    cartHistoryModule.renderCart();
    cartHistoryModule.renderHistory();

    // 途中からQRを読んだ場合（別端末・タブを開き直した場合を含む）でも、
    // DBのコース開始時刻から残り時間を復元して表示する。
    syncDrinkTimer();

    if (window.MOS_CART_FLASH?.message) {
        showToast(window.MOS_CART_FLASH.message);
        showScreen('menuScreen');
        requestAnimationFrame(menuModule.refreshCategoryScrollButtons);
    }

    // レジで会計を通した後のQRで開かれた場合、注文できない理由を画面上部に出す。
    // 実際の遮断はサーバー側（会計状態チェック）で行うので、ここは案内のみ。
    if (window.MOS_DATA.billingClosed === true) {
        const banner = document.getElementById('planConflictBanner');
        const message = document.getElementById('planConflictMessage');

        if (banner && message) {
            message.textContent = 'お会計が完了しているため、ご注文いただけません。スタッフをお呼びください。';
            banner.classList.add('show');
            banner.setAttribute('aria-hidden', 'false');
        }
    }
});
