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
    toggle.setAttribute('aria-label', 'Abrir menú');
  }

  document.querySelectorAll('[data-nav-dropdown].is-open').forEach((dropdown) => {
    dropdown.classList.remove('is-open');
    const trigger = dropdown.querySelector('.nav-dropdown__trigger');
    if (trigger) trigger.setAttribute('aria-expanded', 'false');
  });
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

/* ── Nav Dropdowns ───────────────────────── */
(function () {
  var dropdowns = Array.from(document.querySelectorAll('[data-nav-dropdown]'));
  if (!dropdowns.length) return;

  var isMobile = function () {
    return window.innerWidth <= 920;
  };

  function setOpen(dropdown, open) {
    var trigger = dropdown.querySelector('.nav-dropdown__trigger');
    dropdown.classList.toggle('is-open', open);
    if (trigger) trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  function closeAll(except) {
    dropdowns.forEach(function (item) {
      if (item !== except) setOpen(item, false);
    });
  }

  dropdowns.forEach(function (dropdown) {
    var trigger = dropdown.querySelector('.nav-dropdown__trigger');
    var menu = dropdown.querySelector('.nav-dropdown__menu');
    if (!trigger || !menu) return;

    var hoverTimeout;

    // Click funciona tanto en escritorio como en el menú móvil.
    trigger.addEventListener('click', function (event) {
      event.preventDefault();
      event.stopPropagation();

      var shouldOpen = !dropdown.classList.contains('is-open');
      closeAll(dropdown);
      setOpen(dropdown, shouldOpen);
    });

    // Hover solo en escritorio. Abrir uno cierra el otro.
    dropdown.addEventListener('mouseenter', function () {
      if (isMobile()) return;
      clearTimeout(hoverTimeout);
      closeAll(dropdown);
      setOpen(dropdown, true);
    });

    dropdown.addEventListener('mouseleave', function () {
      if (isMobile()) return;
      hoverTimeout = setTimeout(function () {
        setOpen(dropdown, false);
      }, 180);
    });

    menu.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        setOpen(dropdown, false);
        if (isMobile()) closeNav();
      });
    });
  });

  document.addEventListener('click', function (event) {
    var clickedDropdown = dropdowns.some(function (dropdown) {
      return dropdown.contains(event.target);
    });
    if (!clickedDropdown) closeAll();
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      closeAll();
    }
  });

  // Evita dejar un dropdown abierto al cambiar entre vista móvil/escritorio.
  var lastMobileState = isMobile();
  window.addEventListener('resize', function () {
    var mobileState = isMobile();
    if (mobileState !== lastMobileState) {
      closeAll();
      lastMobileState = mobileState;
    }
  }, { passive: true });
})();

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

/* ── Floating label helpers ───────────────── */
const floatFields = document.querySelectorAll('.form-field--float');
floatFields.forEach((field) => {
  const input = field.querySelector('.form-input');
  if (!input) return;

  const syncValue = () => {
    if (input.value.trim() !== '') {
      field.classList.add('has-value');
    } else {
      field.classList.remove('has-value');
    }
  };

  input.addEventListener('input', syncValue);
  input.addEventListener('change', syncValue);
  syncValue();
});

/* ── Blur (field-level) validation ───────── */
const validateField = (input) => {
  const rules = (input.getAttribute('data-validate') || '').split('|').filter(Boolean);
  if (!rules.length) return true;

  const field = input.closest('.form-field--float');
  const errorEl = field?.querySelector('.form-error');
  let error = '';

  for (const rule of rules) {
    if (rule === 'required' && input.value.trim() === '') {
      error = input.getAttribute('data-error-required') || 'Campo requerido.';
      break;
    }
    if (rule === 'email' && input.value.trim() !== '') {
      const ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value.trim());
      if (!ok) {
        error = input.getAttribute('data-error-email') || 'Correo inválido.';
        break;
      }
    }
  }

  if (error) {
    input.classList.add('is-invalid');
    field?.classList.add('has-error');
    if (errorEl) errorEl.textContent = error;
    return false;
  } else {
    input.classList.remove('is-invalid');
    field?.classList.remove('has-error');
    if (errorEl) errorEl.textContent = '';
    return true;
  }
};

