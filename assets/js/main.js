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

const moduleMarquees = document.querySelectorAll('[data-module-marquee]');

moduleMarquees.forEach((marquee) => {
  const viewport = marquee.querySelector('.modules-ticker__viewport');
  const track = marquee.querySelector('.modules-ticker__track');
  const group = marquee.querySelector('.modules-ticker__group');
  const speed = Number(marquee.getAttribute('data-speed')) || 30;

  if (!viewport || !track || !group) {
    return;
  }

  const clone = group.cloneNode(true);
  clone.setAttribute('aria-hidden', 'true');
  clone.querySelectorAll('[tabindex]').forEach((item) => {
    item.setAttribute('tabindex', '-1');
  });
  track.appendChild(clone);

  let groupWidth = 0;
  let position = 0;
  let rafId = null;
  let lastTime = 0;
  let pointerId = null;
  let dragStartX = 0;
  let dragStartPosition = 0;
  let isDragging = false;
  let isPausedByPointer = false;

  const normalizePosition = () => {
    if (!groupWidth) {
      return;
    }

    while (position <= -groupWidth) {
      position += groupWidth;
    }

    while (position > 0) {
      position -= groupWidth;
    }
  };

  const render = () => {
    track.style.transform = `translate3d(${position}px, 0, 0)`;
  };

  const measure = () => {
    groupWidth = group.getBoundingClientRect().width;
    normalizePosition();
    render();
  };

  const shouldPause = () => (
    isPausedByPointer ||
    isDragging ||
    marquee.matches(':hover') ||
    marquee.contains(document.activeElement)
  );

  const tick = (time) => {
    if (!lastTime) {
      lastTime = time;
    }

    const delta = (time - lastTime) / 1000;
    lastTime = time;

    if (!shouldPause() && groupWidth > 0) {
      position -= speed * delta;
      normalizePosition();
      render();
    }

    rafId = window.requestAnimationFrame(tick);
  };

  const onPointerDown = (event) => {
    if (event.pointerType === 'mouse' && event.button !== 0) {
      return;
    }

    isDragging = true;
    isPausedByPointer = true;
    pointerId = event.pointerId;
    dragStartX = event.clientX;
    dragStartPosition = position;
    viewport.classList.add('is-dragging');
    viewport.setPointerCapture(pointerId);
  };

  const onPointerMove = (event) => {
    if (!isDragging || event.pointerId !== pointerId) {
      return;
    }

    position = dragStartPosition + (event.clientX - dragStartX);
    normalizePosition();
    render();
  };

  const onPointerUp = (event) => {
    if (event.pointerId !== pointerId) {
      return;
    }

    isDragging = false;
    isPausedByPointer = false;
    viewport.classList.remove('is-dragging');

    if (viewport.hasPointerCapture(pointerId)) {
      viewport.releasePointerCapture(pointerId);
    }

    pointerId = null;
  };

  viewport.addEventListener('pointerdown', onPointerDown);
  viewport.addEventListener('pointermove', onPointerMove);
  viewport.addEventListener('pointerup', onPointerUp);
  viewport.addEventListener('pointercancel', onPointerUp);
  viewport.addEventListener('mouseleave', () => {
    if (isDragging) {
      isDragging = false;
      isPausedByPointer = false;
      viewport.classList.remove('is-dragging');
      pointerId = null;
    }
  });

  marquee.addEventListener('focusin', () => {
    render();
  });

  marquee.addEventListener('focusout', () => {
    window.requestAnimationFrame(render);
  });

  window.addEventListener('resize', measure, { passive: true });

  measure();
  render();
  rafId = window.requestAnimationFrame(tick);
});

