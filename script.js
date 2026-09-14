const userMenu = document.getElementById('userMenu');
const userMenuButton = document.getElementById('userMenuButton');

if (userMenu && userMenuButton) {
  userMenuButton.addEventListener('click', function (event) {
    event.stopPropagation();
    const isOpen = userMenu.classList.contains('open');
    userMenu.classList.toggle('open', !isOpen);
    userMenuButton.setAttribute('aria-expanded', String(!isOpen));
  });

  document.addEventListener('click', function (event) {
    if (!userMenu.contains(event.target)) {
      userMenu.classList.remove('open');
      userMenuButton.setAttribute('aria-expanded', 'false');
    }
  });
}