document.querySelectorAll('[data-validate]').forEach((input) => {
  input.addEventListener('blur', () => validateField(input));
});

/* ── Character counter ────────────────────── */
document.querySelectorAll('[data-char-counter]').forEach((textarea) => {
  const field = textarea.closest('.form-field--float');
  const counter = field?.querySelector('.form-char-counter');
  const currentEl = counter?.querySelector('.form-char-counter__current');
  const maxAttr = counter ? parseInt(counter.getAttribute('data-max') || '3000', 10) : 3000;
  const warnAt = Math.floor(maxAttr * 0.8);
  const limitAt = Math.floor(maxAttr * 0.95);

  const update = () => {
    const len = textarea.value.length;
    if (currentEl) currentEl.textContent = len;
    if (!counter) return;
    counter.classList.toggle('is-warn', len >= warnAt && len < limitAt);
    counter.classList.toggle('is-limit', len >= limitAt);
  };

  textarea.addEventListener('input', update);
  update();
});

/* ── Contact form: loading state ─────────── */
const contactForms = document.querySelectorAll('[data-contact-form]');

contactForms.forEach((form) => {
  const submitButton = form.querySelector('[data-contact-submit]');
  const submitLabel = submitButton?.querySelector('.contact-submit__label');
  const formCard = form.closest('.contact-form-card');

  if (!submitButton || !submitLabel) return;

  form.addEventListener('submit', (event) => {
    // Run blur validation on all required fields before submitting
    let valid = true;
    form.querySelectorAll('[data-validate]').forEach((input) => {
      if (!validateField(input)) valid = false;
    });

    if (!valid) {
      event.preventDefault();
      const firstInvalid = form.querySelector('.is-invalid');
      firstInvalid?.focus();
      return;
    }

    submitButton.disabled = true;
    submitButton.classList.add('is-loading');
    form.classList.add('is-submitting');
    formCard?.classList.add('is-submitting');
    submitLabel.textContent = submitLabel.getAttribute('data-loading-label') || 'Enviando…';
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

  /* ── Nav arrows ── */
  const navContainer = marquee.closest('.modules-band')?.querySelector('[data-module-nav]');
  if (navContainer) {
    const cardWidth = () => {
      const card = group.querySelector('.module-card');
      if (!card) return 294;
      return card.getBoundingClientRect().width + 18; // card + gap
    };

    navContainer.querySelectorAll('.modules-nav__btn').forEach((btn) => {
      btn.addEventListener('click', () => {
        const dir = btn.getAttribute('data-dir');
        const jump = cardWidth() * 2;
        const target = dir === 'next' ? position - jump : position + jump;
        animateToPosition(target);
      });
    });
  }

  function animateToPosition(target) {
    const start = position;
    const diff = target - start;
    const duration = 420;
    const startTime = performance.now();
    isPausedByPointer = true;

    function step(now) {
      const elapsed = now - startTime;
      const progress = Math.min(elapsed / duration, 1);
      const ease = 1 - Math.pow(1 - progress, 3); // ease-out cubic
      position = start + diff * ease;
      normalizePosition();
      render();

      if (progress < 1) {
        requestAnimationFrame(step);
      } else {
        isPausedByPointer = false;
      }
    }

    requestAnimationFrame(step);
  }

  /* ── Progress dots ── */
  const dotsContainer = marquee.closest('.modules-band')?.querySelector('[data-module-dots]');
  const totalCards = group.querySelectorAll('.module-card').length;

  if (dotsContainer && totalCards > 0) {
    const dotCount = totalCards;
    const dots = [];

    for (let i = 0; i < dotCount; i++) {
      const dot = document.createElement('button');
      dot.className = 'modules-dots__dot';
      dot.setAttribute('aria-label', 'Ir al módulo ' + (i + 1));
      dot.addEventListener('click', () => {
        const cardW = (group.querySelector('.module-card')?.getBoundingClientRect().width || 276) + 18;
        animateToPosition(-cardW * i);
      });
      dotsContainer.appendChild(dot);
      dots.push(dot);
    }

    // Update dots on a separate interval (never touches the animation loop)
    const refreshDots = () => {
      if (!groupWidth) return;
      const cardW = (group.querySelector('.module-card')?.getBoundingClientRect().width || 276) + 18;
      const normalizedPos = (((-position % groupWidth) + groupWidth) % groupWidth);
      const activeIndex = Math.round(normalizedPos / cardW) % dotCount;
      dots.forEach((d, i) => {
        d.classList.toggle('is-active', i === activeIndex);
      });
    };

    setInterval(refreshDots, 250);
    refreshDots();
  }
});

/* ── Scroll reveal (IntersectionObserver) ── */
(function () {
  // Respect reduced motion preference
  const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (prefersReduced) return;

  const targets = document.querySelectorAll('[data-reveal]');
  if (!targets.length || !('IntersectionObserver' in window)) {
    // Fallback: just show everything
    targets.forEach((el) => el.classList.add('is-revealed'));
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-revealed');
          observer.unobserve(entry.target); // Fire once only
        }
      });
    },
    {
      rootMargin: '0px 0px -80px 0px', // Trigger 80px before fully in view
      threshold: 0.08,
    }
  );

  targets.forEach((el) => observer.observe(el));
})();

