(() => {
  const endpoint = window.FLUS_ANALYTICS_ENDPOINT || '/track.php';
  const allowedEvents = new Set([
    'page_view',
    // Eventos canonicos del sitio comercial.
    'click_whatsapp',
    'demo_click',
    'contact_submit',
    'pricing_view',
    'cta_primary_click',
    'click_contact',
    'click_resource',
    'click_download',
    'lead_form_success',
    'finder_result',
    'view_resource_article',
    'view_industry_landing',
  ]);

  // Nombres historicos -> nombres canonicos. Permite migrar el marcado sin
  // perder datos ya recogidos ni duplicar eventos.
  const eventAliases = {
    click_demo: 'demo_click',
  };

  function canonicalEvent(name) {
    return eventAliases[name] || name;
  }

  const localHosts = new Set(['localhost', '127.0.0.1', '::1']);
  if (localHosts.has(window.location.hostname) || window.location.hostname.endsWith('.local')) {
    return;
  }

  // La analitica es no esencial: sin consentimiento explicito no se envia ni
  // se escribe nada. consent.js carga este archivo solo despues de aceptar,
  // pero el guard se mantiene por defensa en profundidad.
  function consentGranted() {
    return window.FLUSAnalyticsAllowed === true;
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
      const existing = window.sessionStorage.getItem(storageKey);
      if (existing) {
        return existing;
      }

      const generated = buildSessionId();
      window.sessionStorage.setItem(storageKey, generated);
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

  function getFirstTouch() {
    const key = 'flus_analytics_first_touch';
    try {
      const existing = JSON.parse(window.sessionStorage.getItem(key) || 'null');
      if (existing && typeof existing === 'object') return existing;
      const params = new URLSearchParams(window.location.search);
      const data = {
        landing_page: safeText(window.location.pathname, 190) || '/',
        referrer: safeText(document.referrer, 255),
        utm_source: safeText(params.get('utm_source') || '', 100),
        utm_medium: safeText(params.get('utm_medium') || '', 100),
        utm_campaign: safeText(params.get('utm_campaign') || '', 100),
      };
      window.sessionStorage.setItem(key, JSON.stringify(data));
      return data;
    } catch (error) {
      return {landing_page: safeText(window.location.pathname, 190) || '/', referrer: safeText(document.referrer,255), utm_source:'',utm_medium:'',utm_campaign:''};
    }
  }

  function normalizeHref(href) {
    return safeText(href || '', 255);
  }

  function sendToGa4(eventType, extra = {}) {
    if (typeof window.gtag !== 'function' || eventType === 'page_view') {
      return;
    }

    const params = {
      event_category: 'flus_commercial',
      event_label: safeText(extra.label || '', 120),
      page_path: safeText(window.location.pathname, 190) || '/',
      transport_type: 'beacon',
    };

    if (extra.href) {
      params.link_url = normalizeHref(extra.href);
    }
    if (extra.form_id) {
      params.form_id = safeText(extra.form_id, 80);
    }
    if (extra.result) {
      params.result = safeText(extra.result, 80);
    }

    // generate_lead is a recommended GA4 event and represents a real form lead.
    const gaEvent = eventType === 'lead_form_success' ? 'generate_lead' : eventType;
    try {
      window.gtag('event', gaEvent, params);
    } catch (error) {
      // Analytics must never break navigation or forms.
    }
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
    if (!consentGranted()) {
      return;
    }

    const canonical = canonicalEvent(eventType);
    if (!allowedEvents.has(canonical)) {
      return;
    }

    const firstTouch = getFirstTouch();
    const payload = {
      event_type: canonical,
      page_url: safeText(window.location.pathname + window.location.search, 255) || '/',
      page_title: safeText(document.title, 190),
      referrer: firstTouch.referrer || safeText(document.referrer, 255),
      utm_source: safeText(getUtmValue('utm_source') || firstTouch.utm_source, 100),
      utm_medium: safeText(getUtmValue('utm_medium') || firstTouch.utm_medium, 100),
      utm_campaign: safeText(getUtmValue('utm_campaign') || firstTouch.utm_campaign, 100),
      session_id: getSessionId(),
      extra: Object.assign({ landing_page: firstTouch.landing_page || '/' }, extra),
    };

    sendToGa4(canonical, extra);
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

  function firePageView() {
    if (window.__flusAnalyticsPageTracked) {
      return;
    }
    window.__flusAnalyticsPageTracked = true;
    track('page_view');
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', firePageView, { once: true });
  } else {
    firePageView();
  }

  // pricing_view: se dispara una sola vez cuando la seccion de precios entra
  // en pantalla, para medir interes comercial real (no solo el pageview).
  (function observePricing() {
    const pricing = document.getElementById('precios');
    if (!pricing || typeof IntersectionObserver !== 'function') {
      return;
    }

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) {
          return;
        }
        observer.disconnect();
        track('pricing_view', {
          label: safeText(pricing.querySelector('h2')?.textContent || 'Precios', 120),
        });
      });
    }, { threshold: 0.35 });

    observer.observe(pricing);
  })();

  document.addEventListener('click', (event) => {
    const target = event.target.closest('[data-track-event], a[href]');
    if (!target) {
      return;
    }

    const eventType = autoEventFromElement(target);
    if (!allowedEvents.has(canonicalEvent(eventType))) {
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
    if (!allowedEvents.has(canonicalEvent(eventType))) {
      return;
    }

    track(eventType, {
      form_id: safeText(form.getAttribute('id') || '', 80),
      form_name: safeText(form.getAttribute('name') || '', 80),
      action: normalizeHref(form.getAttribute('action') || ''),
    });
  });
})();
