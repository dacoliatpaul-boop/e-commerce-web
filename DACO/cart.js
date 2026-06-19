

/**
 * 
 *
 *
 * @param {string} name       
 * @param {string} category   
 * @param {number} price      
 * @param {number} productId S
 * @param {number} [qty=1]   
 */
async function DCO_addToCart(name, category, price, productId, qty = 1) {
    if (!productId) {
        console.error('DCO_addToCart: productId is required');
        return;
    }

    const fd = new FormData();
    fd.append('action',     'add');
    fd.append('product_id', productId);
    fd.append('quantity',   qty);

    try {
        const res  = await fetch('cart.php', { method: 'POST', body: fd });

        
        if (res.status === 401) {
            window.location.href = 'login.php';
            return;
        }

        const data = await res.json();

        if (data.success) {
            DCO_showToast(`${name} added to cart`);
            DCO_updateCartBadge(data.cart_count);
        } else {
            DCO_showToast(data.message || 'Could not add to cart.', true);
        }
    } catch (err) {
        console.error('Cart error:', err);
        DCO_showToast('Network error. Please try again.', true);
    }
}

function DCO_showToast(message, isError = false) {
    let toast = document.getElementById('dco-toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'dco-toast';
        toast.style.cssText = `
            position:fixed; bottom:32px; left:50%; transform:translateX(-50%) translateY(20px);
            background:${isError ? '#b91c1c' : '#171717'}; color:#fff;
            padding:12px 24px; font-size:.85rem; letter-spacing:.05em;
            opacity:0; transition:opacity .3s, transform .3s; z-index:9999;
            white-space:nowrap; pointer-events:none;
        `;
        document.body.appendChild(toast);
    }
    toast.textContent        = message;
    toast.style.background   = isError ? '#b91c1c' : '#171717';
    toast.style.opacity      = '1';
    toast.style.transform    = 'translateX(-50%) translateY(0)';
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => {
        toast.style.opacity   = '0';
        toast.style.transform = 'translateX(-50%) translateY(20px)';
    }, 2500);
}


function DCO_updateCartBadge(count) {
    const badge = document.getElementById('cart-badge');
    if (badge) {
        badge.textContent   = count > 0 ? count : '';
        badge.style.display = count > 0 ? 'inline' : 'none';
    }
}


document.addEventListener('DOMContentLoaded', async () => {
    try {
        const res  = await fetch('cart.php?action=view');
        if (res.ok) {
            const data = await res.json();
            if (data.success) DCO_updateCartBadge(data.cart_count);
        }
    } catch (_) { }
});
