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

    function cartTotal() {
        return state.cart.reduce((sum, item) => {
            return sum + item.price * item.quantity;
        }, 0);
    }

    function historyTotal() {
        return state.history.reduce((sum, item) => {
            return sum + item.price * item.quantity;
        }, 0);
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

        document.getElementById('planConfirmButton').dataset.planId = plan.id;
        document.getElementById('planModal').classList.add('show');
    }

    function closePlanModal() {
        document.getElementById('planModal').classList.remove('show');
    }

    function getDisplayPrice(menu) {
    // スタンダード(standard)かプレミアム(premium)の場合は、
    // 「ドリンク」カテゴリの商品なら0円にするロジック
    if ((state.selectedPlanId === 'standard' || state.selectedPlanId === 'premium') 
        && menu.category === 'ドリンク') {
        return 0;
    }
    return menu.price;
}

    function renderCategoryTabs() {
        const categoryTabs = document.getElementById('categoryTabs');

        categoryTabs.innerHTML = categories.map(category => {
            const activeClass = category === state.activeCategory ? 'active' : '';

            return `
                <button class="category-tab ${activeClass}" data-category="${category}">
                    ${category}
                </button>
            `;
        }).join('');

        categoryTabs.querySelectorAll('.category-tab').forEach(button => {
            button.addEventListener('click', () => {
                state.activeCategory = button.dataset.category;
                
                // ★ここがポイント：メニューの再描画と、タブ自体の再描画の両方を行う
                renderMenu(); 
                renderCategoryTabs(); 
            });
        });
    }

   function renderMenu() {
        const menuGrid = document.getElementById('menuGrid');

        // 表示用の価格を算出するヘルパー関数
        function getDisplayPrice(menu) {
            // スタンダードかプレミアムなら、ドリンクカテゴリは0円にする
            if ((state.selectedPlanId === 'standard' || state.selectedPlanId === 'premium') 
                && menu.category === 'ドリンク') {
                return 0;
            }
            return menu.price;
        }

        const filteredMenus = menus.filter(menu => {
            return menu.category === state.activeCategory;
        });

        if (filteredMenus.length === 0) {
            menuGrid.innerHTML = '<p class="empty-message">商品がありません</p>';
            return;
        }

        menuGrid.innerHTML = filteredMenus.map(menu => {
            const imageSrc = menu.image_path || 'assets/images/common/img.jpg';
            const displayPrice = getDisplayPrice(menu); // ここでプランを考慮した価格を取得

            return `
                <button class="menu-card" data-menu-id="${menu.id}">
                    <div class="menu-image-frame" style="display: flex; align-items: center; justify-content: center; background: #eee;">
                        <img src="${imageSrc}" 
                             alt="${menu.name}" 
                             style="width: 100%; height: 100%; object-fit: cover; display: block;" 
                             onerror="this.parentElement.style.display='none'; console.error('画像読み込み失敗:', '${imageSrc}')">
                    </div>

                    <div class="menu-card-body">
                        <div class="menu-name">${menu.name}</div>
                        <div class="menu-price">${formatYen(displayPrice)}</div>
                    </div>
                </button>
            `;
        }).join('');

        // イベントリスナーの追加
        menuGrid.querySelectorAll('.menu-card').forEach(card => {
            card.addEventListener('click', () => {
                const menu = findMenu(card.dataset.menuId);

                if (!menu) return;

                state.selectedMenu = menu;

                const imageFrame = document.getElementById('productImageFrame');
                const imageSrc = menu.image_path || '/assets/images/no-image.png';
                
                imageFrame.innerHTML = `<img src="${imageSrc}" alt="${menu.name}" style="width: 100%; height: 100%; object-fit: cover;">`;

                document.getElementById('productName').textContent = menu.name;
                // ここも同様にプラン適用後の価格を表示
                document.getElementById('productPrice').textContent = formatYen(getDisplayPrice(menu));
                document.getElementById('quantityInput').value = '1';

                showScreen('productScreen');
            });
        });
    }
    // 引数を menu, quantity, price の3つにする
