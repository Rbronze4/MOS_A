document.addEventListener('DOMContentLoaded', () => {
    if (window.MOS?.initSideMenu) {
        window.MOS.initSideMenu();
    }

    const cartList = document.getElementById('staffCartList');
    const cartTotal = document.getElementById('staffCartTotal');
    const submitButton = document.getElementById('staffOrderSubmitButton');
    const menuBrowse = document.getElementById('staffMenuBrowse');
    const productSelection = document.getElementById('staffProductSelection');
    const selectionOptions = document.getElementById('staffProductOptions');
    const selectionError = document.getElementById('staffProductSelectionError');
    const selectionQuantity = document.getElementById('staffProductQuantity');
    const selectionTotal = document.getElementById('staffProductSelectionTotal');
    const menus = Array.isArray(window.staffOrderMenus) ? window.staffOrderMenus : [];

    const tableNo = window.staffOrderInfo?.tableNo ?? '';
    const customerId = window.staffOrderInfo?.customerId ?? '';
    const returnRef = window.staffOrderInfo?.returnRef ?? 'home';
    const submitUrl = window.staffOrderInfo?.submitUrl ?? '/MOS_A/public/staff/order/submit';
    const cartStorageKey = `staffOrderCart_${tableNo}_${customerId}`;

    let cart = loadCart();
    let selectedMenu = null;
    let selectedQuantityValue = 1;

    function cartLineKey(productId, optionIds = []) {
        const normalizedOptionIds = optionIds.map(Number).filter(Number.isInteger).sort((left, right) => left - right);
        return `${Number(productId)}-${normalizedOptionIds.join('.')}`;
    }

    function loadCart() {
        const savedCart = sessionStorage.getItem(cartStorageKey);

        if (!savedCart) return [];

        try {
            const parsed = JSON.parse(savedCart);
            if (!Array.isArray(parsed)) return [];

            return parsed.map(item => {
                const options = Array.isArray(item.options) ? item.options : [];
                const optionIds = options.map(option => Number(option.option_id || option.id)).filter(Number.isInteger);

                return {
                    key: item.key || cartLineKey(item.id, optionIds),
                    id: Number(item.id),
                    name: String(item.name || ''),
                    price: Number(item.price || 0),
                    qty: Math.max(1, Number(item.qty || 1)),
                    options
                };
            }).filter(item => Number.isInteger(item.id) && item.id > 0);
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
            const optionSummary = item.options.map(option => option.name).filter(Boolean).join('、');
            const row = document.createElement('div');
            row.className = 'staff-cart-item';
            row.innerHTML = `
                <div class="staff-cart-item-description">
                    <div class="staff-cart-item-name">${escapeHtml(item.name)}</div>
                    ${optionSummary === '' ? '' : `<small>${escapeHtml(optionSummary)}</small>`}
                </div>
                <div class="staff-cart-control">
                    <button type="button" class="cart-minus" data-key="${escapeHtml(item.key)}">−</button>
                    <span>${item.qty}</span>
                    <button type="button" class="cart-plus" data-key="${escapeHtml(item.key)}">＋</button>
                    <span>￥${(item.price * item.qty).toLocaleString()}</span>
                </div>
            `;
            cartList.appendChild(row);
        });

        cartTotal.textContent = `￥${total.toLocaleString()}`;

        cartList.querySelectorAll('.cart-minus').forEach(button => {
            button.addEventListener('click', () => changeQty(button.dataset.key, -1));
        });
        cartList.querySelectorAll('.cart-plus').forEach(button => {
            button.addEventListener('click', () => changeQty(button.dataset.key, 1));
        });
    }

    function addCart(item) {
        const existing = cart.find(cartItem => cartItem.key === item.key);

        if (existing) {
            existing.qty += item.qty;
        } else {
            cart.push(item);
        }

        saveCart();
        renderCart();
    }

    function changeQty(lineKey, diff) {
        const item = cart.find(cartItem => cartItem.key === lineKey);
        if (!item) return;

        item.qty += diff;

        if (item.qty <= 0) {
            cart = cart.filter(cartItem => cartItem.key !== lineKey);
        }

        saveCart();
        renderCart();
    }

    function clearCart() {
        cart = [];
        sessionStorage.removeItem(cartStorageKey);
        renderCart();
    }

    function optionInputTemplate(group, option) {
        const inputType = group.selection_type === 'MULTIPLE' ? 'checkbox' : 'radio';
        const inputName = `staff-option-group-${Number(group.option_group_id)}`;
        const additionalPrice = Number(option.additional_price || 0);

        return `
            <label class="staff-option-choice">
                <input
                    type="${inputType}"
                    name="${inputName}"
                    value="${Number(option.option_id)}"
                    data-option-name="${escapeHtml(option.option_name)}"
                    data-additional-price="${additionalPrice}"
                >
                <span>${escapeHtml(option.option_name)}</span>
                <small>${additionalPrice > 0 ? `＋￥${additionalPrice.toLocaleString()}` : '追加料金なし'}</small>
            </label>
        `;
    }

    function openProductSelection(menuId) {
        selectedMenu = menus.find(menu => Number(menu.id) === Number(menuId)) || null;
        if (!selectedMenu || !menuBrowse || !productSelection) return;

        selectedQuantityValue = 1;
        selectionError.textContent = '';
        document.getElementById('staffSelectedProductImage').src = selectedMenu.image_path || '';
        document.getElementById('staffSelectedProductImage').alt = selectedMenu.name || '';
        document.getElementById('staffSelectedProductName').textContent = selectedMenu.name || '';
        document.getElementById('staffSelectedProductBasePrice').textContent = Number(selectedMenu.plan_applied_flag) === 1
            ? '飲み放題対象 ￥0'
            : `税込 ￥${Number(selectedMenu.display_price || 0).toLocaleString()}`;

        const groups = Array.isArray(selectedMenu.option_groups) ? selectedMenu.option_groups : [];
        selectionOptions.innerHTML = groups.length === 0
            ? ''
            : groups.map(group => {
                const requiredLabel = Number(group.is_required) === 1
                    ? '<span class="staff-option-required">必須</span>'
                    : '<span class="staff-option-optional">任意</span>';
                const noneChoice = group.selection_type === 'SINGLE' && Number(group.is_required) !== 1
                    ? `
                        <label class="staff-option-choice">
                            <input type="radio" name="staff-option-group-${Number(group.option_group_id)}" value="" checked>
                            <span>指定なし</span>
                            <small>追加料金なし</small>
                        </label>
                    `
                    : '';

                return `
                    <fieldset class="staff-option-group" data-group-name="${escapeHtml(group.group_name)}" data-required="${Number(group.is_required) === 1 ? '1' : '0'}">
                        <legend>${escapeHtml(group.group_name)} ${requiredLabel}</legend>
                        <div class="staff-option-choices">
                            ${noneChoice}
                            ${(Array.isArray(group.options) ? group.options : []).map(option => optionInputTemplate(group, option)).join('')}
                        </div>
                    </fieldset>
                `;
            }).join('');

        selectionOptions.querySelectorAll('input').forEach(input => {
            input.addEventListener('change', () => {
                selectionError.textContent = '';
                renderSelectionTotal();
            });
        });

        menuBrowse.hidden = true;
        productSelection.hidden = false;
        renderSelectionTotal();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function closeProductSelection() {
        if (!menuBrowse || !productSelection) return;
        productSelection.hidden = true;
        menuBrowse.hidden = false;
        selectedMenu = null;
        selectionError.textContent = '';
    }

    function selectedOptions() {
        if (!selectionOptions) return [];

        return Array.from(selectionOptions.querySelectorAll('input:checked'))
            .filter(input => input.value !== '')
            .map(input => ({
                option_id: Number(input.value),
                name: input.dataset.optionName || '',
                additional_price: Number(input.dataset.additionalPrice || 0)
            }));
    }

    function validateSelectedOptions() {
        const missingGroup = Array.from(selectionOptions.querySelectorAll('.staff-option-group'))
            .find(group => group.dataset.required === '1' && group.querySelectorAll('input:checked').length === 0);

        if (missingGroup) {
            selectionError.textContent = `${missingGroup.dataset.groupName}を選択してください。`;
            return false;
        }

        return true;
    }

    /**
     * 税抜価格へ税率を適用し、税込価格の1円未満を切り捨てる。
     * サーバー側(StaffOrderModel / MenuModel)のtaxIncludedPriceと同じ計算にすること。
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
    function unitPriceWithOptions(menu, optionPrice) {
        const planApplied = Number(menu.plan_applied_flag || 0) === 1;
        const netUnitPrice = planApplied ? 0 : Number(menu.price ?? 0);

        return taxIncludedPrice(netUnitPrice + Number(optionPrice || 0), menu.tax_rate);
    }

    function renderSelectionTotal() {
        if (!selectedMenu || !selectionQuantity || !selectionTotal) return;
        const optionPrice = selectedOptions().reduce((sum, option) => sum + option.additional_price, 0);
        const unitPrice = unitPriceWithOptions(selectedMenu, optionPrice);

        selectionQuantity.textContent = String(selectedQuantityValue);
        selectionTotal.textContent = `￥${(unitPrice * selectedQuantityValue).toLocaleString()}`;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    document.querySelectorAll('.staff-menu-card').forEach(card => {
        card.addEventListener('click', () => {
            if (!card.disabled) openProductSelection(Number(card.dataset.menuId));
        });
    });

    document.getElementById('staffProductQuantityMinus')?.addEventListener('click', () => {
        selectedQuantityValue = Math.max(1, selectedQuantityValue - 1);
        renderSelectionTotal();
    });
    document.getElementById('staffProductQuantityPlus')?.addEventListener('click', () => {
        selectedQuantityValue = Math.min(99, selectedQuantityValue + 1);
        renderSelectionTotal();
    });
    document.getElementById('staffProductSelectionBack')?.addEventListener('click', closeProductSelection);
    document.getElementById('staffProductSelectionCancel')?.addEventListener('click', closeProductSelection);
    document.getElementById('staffProductAddToCart')?.addEventListener('click', () => {
        if (!selectedMenu || !validateSelectedOptions()) return;

        const options = selectedOptions();
        const optionIds = options.map(option => option.option_id);
        const optionPrice = options.reduce((sum, option) => sum + option.additional_price, 0);

        addCart({
            key: cartLineKey(selectedMenu.id, optionIds),
            id: Number(selectedMenu.id),
            name: String(selectedMenu.name || ''),
            price: unitPriceWithOptions(selectedMenu, optionPrice),
            qty: selectedQuantityValue,
            options
        });
        closeProductSelection();
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
            if (productSelection && !productSelection.hidden) {
                closeProductSelection();
                return;
            }
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
                            quantity: item.qty,
                            option_ids: item.options.map(option => option.option_id)
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
            if (cart.length > 0 && confirm('注文かごの商品をすべて削除しますか？')) {
                clearCart();
            }
        });
    }

    renderCart();
});
