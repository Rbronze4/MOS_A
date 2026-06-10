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

    const planModule = window.MOS.customer.createPlanModule({
        plans,
        state,
        categories,
        formatYen,
        findPlan,
        showScreen,
        renderMenu: menuModule.renderMenu
    });

    cartHistoryModule = window.MOS.customer.createCartHistoryModule({
        state,
        formatYen,
        findMenu,
        showScreen,
        showToast,
        getDisplayPrice,
        openProduct
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
    });

    planModule.bindPlanEvents();
    menuModule.bindCategoryScroll();
    cartHistoryModule.bindCartHistoryEvents();

    menuModule.renderCategoryTabs();
    menuModule.renderMenu();
    cartHistoryModule.renderCart();
    cartHistoryModule.renderHistory();
});
