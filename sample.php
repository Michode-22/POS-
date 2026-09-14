<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>StockIT — Point of Sale</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --navy: #10233F; --blue-accent: #2E6BE6; --blue-soft: #E8F0FE; --amber: #E0A62E;
    --red: #E5484D; --green: #1FAA59; --bg: #F4F6F9; --white: #FFFFFF; --border: #E5E9F0;
    --text-primary: #1A1F2B; --text-secondary: #6B7280; --muted-nav: #9FB0C9;
    --radius: 10px; --shadow: 0 1px 3px rgba(16,35,63,0.08), 0 1px 2px rgba(16,35,63,0.06);
}
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; }
body { font-family: 'Inter', Helvetica, Arial, sans-serif; background: var(--bg); color: var(--text-primary); }
a { text-decoration: none; color: inherit; }
button { font-family: inherit; }
.app { display: flex; min-height: 100vh; }
.sidebar { width: 240px; min-width: 240px; background: var(--navy); padding: 24px 16px; display: flex; flex-direction: column; gap: 4px; position: relative; z-index: 20; }
.logo { padding: 4px 12px 24px; }
.logo-text { color: #fff; font-weight: 700; font-size: 20px; }
.nav { display: flex; flex-direction: column; gap: 4px; }
.nav-item { display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 8px; color: var(--muted-nav); font-weight: 500; font-size: 14px; }
.nav-icon { width: 20px; text-align: center; font-size: 14px; }
.nav-item:hover { background: rgba(255,255,255,0.06); color: #fff; }
.nav-item.active { background: var(--blue-accent); color: #fff; }
.sidebar-backdrop { display: none; }
.main { flex: 1; display: flex; flex-direction: column; min-width: 0; }
.topbar { height: 64px; background: var(--white); border-bottom: 1px solid var(--border); display: flex; align-items: center; padding: 0 24px; gap: 16px; position: sticky; top: 0; z-index: 10; }
.hamburger { display: none; background: none; border: none; font-size: 20px; color: var(--text-secondary); cursor: pointer; }

.spacer { flex: 1; }
.icon-button { position: relative; background: none; border: none; font-size: 18px; cursor: pointer; color: var(--text-secondary); padding: 6px; }
.notif-dot { position: absolute; top: 4px; right: 4px; width: 8px; height: 8px; border-radius: 50%; background: var(--red); border: 2px solid var(--white); }
.profile { display: flex; align-items: center; gap: 10px; margin-left: 8px; }
.avatar { width: 32px; height: 32px; border-radius: 50%; background: var(--blue-accent); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 13px; }
.profile-name { font-size: 14px; font-weight: 500; }
.content { flex: 1; padding: 28px 32px 40px; display: flex; flex-direction: column; gap: 20px; max-width: 1280px; }
.page-title { font-size: 24px; font-weight: 700; margin: 0; }
.pos-layout { display: flex; gap: 20px; align-items: flex-start; flex-wrap: wrap; }
.product-grid { flex: 2; min-width: 280px; display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 14px; }
.product-tile { background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow); padding: 16px; cursor: pointer; border: 1px solid transparent; }
.product-tile:hover { border-color: var(--blue-accent); }
.product-tile .pname { font-weight: 600; font-size: 14px; margin: 0 0 6px; }
.product-tile .pprice { color: var(--blue-accent); font-weight: 700; font-size: 15px; margin: 0 0 4px; }
.product-tile .pqty { font-size: 12px; color: var(--text-secondary); margin: 0; }
.cart-panel { flex: 1; min-width: 260px; background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow); padding: 20px; position: sticky; top: 84px; }
.cart-panel h2 { font-size: 16px; margin: 0 0 14px; }
.cart-items { display: flex; flex-direction: column; gap: 10px; min-height: 60px; max-height: 260px; overflow-y: auto; }
.cart-item { display: flex; justify-content: space-between; align-items: center; font-size: 13px; border-bottom: 1px solid var(--border); padding-bottom: 8px; }
.cart-empty { font-size: 13px; color: var(--text-secondary); text-align: center; padding: 20px 0; }
.cart-total-row { display: flex; justify-content: space-between; font-weight: 700; font-size: 16px; margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--border); }
.btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 12px 18px; border-radius: 8px; font-size: 14px; font-weight: 600; border: none; cursor: pointer; margin-top: 14px; }
.btn-primary { background: var(--blue-accent); color: #fff; }
@media (max-width: 768px) {
    .hamburger { display: block; }
    .sidebar { position: fixed; top: 0; left: 0; bottom: 0; transform: translateX(-100%); transition: transform 0.25s ease; box-shadow: 4px 0 16px rgba(0,0,0,0.15); }
    .sidebar.open { transform: translateX(0); }
    .sidebar-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.35); border: none; z-index: 15; }
    .sidebar-backdrop.visible { display: block; }
    .content { padding: 18px; }
    .cart-panel { position: static; }
    .profile-name { display: none; }

}
</style>
</head>
<body>
<div class="app">
    <button class="sidebar-backdrop" id="sidebarBackdrop"></button>
    <aside class="sidebar" id="sidebar">
        <div class="logo"><span class="logo-text">StockIT</span></div>
        <nav class="nav">
            <a href="index.html" class="nav-item"><span class="nav-icon">▦</span><span>Dashboard</span></a>
            <a href="point-of-sale.html" class="nav-item active"><span class="nav-icon">🛒</span><span>Point of Sale</span></a>
            <a href="inventory.html" class="nav-item"><span class="nav-icon">📦</span><span>Inventory</span></a>
            <a href="sales-history.html" class="nav-item"><span class="nav-icon">🕘</span><span>Sales History</span></a>
            <a href="sales-report.html" class="nav-item"><span class="nav-icon">📊</span><span>Sales Report</span></a>
        </nav>
    </aside>
    <div class="main">
        <header class="topbar">
            <button class="hamburger" id="sidebarToggle">☰</button>
            <div class="spacer"></div>
            <button class="icon-button">🔔<span class="notif-dot"></span></button>
            <div class="profile"><div class="avatar">J</div><span class="profile-name">admin</span></div>
        </header>
        <main class="content">
            <h1 class="page-title">Point of Sale</h1>
            <div class="pos-layout">
                <div class="product-grid" id="productGrid">
                    <div class="product-tile" data-id="1" data-name="Coca-Cola 1.5L" data-price="65.00"><p class="pname">Coca-Cola 1.5L</p><p class="pprice">₱65.00</p><p class="pqty">84 in stock</p></div>
                    <div class="product-tile" data-id="2" data-name="Lucky Me Pancit Canton" data-price="15.00"><p class="pname">Lucky Me Pancit Canton</p><p class="pprice">₱15.00</p><p class="pqty">6 in stock</p></div>
                    <div class="product-tile" data-id="3" data-name="Piattos Cheese 85g" data-price="32.00"><p class="pname">Piattos Cheese 85g</p><p class="pprice">₱32.00</p><p class="pqty">40 in stock</p></div>
                    <div class="product-tile" data-id="4" data-name="Nescafe 3-in-1" data-price="8.00"><p class="pname">Nescafe 3-in-1</p><p class="pprice">₱8.00</p><p class="pqty">3 in stock</p></div>
                    <div class="product-tile" data-id="5" data-name="Argentina Corned Beef" data-price="45.00"><p class="pname">Argentina Corned Beef</p><p class="pprice">₱45.00</p><p class="pqty">55 in stock</p></div>

               </div>
                <div class="cart-panel">
                    <h2>Current Order</h2>
                    <div class="cart-items" id="cartItems">
                        <p class="cart-empty" id="cartEmptyMsg">No items yet. Tap a product to add it.</p>
                    </div>
                    <div class="cart-total-row"><span>Total</span><span id="cartTotal">₱0.00</span></div>
                    <button class="btn btn-primary" id="checkoutBtn">Checkout</button>
                </div>
            </div>
        </main>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const t = document.getElementById('sidebarToggle'), s = document.getElementById('sidebar'), b = document.getElementById('sidebarBackdrop');
    if (t && s && b) {
        t.addEventListener('click', () => { s.classList.toggle('open'); b.classList.toggle('visible'); });
        b.addEventListener('click', () => { s.classList.remove('open'); b.classList.remove('visible'); });
    }
    const cart = {};
    const cartItemsEl = document.getElementById('cartItems');
    const cartTotalEl = document.getElementById('cartTotal');
    const emptyMsg = document.getElementById('cartEmptyMsg');
    document.querySelectorAll('.product-tile').forEach(tile => {
        tile.addEventListener('click', () => {
            const id = tile.dataset.id, name = tile.dataset.name, price = parseFloat(tile.dataset.price);
            if (!cart[id]) cart[id] = { name, price, qty: 0 };
            cart[id].qty += 1;
            renderCart();
        });
    });

   function renderCart() {
        const ids = Object.keys(cart);
        emptyMsg.style.display = ids.length ? 'none' : 'block';
        cartItemsEl.querySelectorAll('.cart-item').forEach(el => el.remove());
        let total = 0;
        ids.forEach(id => {
            const item = cart[id];
            const lineTotal = item.price * item.qty;
            total += lineTotal;
            const row = document.createElement('div');
            row.className = 'cart-item';
            row.innerHTML = `<span>${item.name} x${item.qty}</span><span>₱${lineTotal.toFixed(2)}</span>`;
            cartItemsEl.appendChild(row);
        });
        cartTotalEl.textContent = '₱' + total.toFixed(2);
    }
    document.getElementById('checkoutBtn').addEventListener('click', () => {
        if (!Object.keys(cart).length) { alert('Cart is empty.'); return; }
        alert('Checkout UI only — will be connected to MySQL later.');
    });
});
</script>
</body>
</html>

