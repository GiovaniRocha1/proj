fetch('Defaults/Header.html')
  .then(response => {
    if (!response.ok) {
      throw new Error('Não foi possível carregar o cabeçalho.');
    }
    return response.text();
  })
  .then(html => {
    document.getElementById('header-placeholder').innerHTML = html;
    const searchInput = document.querySelector('.search input');
    if (searchInput && window.location.pathname.endsWith('/dashboard.php')) {
      searchInput.value = new URLSearchParams(window.location.search).get('q') || '';
    }

    function updateCartBadge() {
      fetch('cart_count.php', { cache: 'no-store' })
        .then(response => response.ok ? response.json() : Promise.reject(new Error('Não foi possível carregar a quantidade de itens no carrinho.')))
        .then(data => {
          const cartCount = document.querySelector('.cart-count');
          if (!cartCount) {
            return;
          }
          const count = Number(data.count) || 0;
          cartCount.textContent = String(count);
          const cartLink = cartCount.closest('.cart-link');
          if (cartLink) {
            cartLink.setAttribute(
              'aria-label',
              count === 1 ? '1 item no carrinho' : count + ' itens no carrinho'
            );
          }
        })
        .catch(error => console.error(error));
    }

    updateCartBadge();

    const accountMenu = document.querySelector('.account-menu');
    const accountToggle = document.querySelector('.account-toggle');

    if (accountMenu && accountToggle) {
      accountToggle.addEventListener('click', function (event) {
        event.stopPropagation();
        const isOpen = accountMenu.classList.toggle('open');
        accountToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      });

      document.addEventListener('click', function (event) {
        if (!accountMenu.contains(event.target)) {
          accountMenu.classList.remove('open');
          accountToggle.setAttribute('aria-expanded', 'false');
        }
      });

      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
          accountMenu.classList.remove('open');
          accountToggle.setAttribute('aria-expanded', 'false');
          accountToggle.focus();
        }
      });
    }
  })
  .catch(error => console.error(error));
