/**
 * FLUS · Banner de consentimiento de cookies + carga condicionada de analitica.
 *
 * Sin dependencias. Debe cargarse temprano (en el <head>, sin defer) para que
 * GA4 no llegue a inyectarse cuando no hay consentimiento.
 *
 * Expone window.FLUSConsent con:
 *   state()          -> 'granted' | 'denied' | ''
 *   granted()        -> boolean
 *   accept()         -> guarda 'granted' y carga analitica
 *   reject()         -> guarda 'denied' y no carga nada
 *   reset()          -> borra la cookie y vuelve a mostrar el banner
 *   onChange(fn)     -> notifica cambios de estado
 *
 * Antes de que el usuario decida, window.FLUSAnalyticsAllowed es false, por lo
 * que site-analytics.js no envia ningun evento.
 */
(function () {
  'use strict';

  var CONFIG = window.FLUS_CONSENT_CONFIG || {};
  var COOKIE = 'flus_consent';
  var MAX_AGE = 60 * 60 * 24 * 180; // 180 dias
  var listeners = [];
  var loaded = { ga4: false, analytics: false };

  /* ── Persistencia ─────────────────────────────────────────── */

  function readCookie(name) {
    var parts = ('; ' + document.cookie).split('; ' + name + '=');
    return parts.length === 2 ? parts.pop().split(';').shift() : '';
  }

  function writeCookie(value) {
    var secure = location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = COOKIE + '=' + value +
      '; Max-Age=' + MAX_AGE +
      '; Path=/; SameSite=Lax' + secure;
  }

  function clearCookie() {
    document.cookie = COOKIE + '=; Max-Age=0; Path=/; SameSite=Lax';
  }

  function state() {
    var v = readCookie(COOKIE);
    return (v === 'granted' || v === 'denied') ? v : (CONFIG.state || '');
  }

  function granted() {
    return state() === 'granted';
  }

  /* ── Carga de analitica ───────────────────────────────────── */

  function loadGA4() {
    if (loaded.ga4 || !CONFIG.ga4) return;
    loaded.ga4 = true;

    var s = document.createElement('script');
    s.async = true;
    s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(CONFIG.ga4);
    document.head.appendChild(s);

    window.dataLayer = window.dataLayer || [];
    window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
    window.gtag('js', new Date());
    window.gtag('config', CONFIG.ga4, { anonymize_ip: true });
  }

  function loadAnalytics() {
    if (loaded.analytics) return;
    loaded.analytics = true;

    // Habilita el envio en site-analytics.js
    window.FLUSAnalyticsAllowed = true;
    window.FLUS_ANALYTICS_ENDPOINT = window.FLUS_ANALYTICS_ENDPOINT || CONFIG.analyticsEndpoint;

    var existing = document.querySelector('script[data-flus-analytics]');
    if (existing) return;

    var s = document.createElement('script');
    s.async = true;
    s.setAttribute('data-flus-analytics', '');
    s.src = (window.__flusAnalyticsSrc || '/assets/js/site-analytics.js');
    document.head.appendChild(s);
  }

  function enableAnalytics() {
    loadGA4();
    loadAnalytics();
  }

  function notify() {
    var st = state();
    for (var i = 0; i < listeners.length; i++) {
      try { listeners[i](st); } catch (e) { /* noop */ }
    }
  }

  /* ── API publica ──────────────────────────────────────────── */

  window.FLUSConsent = {
    state: state,
    granted: granted,
    onChange: function (fn) { if (typeof fn === 'function') listeners.push(fn); },
    accept: function () { writeCookie('granted'); enableAnalytics(); hide(); notify(); },
    reject: function () { writeCookie('denied'); hide(); notify(); },
    reset: function () { clearCookie(); show(); notify(); }
  };

  // Gate inicial: hasta que no haya consentimiento explicito, la analitica
  // propia no debe enviar nada.
  window.FLUSAnalyticsAllowed = granted();

  if (granted()) {
    enableAnalytics();
  }

  /* ── Banner ───────────────────────────────────────────────── */

  var banner = null;

  function buildBanner() {
    banner = document.createElement('div');
    banner.className = 'consent-banner';
    banner.setAttribute('role', 'dialog');
    banner.setAttribute('aria-modal', 'false');
    banner.setAttribute('aria-labelledby', 'consent-title');
    banner.setAttribute('aria-describedby', 'consent-desc');
    banner.innerHTML =
      '<div class="consent-banner__inner">' +
        '<div class="consent-banner__text">' +
          '<strong id="consent-title">Cookies y analítica</strong>' +
          '<p id="consent-desc">Usamos cookies propias y de Google Analytics para entender cómo se usa el sitio y mejorarlo. ' +
          'No son necesarias para navegar: podés aceptarlas o rechazarlas. ' +
          'Más info en la <a href="' + (CONFIG.cookiePolicyUrl || '/politica-de-cookies.php') + '">política de cookies</a>.</p>' +
        '</div>' +
        '<div class="consent-banner__actions">' +
          '<button type="button" class="btn btn-secondary consent-banner__reject">Rechazar no esenciales</button>' +
          '<button type="button" class="btn btn-primary consent-banner__accept">Aceptar</button>' +
        '</div>' +
      '</div>';
    document.body.appendChild(banner);

    banner.querySelector('.consent-banner__accept').addEventListener('click', function () {
      window.FLUSConsent.accept();
    });
    banner.querySelector('.consent-banner__reject').addEventListener('click', function () {
      window.FLUSConsent.reject();
    });

    // Foco al primer boton solo con teclado, para no robar el foco al cargar.
    banner.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') window.FLUSConsent.reject();
    });
  }

  /**
   * Publica el alto REAL del banner en --consent-banner-h.
   *
   * El boton flotante de WhatsApp es fixed abajo a la derecha y el banner lo
   * tapa mientras esta visible. El CSS usa esta variable para subirlo, asi no
   * hay que mantener un valor fijo (el banner cambia de alto en movil).
   */
  function syncBannerHeight() {
    if (!banner) return;
    var h = Math.ceil(banner.getBoundingClientRect().height);
    if (h > 0) {
      document.documentElement.style.setProperty('--consent-banner-h', h + 'px');
    }
  }

  function show() {
    if (!banner) buildBanner();
    // Un frame para que la transicion CSS se aplique.
    requestAnimationFrame(function () { banner.classList.add('is-visible'); });
    document.body.classList.add('has-consent-banner');
    syncBannerHeight();
    window.addEventListener('resize', syncBannerHeight);
  }

  function hide() {
    if (!banner) return;
    window.removeEventListener('resize', syncBannerHeight);
    banner.classList.remove('is-visible');
    document.body.classList.remove('has-consent-banner');
    document.documentElement.style.removeProperty('--consent-banner-h');
    window.setTimeout(function () {
      if (banner && banner.parentNode) banner.parentNode.removeChild(banner);
      banner = null;
    }, 260);
  }

  function init() {
    if (state() === '') show();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