/* FLUS V4 — edition finder */
(function () {
  const finder = document.querySelector('[data-flus-finder]');
  if (!finder) return;

  const groups = Array.from(finder.querySelectorAll('[data-finder-group]'));
  const result = finder.querySelector('[data-finder-result]');
  const title = finder.querySelector('[data-finder-title]');
  const copy = finder.querySelector('[data-finder-copy]');
  const benefits = finder.querySelector('[data-finder-benefits]');
  const icon = finder.querySelector('.flus26-finder__resulticon');
  const primary = finder.querySelector('[data-finder-primary]');
  const secondary = finder.querySelector('[data-finder-secondary]');

  if (!result || !title || !copy || !benefits || !icon || !primary || !secondary) return;

  const webDemoUrl = primary.getAttribute('href');
  const whatsappUrl = secondary.getAttribute('href');

  function render() {
    let webScore = 0;
    let serverScore = 0;

    groups.forEach((group) => {
      const selected = group.querySelector('[data-finder-choice].is-selected');
      if (!selected) return;
      if (selected.dataset.finderChoice === 'server') serverScore += 1;
      else webScore += 1;
    });

    const isServer = serverScore > webScore;
    result.classList.toggle('is-server', isServer);
    result.classList.toggle('is-web', !isServer);

    if (isServer) {
      icon.textContent = 'S';
      title.textContent = 'FLUS Server';
      copy.textContent = 'Por lo que marcaste, tu operación parece necesitar una implementación más personalizada, con infraestructura propia, más puestos o integraciones específicas.';
      benefits.innerHTML = '<li>Implementación según puestos y operación</li><li>Infraestructura propia o del cliente</li><li>Cotización personalizada según alcance</li>';
      primary.textContent = 'Cotizar FLUS Server';
      primary.setAttribute('href', whatsappUrl);
      primary.setAttribute('target', '_blank');
      primary.setAttribute('rel', 'noopener');
      primary.setAttribute('data-track-event', 'click_whatsapp');
      primary.setAttribute('data-track-label', 'Quiz cotizar Server');
      secondary.textContent = 'Comparar con FLUS Web →';
      secondary.setAttribute('href', '#flus-web');
    } else {
      icon.textContent = 'W';
      title.textContent = 'FLUS Web';
      copy.textContent = 'Por lo que marcaste, te conviene empezar con la versión Web: rápida de implementar, administrada por FLUS y lista para usar desde distintos dispositivos.';
      benefits.innerHTML = '<li>Acceso desde navegador y PWA</li><li>Infraestructura administrada</li><li>Entrada mensual simple para empezar</li>';
      primary.textContent = 'Probar FLUS Web';
      primary.setAttribute('href', webDemoUrl);
      primary.setAttribute('target', '_blank');
      primary.setAttribute('rel', 'noopener');
      primary.setAttribute('data-track-event', 'cta_primary_click');
      primary.setAttribute('data-track-label', 'Quiz probar FLUS');
      secondary.textContent = 'Hablar con nosotros →';
      secondary.setAttribute('href', whatsappUrl);
    }
  }

  groups.forEach((group) => {
    const choices = Array.from(group.querySelectorAll('[data-finder-choice]'));
    choices.forEach((choice) => {
      choice.setAttribute('aria-pressed', choice.classList.contains('is-selected') ? 'true' : 'false');
      choice.addEventListener('click', () => {
        choices.forEach((button) => {
          const selected = button === choice;
          button.classList.toggle('is-selected', selected);
          button.setAttribute('aria-pressed', selected ? 'true' : 'false');
        });
        render();
        if (window.FLUSTrack && typeof window.FLUSTrack.track === 'function') {
          const serverScore = groups.filter((g) => g.querySelector('[data-finder-choice].is-selected')?.dataset.finderChoice === 'server').length;
          const webScore = groups.length - serverScore;
          window.FLUSTrack.track('finder_result', { recommended_flus: serverScore > webScore ? 'server' : 'web' });
        }
      });
    });
  });

  render();
})();

