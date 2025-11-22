/* ===== BIG-CART PAGE GUARD: skip toàn bộ mini-cart trên trang giỏ hàng lớn ===== */
(function () {
  // Nhận diện trang giỏ hàng lớn bằng flag hoặc path
  const IS_CART_PAGE =
    (typeof window !== 'undefined' && window.IS_BIG_CART_PAGE === true) ||
    (/\/pages\/giohang\.php($|\?)/.test(location.pathname));

  // Ghim biến toàn cục để các đoạn code phía dưới có thể kiểm tra
  window.__DISABLE_MINI_CART__ = !!IS_CART_PAGE;

  if (!IS_CART_PAGE) return;

  // Ẩn UI mini-cart nếu header đã render
  document.addEventListener('DOMContentLoaded', function () {
    ['#mini-cart', '.mini-cart', '.cart-mini', '.header-cart-popover'].forEach(sel => {
      document.querySelectorAll(sel).forEach(el => {
        el.style.display = 'none';
        el.setAttribute('aria-hidden', 'true');
      });
    });
    // Vô hiệu hóa trigger/icon giỏ
    document
      .querySelectorAll('[data-mini-cart-trigger], .js-mini-cart-trigger, .header [data-cart], .header .cart, .header .cart-icon')
      .forEach(t => {
        t.style.pointerEvents = 'none';
        t.style.cursor = 'default';
        t.setAttribute('aria-disabled', 'true');
      });
  });

  // Ghi đè các API điều khiển mini-cart để không làm gì cả
  window.viewCart = function () {};
  window.openMiniCart = function () {};
  window.closeMiniCart = function () {};
})();

// static/js/cart-mini.js
(function () {
  if (window.__DISABLE_MINI_CART__) return;

  const BASE_URL = "<?= $base_url ?>";

  const $mini   = document.getElementById("miniCart");
  const $list   = document.getElementById("miniCartList");
  const $total  = document.getElementById("miniCartTotal");
  const $count  = document.getElementById("cartCount");
  const $close  = $mini ? $mini.querySelector(".mini-cart__close") : null;
  const $trigger= document.getElementById("jsCartTrigger");
  const emitHeaderDropdown = () => document.dispatchEvent(new CustomEvent('header-cart:open'));

  function money(n){ try{ return (n||0).toLocaleString('vi-VN') + ' đ'; }catch{ return (n||0)+' đ'; } }

  async function api(method, url, body){
    const opt = { method, credentials: 'same-origin' };
    if (body && method !== 'GET'){
      opt.headers = {'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'};
      opt.body = body;
    }
    // fallback GET when server expects query only
    try { return await fetch(url, opt).then(r => r.json()); }
    catch { return { items: [], count: 0, sum: 0 }; }
  }

  async function viewCart(){
    if (window.__DISABLE_MINI_CART__) return;
    const js = await api('GET', `${BASE_URL}/cart.php?action=view`);
    const items = js.items || [];
    $count && ($count.textContent = js.count ?? items.length ?? 0);
    $total && ($total.textContent = money(js.sum ?? items.reduce((s,i)=>s+(+i.final_price||0)*(+i.qty||1),0)));
    if (!items.length){
      $list.innerHTML = `<div class="mini-cart__empty">Giỏ hàng trống</div>`;
      return;
    }
    $list.innerHTML = items.map(i => {
      const name = i.tensp || i.name || '';
      const qty  = i.qty  || i.so_luong || 1;
      const price= (+i.final_price || +i.giaban - (+i.giagiam||0) || +i.giaban || 0);
      const id   = i.masp || i.id;
      const img  = i.hinhsp || i.img || i.image || '';
      return `
        <div class="mini-cart__item">
          <img class="mini-cart__thumb" src="${img}" alt="">
          <div class="mini-cart__name">
            <a href="${BASE_URL}/base.php?page=detailsproducts&masp=${id}">${name}</a>
            <div class="mini-cart__qty">SL: ${qty}</div>
          </div>
          <div class="mini-cart__price">
            <div>${money(price)}</div>
            <button class="mini-cart__remove" data-remove-masp="${id}" title="Xóa">🗑</button>
          </div>
        </div>`;
    }).join('');
  }

  async function addToCart(masp, qty){
    if (window.__DISABLE_MINI_CART__) return;
    // POST, fallback GET handled server-side
    await api('POST', `${BASE_URL}/cart.php?action=add`, `masp=${encodeURIComponent(masp)}&qty=${encodeURIComponent(qty||1)}`);
    if (!window.__DISABLE_MINI_CART__) {
      await viewCart();
      showMini();
    }
  }

  function showMini(){
    if (window.__DISABLE_MINI_CART__) return;
    if (!$mini) {
      emitHeaderDropdown();
      return;
    }
    $mini.hidden = false;
    $mini.classList.add('show');
    clearTimeout(showMini._t);
    showMini._t = setTimeout(hideMini, 4000);
  }
  function hideMini(){
    if (window.__DISABLE_MINI_CART__) return;
    if (!$mini) return;
    $mini.classList.remove('show');
    $mini.hidden = true;
  }

  // Global click handler: add-to-cart buttons
  if (!window.__DISABLE_MINI_CART__) {
    document.addEventListener('click', (e) => {
      const addBtn = e.target.closest('.js-add-to-cart');
      if (addBtn){
        e.preventDefault();
        const masp = addBtn.dataset.masp;
        const qty  = addBtn.dataset.qty || 1;
        if (masp) addToCart(masp, qty);
        return;
      }
      const rm = e.target.closest('[data-remove-masp]');
      if (rm){
        e.preventDefault();
        const id = rm.dataset.removeMasp;
        api('POST', `${BASE_URL}/cart.php?action=remove`, `masp=${encodeURIComponent(id)}`).then(() => {
          if (!window.__DISABLE_MINI_CART__) viewCart();
        });
        return;
      }
      if (e.target.closest('#cartBtn')){
        e.preventDefault();
        if (!window.__DISABLE_MINI_CART__) viewCart().then(showMini);
        return;
      }
      if (!$mini.contains(e.target) && !e.target.closest('#jsCartTrigger')) hideMini();
    });

    $close && $close.addEventListener('click', (e)=>{ e.preventDefault(); hideMini(); });
  }

  // Load count at start
  if (!window.__DISABLE_MINI_CART__ && $mini) viewCart();
})();
