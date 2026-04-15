(() => {
  const endpoint = window.FLUS_ANALYTICS_ENDPOINT || '/track.php';
  const allowedEvents = new Set([
    'page_view',
    'click_whatsapp',
    'click_contact',
    'click_demo',
    'click_download',
  ]);

  const localHosts = new Set(['localhost', '127.0.0.1', '::1']);
  if (localHosts.has(window.location.hostname) || window.location.hostname.endsWith('.local')) {
    return;
  }

  function safeText(value, maxLength = 190) {
    return String(value || '').replace(/\s+/g, ' ').trim().slice(0, maxLength);
  }

  function buildSessionId() {
    const randomPart = Math.random().toString(36).slice(2, 10);
    return `ws_${Date.now().toString(36)}_${randomPart}`;
  }

  function getSessionId() {
    const storageKey = 'flus_analytics_session_id';

    try {
      const existing = window.localStorage.getItem(storageKey);
      if (existing) {
        return existing;
      }

      const generated = buildSessionId();
      window.localStorage.setItem(storageKey, generated);
      return generated;
    } catch (error) {
      if (!window.__flusAnalyticsMemoryId) {
        window.__flusAnalyticsMemoryId = buildSessionId();
      }
      return window.__flusAnalyticsMemoryId;
    }
  }

  function getUtmValue(name) {
    try {
      return new URLSearchParams(window.location.search).get(name) || '';
    } catch (error) {
      return '';
    }
  }

  function normalizeHref(href) {
    return safeText(href || '', 255);
  }

  function sendPayload(payload) {
    const body = JSON.stringify(payload);

    try {
      if (navigator.sendBeacon) {
        const blob = new Blob([body], { type: 'application/json' });
        if (navigator.sendBeacon(endpoint, blob)) {
          return;
        }
      }
    } catch (error) {
      // fallback to fetch
    }

    try {
      fetch(endpoint, {
        method: 'POST',
        credentials: 'same-origin',
        keepalive: true,
        headers: {
          'Content-Type': 'application/json',
        },
        body,
      }).catch(() => {});
    } catch (error) {
      // no-op
    }
  }

  function track(eventType, extra = {}) {
    if (!allowedEvents.has(eventType)) {
      return;
    }

    const payload = {
      event_type: eventType,
      page_url: safeText(window.location.pathname + window.location.search, 255) || '/',
      page_title: safeText(document.title, 190),
      referrer: safeText(document.referrer, 255),
      utm_source: safeText(getUtmValue('utm_source'), 100),
      utm_medium: safeText(getUtmValue('utm_medium'), 100),
      utm_campaign: safeText(getUtmValue('utm_campaign'), 100),
      session_id: getSessionId(),
      extra,
    };

    sendPayload(payload);
  }

  function autoEventFromElement(element) {
    const explicitEvent = element.getAttribute('data-track-event');
    if (explicitEvent) {
      return explicitEvent;
    }

    if (element.tagName !== 'A') {
      return '';
    }

    const href = element.getAttribute('href') || '';
    if (/wa\.me\//i.test(href)) {
      return 'click_whatsapp';
    }

    if (/^mailto:|^tel:/i.test(href) || /contacto\.php/i.test(href)) {
      return 'click_contact';
    }

    if (
      element.hasAttribute('download') ||
      /\/downloads?\b/i.test(href) ||
      /\.(pdf|zip|rar|7z|exe|msi|dmg)(\?|#|$)/i.test(href)
    ) {
      return 'click_download';
    }

    return '';
  }

  window.FLUSTrack = {
    track,
  };

  if (!window.__flusAnalyticsPageTracked) {
    window.__flusAnalyticsPageTracked = true;
    track('page_view');
  }

  document.addEventListener('click', (event) => {
    const target = event.target.closest('[data-track-event], a[href]');
    if (!target) {
      return;
    }

    const eventType = autoEventFromElement(target);
    if (!allowedEvents.has(eventType)) {
      return;
    }

    track(eventType, {
      href: normalizeHref(target.href || target.getAttribute('href') || ''),
      label: safeText(target.getAttribute('data-track-label') || target.textContent || '', 120),
      download: eventType === 'click_download',
    });
  }, { passive: true });

  document.addEventListener('submit', (event) => {
    if (event.defaultPrevented) {
      return;
    }

    const form = event.target.closest('form[data-track-event]');
    if (!form) {
      return;
    }

    const eventType = form.getAttribute('data-track-event') || '';
    if (!allowedEvents.has(eventType)) {
      return;
    }

    track(eventType, {
      form_id: safeText(form.getAttribute('id') || '', 80),
      form_name: safeText(form.getAttribute('name') || '', 80),
      action: normalizeHref(form.getAttribute('action') || ''),
    });
  });
})();