function addCart(menu, quantity, price) {
    console.log("addCartが呼び出されました:", { menu, quantity, price }); // ← これを追加

    const existing = state.cart.find(item => String(item.id) === String(menu.id));

    if (existing) {
        existing.quantity += quantity;
    } else {
        // もしここでまだ「price is not defined」が出るなら、
        // 上の引数で price を受け取れていません
        state.cart.push({
            id: menu.id,
            name: menu.name,
            price: price, 
            quantity: quantity
        });
    }

    renderCart();
}
    function renderCart() {
        document.getElementById('cartTotal').textContent = formatYen(cartTotal());

        const cartList = document.getElementById('cartList');

        if (state.cart.length === 0) {
            cartList.innerHTML = '<p class="empty-message">カートは空です</p>';
            return;
        }

        cartList.innerHTML = state.cart.map(item => {
            return `
                <div class="cart-row">
                    <span>${item.name}</span>
                    <span>${item.quantity}</span>
                    <span>${formatYen(item.price)}</span>

                    <button class="pill-button change-button" data-action="change" data-menu-id="${item.id}">
                        変更
                    </button>

                    <button class="pill-button delete-button" data-action="delete" data-menu-id="${item.id}">
                        削除
                    </button>
                </div>
            `;
        }).join('');

        cartList.querySelectorAll('.pill-button').forEach(button => {
            button.addEventListener('click', () => {
                const menuId = button.dataset.menuId;
                const action = button.dataset.action;
                const cartItem = state.cart.find(item => String(item.id) === String(menuId));

                if (!cartItem) {
                    return;
                }

                if (action === 'delete') {
                    state.cart = state.cart.filter(item => String(item.id) !== String(menuId));
                    renderCart();
                    showToast(`${cartItem.name}を削除しました`);
                    return;
                }

                if (action === 'change') {
                    const menu = findMenu(menuId);

                    if (!menu) {
                        return;
                    }

                    // 編集対象のカート内データを退避する
                    state.editingItem = cartItem;
                    state.selectedMenu = menu;

                    // 画像更新処理
                    const imageFrame = document.getElementById('productImageFrame');
                    const imageSrc = menu.image_path || '/assets/images/common/img.jpg';
                    imageFrame.innerHTML = `<img src="${imageSrc}" alt="${menu.name}" style="width: 100%; height: 100%; object-fit: cover;">`;

                    // 画面の各値を設定
                    document.getElementById('productName').textContent = menu.name;
                    document.getElementById('productPrice').textContent = formatYen(menu.price);
                    document.getElementById('quantityInput').value = String(cartItem.quantity);

                    // カートから一時的に削除（確定時や戻る時に再投入するため）
                    state.cart = state.cart.filter(item => String(item.id) !== String(menuId));

                    showScreen('productScreen');
                }
            });
        });
    }

    function openOrderModal() {
        if (state.cart.length === 0) {
            showToast('商品が選択されていません');
            return;
        }

        document.getElementById('modalOrderTotal').textContent = formatYen(cartTotal());

        document.getElementById('modalOrderList').innerHTML = state.cart.map(item => {
            return `
                <div class="modal-order-row">
                    <span>${item.name}</span>
                    <span>${item.quantity}</span>
                    <span>${formatYen(item.price)}</span>
                </div>
            `;
        }).join('');

        document.getElementById('orderModal').classList.add('show');
    }

    function closeOrderModal() {
        document.getElementById('orderModal').classList.remove('show');
    }

    function renderHistory() {
        document.getElementById('historyTotal').textContent = formatYen(historyTotal());

        const historyList = document.getElementById('historyList');

        if (state.history.length === 0) {
            historyList.innerHTML = '<p class="empty-message">注文履歴はありません</p>';
            return;
        }

        historyList.innerHTML = state.history.map(item => {
            return `
                <div class="history-row">
                    <span class="history-status">[注文済み]</span>
                    <span>${item.name}</span>
                    <span>${item.quantity}</span>
                    <span>${formatYen(item.price)}</span>

                    <button class="reorder-button" data-menu-id="${item.id}">
                        再注文
                    </button>
                </div>
            `;
        }).join('');

        historyList.querySelectorAll('.reorder-button').forEach(button => {
            button.addEventListener('click', () => {
                const historyItem = state.history.find(item => String(item.id) === String(button.dataset.menuId));

                if (!historyItem) {
                    return;
                }

                addCart(historyItem, historyItem.quantity);
                showToast(`${historyItem.name}をカートに追加しました`);
            });
        });
    }

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
            ? 'ドリンク'
            : categories[0];

        renderMenu();
        showScreen('menuScreen');
    });

    const categoryTabs = document.getElementById('categoryTabs');
    const categoryScrollLeft = document.getElementById('categoryScrollLeft');
    const categoryScrollRight = document.getElementById('categoryScrollRight');

    function updateCategoryScrollButtons() {
        if (!categoryTabs || !categoryScrollLeft || !categoryScrollRight) return;

        const maxScrollLeft = categoryTabs.scrollWidth - categoryTabs.clientWidth;

        if (categoryTabs.scrollLeft <= 0) {
            categoryScrollLeft.classList.add('hidden');
        } else {
            categoryScrollLeft.classList.remove('hidden');
        }

        if (categoryTabs.scrollLeft >= maxScrollLeft - 1) {
            categoryScrollRight.classList.add('hidden');
        } else {
            categoryScrollRight.classList.remove('hidden');
        }
    }

    if (categoryScrollLeft) {
        categoryScrollLeft.addEventListener('click', () => {
            categoryTabs.scrollBy({ left: -220, behavior: 'smooth' });
        });
    }

    if (categoryScrollRight) {
        categoryScrollRight.addEventListener('click', () => {
            categoryTabs.scrollBy({ left: 220, behavior: 'smooth' });
        });
    }

    if (categoryTabs) {
        categoryTabs.addEventListener('scroll', updateCategoryScrollButtons);
        window.addEventListener('resize', updateCategoryScrollButtons);
    }

    document.getElementById('productBackButton').addEventListener('click', () => {

        if (state.editingItem) {
            state.cart.push(state.editingItem);
            state.editingItem = null; // 編集モードを解除
            renderCart(); // カート画面を再描画（戻った時に反映されるように）
        }

        showScreen('menuScreen');
    });

    document.getElementById('minusButton').addEventListener('click', () => {
        const input = document.getElementById('quantityInput');
        const current = Number(input.value || 1);

        input.value = String(Math.max(1, current - 1));
    });

    document.getElementById('plusButton').addEventListener('click', () => {
        const input = document.getElementById('quantityInput');
        const current = Number(input.value || 1);

        input.value = String(Math.min(99, current + 1));
    });

    document.getElementById('addCartButton').addEventListener('click', () => {
    console.log("確定ボタンクリック！"); // ← 追加

    if (!state.selectedMenu) {
        showToast('商品が選択されていません');
        return;
    }

    const quantity = Math.max(1, Math.min(99, Number(document.getElementById('quantityInput').value || 1)));
    const priceToApply = getDisplayPrice(state.selectedMenu);

    console.log("価格計算結果:", priceToApply); // ← 追加

    state.editingItem = null;

    addCart(state.selectedMenu, quantity, priceToApply);

    showToast(`${state.selectedMenu.name}を追加しました`);
    showScreen('menuScreen');
    });

    document.getElementById('cartButton').addEventListener('click', () => {
        renderCart();
        showScreen('cartScreen');
    });

    document.getElementById('historyButton').addEventListener('click', () => {
        renderHistory();
        showScreen('historyScreen');
    });

    document.getElementById('cartBackButton').addEventListener('click', () => {
        showScreen('menuScreen');
    });

    document.getElementById('historyBackButton').addEventListener('click', () => {
        showScreen('menuScreen');
    });

    document.getElementById('orderConfirmButton').addEventListener('click', () => {
        openOrderModal();
    });

    document.getElementById('closeOrderModalButton').addEventListener('click', () => {
        closeOrderModal();
    });

    document.getElementById('submitOrderButton').addEventListener('click', () => {
        state.history.push(...state.cart.map(item => ({ ...item })));
        state.cart = [];

        closeOrderModal();
        renderCart();
        renderHistory();

        showToast('注文を送信しました');
        showScreen('menuScreen');
    });

    renderCategoryTabs();
    renderMenu();
    renderCart();
    renderHistory();
});