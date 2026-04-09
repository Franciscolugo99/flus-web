document.documentElement.classList.add('js');

const toggle = document.querySelector('.nav-toggle');
const body = document.body;

if (toggle) {
  toggle.addEventListener('click', () => {
    const isOpen = body.classList.toggle('nav-open');
    toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      body.classList.remove('nav-open');
      toggle.setAttribute('aria-expanded', 'false');
    }
  });
}
