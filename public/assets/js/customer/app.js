/**
 * 客側アプリの中心スクリプト。
 * window.MOS_DATA（plans/categories/menus）を読み込み、共有 state（卓番号・選択プラン・
 * カート・履歴など）と共有関数（showScreen / formatYen / getDisplayPrice / openProduct 等）を定義。
 * これらを context として各モジュール（plans / menu / cart-history）に渡して画面機能を生成する。
 *
 * 主な関数:
 *   showScreen()        … .screen の active 切り替えで画面遷移
 *   getDisplayPrice()   … プラン適用後の価格（飲み放題プランはドリンク0円）
 *   isDrinkCategory()   … 「ドリンク」カテゴリ判定
 *   openProduct()       … 商品詳細画面を開く
 */
document.addEventListener('DOMContentLoaded', () => {
    const plans = window.MOS_DATA.plans;
    const categories = window.MOS_DATA.categories;
    const menus = window.MOS_DATA.menus;

    const state = {
        tableNumber: '',
        selectedPlanId: null,
        activeCategory: categories[0] ?? '',
        selectedMenu: null,
        cart: [],
        history: [],
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

    function isDrinkCategory(category) {
        return String(category).includes('ドリンク') || String(category).includes('繝峨Μ');
    }

    function getDisplayPrice(menu) {
        if (
            (state.selectedPlanId === 'standard' || state.selectedPlanId === 'premium') &&
            isDrinkCategory(menu.category)
        ) {
            return 0;
        }

        return menu.price;
    }

    let cartHistoryModule;

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
        renderMenu: menuModule.renderMenu,
        refreshCategoryScrollButtons: menuModule.refreshCategoryScrollButtons,
        onPlanConfirmed
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
        refreshCategoryScrollButtons: menuModule.refreshCategoryScrollButtons
    });

    document.getElementById('tableSubmitButton').addEventListener('click', () => {
        const input = document.getElementById('tableNumberInput');
        const error = document.getElementById('tableError');
        const value = input.value.trim();

        if (!/^\d{1,3}$/.test(value)) {
            error.textContent = '卓番号を数字で入力してください';
            return;
        }

        state.tableNumber = value;
        error.textContent = '';

        showScreen('planScreen');
    });

    document.getElementById('productBackButton').addEventListener('click', () => {
        if (state.editingItem) {
            state.cart.push(state.editingItem);
            state.editingItem = null;
            cartHistoryModule.renderCart();
        }

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

    document.getElementById('addCartButton').addEventListener('click', () => {
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

        const priceToApply = getDisplayPrice(state.selectedMenu);

        state.editingItem = null;
        cartHistoryModule.addCart(state.selectedMenu, quantityValue, priceToApply);

        showToast(`${state.selectedMenu.name}を追加しました`);
        showScreen('menuScreen');
        requestAnimationFrame(menuModule.refreshCategoryScrollButtons);
    });

    planModule.bindPlanEvents();
    menuModule.bindCategoryScroll();
    cartHistoryModule.bindCartHistoryEvents();

    menuModule.renderCategoryTabs();
    menuModule.renderMenu();
    cartHistoryModule.renderCart();
    cartHistoryModule.renderHistory();
});
