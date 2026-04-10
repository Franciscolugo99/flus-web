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


const carousels = document.querySelectorAll('[data-carousel]');

carousels.forEach((carousel) => {
  const tabs = Array.from(carousel.querySelectorAll('[data-slide]'));
  const slides = Array.from(carousel.querySelectorAll('.story-slide'));
  const intervalMs = Number(carousel.getAttribute('data-interval')) || 5000;
  let current = 0;
  let timer = null;

  if (!tabs.length || !slides.length || tabs.length !== slides.length) {
    return;
  }

  const goTo = (index) => {
    current = (index + slides.length) % slides.length;

    tabs.forEach((tab, idx) => {
      const active = idx === current;
      tab.classList.toggle('is-active', active);
      tab.setAttribute('aria-selected', active ? 'true' : 'false');
      tab.setAttribute('tabindex', active ? '0' : '-1');
      slides[idx].classList.toggle('is-active', active);
      slides[idx].hidden = !active;
    });
  };

  const start = () => {
    stop();
    timer = window.setInterval(() => {
      goTo(current + 1);
    }, intervalMs);
  };

  const stop = () => {
    if (timer) {
      window.clearInterval(timer);
      timer = null;
    }
  };

  tabs.forEach((tab, idx) => {
    tab.addEventListener('click', () => {
      goTo(idx);
      start();
    });

    tab.addEventListener('keydown', (event) => {
      if (event.key === 'ArrowRight') {
        event.preventDefault();
        tabs[(idx + 1) % tabs.length].focus();
        goTo(idx + 1);
        start();
      }
      if (event.key === 'ArrowLeft') {
        event.preventDefault();
        tabs[(idx - 1 + tabs.length) % tabs.length].focus();
        goTo(idx - 1);
        start();
      }
    });
  });

  carousel.addEventListener('mouseenter', stop);
  carousel.addEventListener('mouseleave', start);
  carousel.addEventListener('focusin', stop);
  carousel.addEventListener('focusout', () => {
    if (!carousel.contains(document.activeElement)) {
      start();
    }
  });

  goTo(0);
  start();
});
