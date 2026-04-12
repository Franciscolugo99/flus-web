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
    toggle.setAttribute('aria-label', isOpen ? 'Cerrar menú' : 'Abrir menú');
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

/* ── WhatsApp FAB — aparece al bajar 300px ─ */
const fab = document.getElementById('whatsapp-fab');

if (fab) {
  const onFabScroll = () => {
    fab.classList.toggle('is-visible', window.scrollY > 300);
  };
  window.addEventListener('scroll', onFabScroll, { passive: true });
  onFabScroll();
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
  const brandMarkSrc = document.querySelector('.brand-mark img, .footer-brand-lockup img')?.getAttribute('src') || '';
  const contourHeadWidth = 7;
  const contourHeadHeight = 9;
  const intervalMs = Number(carousel.getAttribute('data-interval')) || 5000;
  const transitionMs = 560;
  let current = 0;
  let rafId = null;
  let startedAt = 0;
  let progress = 0;
  let leavingTimer = null;
  let popTimer = null;

  if (!tabs.length || !slides.length || tabs.length !== slides.length) {
    return;
  }

  slides.forEach((slide, idx) => {
    slide.hidden = false;
    slide.classList.toggle('is-active', idx === 0);
    slide.classList.remove('is-leaving');
    slide.setAttribute('aria-hidden', idx === 0 ? 'false' : 'true');
  });

  const ensureContourUi = (tab) => {
    let label = tab.querySelector('.story-dot__label');
    if (!label) {
      label = document.createElement('span');
      label.className = 'story-dot__label';

      while (tab.firstChild) {
        label.appendChild(tab.firstChild);
      }

      tab.appendChild(label);
    }

    let contour = tab.querySelector('.story-dot__contour');
    if (!contour) {
      contour = document.createElement('span');
      contour.className = 'story-dot__contour';
      contour.setAttribute('aria-hidden', 'true');
      contour.innerHTML = [
        '<svg focusable="false" aria-hidden="true">',
        '  <path class="story-dot__contour-value"></path>',
        `  <image class="story-dot__contour-head-image" width="${contourHeadWidth}" height="${contourHeadHeight}" preserveAspectRatio="xMidYMid meet"></image>`,
        '</svg>',
      ].join('');
      tab.appendChild(contour);
    }

    const head = contour.querySelector('.story-dot__contour-head-image');
    if (head && brandMarkSrc) {
      head.setAttribute('href', brandMarkSrc);
    }

    return {
      svg: contour.querySelector('svg'),
      value: contour.querySelector('.story-dot__contour-value'),
      head,
    };
  };

  const buildContours = () => {
    tabs.forEach((tab) => {
      const ui = ensureContourUi(tab);
      const width = Math.max(tab.clientWidth, 1);
      const height = Math.max(tab.clientHeight, 1);
      const strokeWidth = 2.15;
      const inset = 2 + (strokeWidth / 2);
      const radius = Math.max(((height - (inset * 2)) / 2), 0);
      const left = inset;
      const right = width - inset;
      const top = inset;
      const bottom = height - inset;
      const midX = width / 2;
      const pathData = [
        `M ${midX} ${top}`,
        `H ${right - radius}`,
        `A ${radius} ${radius} 0 0 1 ${right} ${top + radius}`,
        `V ${bottom - radius}`,
        `A ${radius} ${radius} 0 0 1 ${right - radius} ${bottom}`,
        `H ${left + radius}`,
        `A ${radius} ${radius} 0 0 1 ${left} ${bottom - radius}`,
        `V ${top + radius}`,
        `A ${radius} ${radius} 0 0 1 ${left + radius} ${top}`,
        `H ${midX}`,
      ].join(' ');

      ui.svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
      ui.value.setAttribute('d', pathData);

      const totalLength = ui.value.getTotalLength();
      ui.value.dataset.length = String(totalLength);
      ui.value.style.strokeDasharray = `0 ${totalLength}`;
      ui.head.setAttribute('x', String(midX - (contourHeadWidth / 2)));
      ui.head.setAttribute('y', String(top - (contourHeadHeight * 0.55)));
      ui.head.style.opacity = '0';
    });
  };

  const setProgress = (value) => {
    progress = Math.max(0, Math.min(100, value));

    tabs.forEach((tab, idx) => {
      const valuePath = tab.querySelector('.story-dot__contour-value');
      const head = tab.querySelector('.story-dot__contour-head-image');

      if (!valuePath || !head) {
        return;
      }

      const totalLength = Number(valuePath.dataset.length || valuePath.getTotalLength());

      if (idx === current) {
        const drawLength = totalLength * (progress / 100);
        const point = valuePath.getPointAtLength(Math.min(drawLength, totalLength));

        valuePath.style.strokeDasharray = `${drawLength} ${totalLength}`;
        head.setAttribute('x', String(point.x - (contourHeadWidth / 2)));
        head.setAttribute('y', String(point.y - (contourHeadHeight * 0.55)));
        head.style.opacity = progress > 0.5 ? '1' : '0';
      } else {
        valuePath.style.strokeDasharray = `0 ${totalLength}`;
        head.style.opacity = '0';
      }
    });
  };

  const goTo = (index) => {
    const previous = current;
    current = (index + slides.length) % slides.length;
    startedAt = 0;
    progress = 0;

    tabs.forEach((tab, idx) => {
      const active = idx === current;

      tab.classList.toggle('is-active', active);
      tab.setAttribute('aria-selected', active ? 'true' : 'false');
      tab.setAttribute('tabindex', active ? '0' : '-1');
    });

    if (popTimer) {
      window.clearTimeout(popTimer);
      popTimer = null;
    }

    tabs.forEach((tab) => {
      tab.classList.remove('is-popping');
    });

    tabs[current].classList.add('is-popping');
    popTimer = window.setTimeout(() => {
      tabs.forEach((tab) => {
        tab.classList.remove('is-popping');
      });
      popTimer = null;
    }, 520);

    if (leavingTimer) {
      window.clearTimeout(leavingTimer);
      leavingTimer = null;
    }

    slides.forEach((slide, idx) => {
      const active = idx === current;
      const wasActive = idx === previous;

      slide.classList.toggle('is-active', active);
      slide.classList.toggle('is-leaving', wasActive && !active);
      slide.setAttribute('aria-hidden', active ? 'false' : 'true');
    });

    if (previous !== current) {
      leavingTimer = window.setTimeout(() => {
        slides.forEach((slide, idx) => {
          if (idx !== current) {
            slide.classList.remove('is-leaving');
          }
        });
        leavingTimer = null;
      }, transitionMs);
    }

    setProgress(0);
  };

  const tick = (timestamp) => {
    if (!startedAt) {
      startedAt = timestamp;
    }

    const elapsed = timestamp - startedAt;
    setProgress((elapsed / intervalMs) * 100);

    if (elapsed >= intervalMs) {
      goTo(current + 1);
    }

    rafId = window.requestAnimationFrame(tick);
  };

  const start = () => {
    if (rafId) {
      window.cancelAnimationFrame(rafId);
    }

    rafId = window.requestAnimationFrame(tick);
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

  buildContours();
  window.addEventListener('resize', () => {
    buildContours();
    setProgress(progress);
  }, { passive: true });
  goTo(0);
  start();
});

const contactForms = document.querySelectorAll('[data-contact-form]');

contactForms.forEach((form) => {
  const submitButton = form.querySelector('[data-contact-submit]');
  const submitLabel = submitButton?.querySelector('.contact-submit__label');
  const formCard = form.closest('.contact-form-card');

  if (!submitButton || !submitLabel) {
    return;
  }

  form.addEventListener('submit', () => {
    submitButton.disabled = true;
    submitButton.classList.add('is-loading');
    form.classList.add('is-submitting');
    formCard?.classList.add('is-submitting');
    submitLabel.textContent = submitLabel.getAttribute('data-loading-label') || 'Enviando...';
  });
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