/* FLUS V5 — interactive product tour */
(function () {
  const tour = document.querySelector('[data-product-tour]');
  if (!tour) return;

  const tabs = Array.from(tour.querySelectorAll('[data-tour-target]'));
  const panels = Array.from(tour.querySelectorAll('[data-tour-panel]'));
  const windowTitle = tour.querySelector('[data-tour-window-title]');
  const kicker = tour.querySelector('[data-tour-kicker-output]');
  const caption = tour.querySelector('[data-tour-caption-output]');

  function activate(key) {
    let activePanel = null;

    tabs.forEach((tab) => {
      const active = tab.dataset.tourTarget === key;
      tab.classList.toggle('is-active', active);
      tab.setAttribute('aria-selected', active ? 'true' : 'false');
      tab.tabIndex = active ? 0 : -1;
    });

    panels.forEach((panel) => {
      const active = panel.dataset.tourPanel === key;
      panel.classList.toggle('is-active', active);
      panel.hidden = !active;
      if (active) activePanel = panel;
    });

    if (!activePanel) return;
    if (windowTitle) windowTitle.textContent = activePanel.dataset.tourTitle || 'FLUS';
    if (kicker) kicker.textContent = activePanel.dataset.tourKicker || '';
    if (caption) caption.textContent = activePanel.dataset.tourCaption || '';
  }

  tabs.forEach((tab, index) => {
    tab.addEventListener('click', () => activate(tab.dataset.tourTarget));
    tab.addEventListener('keydown', (event) => {
      if (!['ArrowRight', 'ArrowLeft', 'ArrowDown', 'ArrowUp'].includes(event.key)) return;
      event.preventDefault();
      const forward = event.key === 'ArrowRight' || event.key === 'ArrowDown';
      const nextIndex = (index + (forward ? 1 : -1) + tabs.length) % tabs.length;
      tabs[nextIndex].focus();
      activate(tabs[nextIndex].dataset.tourTarget);
    });
  });

  const initial = tabs.find((tab) => tab.classList.contains('is-active')) || tabs[0];
  if (initial) activate(initial.dataset.tourTarget);
})();
