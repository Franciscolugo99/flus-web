document.documentElement.classList.add('js');

const toggle = document.querySelector('.nav-toggle');
const navPanel = document.querySelector('.nav-panel');
const nav = document.querySelector('.nav');
const body = document.body;
const header = document.getElementById('site-header');

function closeNav() {
  body.classList.remove('nav-open');
  if (toggle) {
    toggle.setAttribute('aria-expanded', 'false');
  }
}

if (toggle && navPanel) {
  toggle.addEventListener('click', (event) => {
    event.stopPropagation();
    const isOpen = body.classList.toggle('nav-open');
    toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  });

  if (nav) {
    nav.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', () => {
        closeNav();
      });
    });
  }

  document.addEventListener('click', (event) => {
    if (!body.classList.contains('nav-open')) {
      return;
    }

    const clickedInsideNav = navPanel.contains(event.target);
    const clickedToggle = toggle.contains(event.target);

    if (!clickedInsideNav && !clickedToggle) {
      closeNav();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeNav();
    }
  });
}

if (header) {
  const onScroll = () => {
    header.classList.toggle('scrolled', window.scrollY > 16);
  };

  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
}
