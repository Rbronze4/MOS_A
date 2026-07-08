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

    function formatYen(value) {
        return '¥' + Number(value || 0).toLocaleString('ja-JP');
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

    function addCartToServer(productId, quantity) {
        return postCartAction('/MOS_A/public/customer/cart/add', {
            session_id: state.sessionId,
            product_id: productId,
            quantity
        });
    }

    function updateCartOnServer(productId, quantity) {
        return postCartAction('/MOS_A/public/customer/cart/update', {
            session_id: state.sessionId,
            product_id: productId,
            quantity
        });
    }

    function deleteCartFromServer(productId) {
        return postCartAction('/MOS_A/public/customer/cart/delete', {
            session_id: state.sessionId,
            product_id: productId
        });
    }

    function submitOrderToServer() {
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
        const imageSrc = menu.image_path || '/assets/images/no-image.png';
        imageFrame.innerHTML = `<img src="${imageSrc}" alt="${menu.name}" style="width: 100%; height: 100%; object-fit: cover;">`;

        document.getElementById('productName').textContent = menu.name;
        document.getElementById('productPrice').textContent = formatYen(getDisplayPrice(menu));
        document.getElementById('quantityInput').value = String(quantity);

        showScreen('productScreen');
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
    // 飲み放題タイマー（フロントのみ・sessionStorageに終了予定時刻を保存）
    //   - プラン確定時に開始（終了予定時刻 = 現在 + 制限時間）
    //   - 注文画面 上部バー右に「ラストオーダー HH:MM（残り○分）」を表示（残りは分刻み）
    //   - ラストオーダー = コース終了の30分前
    //   - 単品プランはタイマーなし（非表示）
    //   ※サーバー時刻基準ではなく端末時刻基準。将来DB化で差し替え予定。
    // ============================================================
    const TIMER_STORAGE_KEY = 'mosDrinkTimer';
    const LAST_ORDER_BEFORE_MS = 30 * 60 * 1000; // ラストオーダーはコース終了の30分前
    let timerIntervalId = null;

    function loadTimer() {
        try {
            return JSON.parse(sessionStorage.getItem(TIMER_STORAGE_KEY));
        } catch (error) {
            return null;
        }
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

        const timer = loadTimer();
        if (!timer) {
            el.style.display = 'none';
            return;
        }

        el.style.display = '';
        // ラストオーダー時刻 = コース終了予定の30分前
        const lastOrderAt = timer.endsAt - LAST_ORDER_BEFORE_MS;
        const remainMs = lastOrderAt - Date.now();

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

    // 飲み放題プランのタイマーを開始し、終了予定時刻を保存する
    function startDrinkTimer(minutes) {
        const now = Date.now();
        const timer = {
            tableNo: state.tableNumber,
            minutes,
            startedAt: now,
            endsAt: now + minutes * 60 * 1000
        };

        sessionStorage.setItem(TIMER_STORAGE_KEY, JSON.stringify(timer));
        startTimerInterval();
    }

    // タイマーを破棄（単品プランなど）
    function clearDrinkTimer() {
        sessionStorage.removeItem(TIMER_STORAGE_KEY);
        stopTimerInterval();

        const el = document.getElementById('menuRemainTime');
        if (el) {
            el.style.display = 'none';
        }
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

        if (planId === 'single' || !minutes) {
            clearDrinkTimer();
        } else {
            startDrinkTimer(minutes);
        }
    }

    const planModule = window.MOS.customer.createPlanModule({
        plans,
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
        showToast
    });

    cartHistoryModule = window.MOS.customer.createCartHistoryModule({
        state,
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
        const value = input.value.trim();

        if (!/^\d{1,3}$/.test(value)) {
            error.textContent = '卓番号を数字で入力してください';
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

        if (!state.sessionId) {
            showToast('卓番号とプランを選択してください');
            showScreen(state.hasActiveCustomerPlan ? 'tableScreen' : 'planScreen');
            return;
        }

        try {
            const result = state.editingItem
                ? await updateCartOnServer(state.selectedMenu.id, quantityValue)
                : await addCartToServer(state.selectedMenu.id, quantityValue);

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

    if (window.MOS_CART_FLASH?.message) {
        showToast(window.MOS_CART_FLASH.message);
        showScreen('menuScreen');
        requestAnimationFrame(menuModule.refreshCategoryScrollButtons);
    }
});
