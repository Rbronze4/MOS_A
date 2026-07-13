document.addEventListener('DOMContentLoaded', () => {
    if (window.MOS?.initSideMenu) {
        window.MOS.initSideMenu();
    }

    const cartList = document.getElementById('staffCartList');
    const cartTotal = document.getElementById('staffCartTotal');
    const submitButton = document.getElementById('staffOrderSubmitButton');

    const tableNo = window.staffOrderInfo?.tableNo ?? '';
    const customerId = window.staffOrderInfo?.customerId ?? '';
    const returnRef = window.staffOrderInfo?.returnRef ?? 'home';
    const submitUrl = window.staffOrderInfo?.submitUrl ?? '/MOS_A/public/staff/order/submit';
    const cartStorageKey = `staffOrderCart_${tableNo}_${customerId}`;

    let cart = loadCart();

    function loadCart() {
        const savedCart = sessionStorage.getItem(cartStorageKey);

        if (!savedCart) {
            return [];
        }

        try {
            return JSON.parse(savedCart);
        } catch (error) {
            return [];
        }
    }

    function saveCart() {
        sessionStorage.setItem(cartStorageKey, JSON.stringify(cart));
    }

    function renderCart() {
        if (!cartList || !cartTotal) return;

        cartList.innerHTML = '';

        if (cart.length === 0) {
            cartList.innerHTML = '<p class="empty-cart-text">商品が選択されていません</p>';
            cartTotal.textContent = '￥0';
            return;
        }

        let total = 0;

        cart.forEach(item => {
            total += item.price * item.qty;

            const row = document.createElement('div');
            row.className = 'staff-cart-item';
            row.innerHTML = `
                <div class="staff-cart-item-name">${escapeHtml(item.name)}</div>
                <div class="staff-cart-control">
                    <button type="button" class="cart-minus" data-id="${item.id}">−</button>
                    <span>${item.qty}</span>
                    <button type="button" class="cart-plus" data-id="${item.id}">＋</button>
                    <span>￥${(item.price * item.qty).toLocaleString()}</span>
                </div>
            `;

            cartList.appendChild(row);
        });

        cartTotal.textContent = `￥${total.toLocaleString()}`;

        document.querySelectorAll('.cart-minus').forEach(button => {
            button.addEventListener('click', () => {
                changeQty(Number(button.dataset.id), -1);
            });
        });

        document.querySelectorAll('.cart-plus').forEach(button => {
            button.addEventListener('click', () => {
                changeQty(Number(button.dataset.id), 1);
            });
        });
    }

    function addCart(menu) {
        const existing = cart.find(item => Number(item.id) === Number(menu.id));

        if (existing) {
            existing.qty += 1;
        } else {
            cart.push({
                id: menu.id,
                name: menu.name,
                price: menu.price,
                qty: 1
            });
        }

        saveCart();
        renderCart();
    }

    function changeQty(menuId, diff) {
        const item = cart.find(cartItem => Number(cartItem.id) === Number(menuId));
        if (!item) return;

        item.qty += diff;

        if (item.qty <= 0) {
            cart = cart.filter(cartItem => Number(cartItem.id) !== Number(menuId));
        }

        saveCart();
        renderCart();
    }

    function clearCart() {
        cart = [];
        sessionStorage.removeItem(cartStorageKey);
        renderCart();
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    document.querySelectorAll('.staff-menu-card').forEach(card => {
        card.addEventListener('click', () => {
            if (card.disabled) return;

            addCart({
                id: Number(card.dataset.menuId),
                name: card.dataset.menuName,
                price: Number(card.dataset.menuPrice)
            });
        });
    });

    const entryBackButton = document.getElementById('staffOrderBackButton');
    if (entryBackButton) {
        entryBackButton.addEventListener('click', () => {
            const params = new URLSearchParams(window.location.search);
            const entryCustomerId = params.get('customer_id') || '';
            const entryReturnRef = params.get('ref') || '';

            if (entryReturnRef === 'customerDetail' && entryCustomerId !== '') {
                location.href = `/MOS_A/public/staff/customer/detail?customer_id=${encodeURIComponent(entryCustomerId)}`;
                return;
            }

            if (entryReturnRef === 'customerList') {
                location.href = '/MOS_A/public/staff?ref=customerList';
                return;
            }

            location.href = '/MOS_A/public/staff?ref=home';
        });
    }

    const menuBackButton = document.getElementById('staffOrderMenuBackButton');
    if (menuBackButton) {
        menuBackButton.addEventListener('click', () => {
            if (returnRef === 'customerDetail' && customerId !== '') {
                location.href = `/MOS_A/public/staff/customer/detail?customer_id=${encodeURIComponent(customerId)}`;
                return;
            }

            if (returnRef === 'customerList') {
                location.href = '/MOS_A/public/staff?ref=customerList';
                return;
            }

            if (returnRef === 'detail') {
                location.href = '/MOS_A/public/staff?ref=orderDetail';
                return;
            }

            location.href = '/MOS_A/public/staff?ref=home';
        });
    }

    if (submitButton) {
        submitButton.addEventListener('click', async () => {
            if (cart.length === 0) {
                alert('商品を選択してください。');
                return;
            }

            submitButton.disabled = true;

            try {
                const response = await fetch(submitUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        table_number: tableNo,
                        customer_id: customerId,
                        items: cart.map(item => ({
                            product_id: item.id,
                            quantity: item.qty
                        }))
                    })
                });
                const result = await response.json();

                if (!response.ok || !result.ok) {
                    throw new Error(result.message || '注文登録に失敗しました。');
                }

                alert('注文を受け付けました。');
                clearCart();
            } catch (error) {
                alert(error instanceof Error ? error.message : '注文登録に失敗しました。');
            } finally {
                submitButton.disabled = false;
            }
        });
    }

    const clearButton = document.getElementById('staffCartClearButton');
    if (clearButton) {
        clearButton.addEventListener('click', () => {
            if (cart.length === 0) {
                return;
            }

            if (confirm('注文かごの商品をすべて削除しますか？')) {
                clearCart();
            }
        });
    }

    renderCart();
});
