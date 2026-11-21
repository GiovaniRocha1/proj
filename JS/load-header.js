fetch('Defaults/Header.html')
  .then(response => {
    if (!response.ok) {
      throw new Error('Failed to load header');
    }
    return response.text();
  })
  .then(html => {
    document.getElementById('header-placeholder').innerHTML = html;

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
