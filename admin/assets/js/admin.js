/* ============================================================
   FLUS Admin — admin.js v2.0
   Vanilla JS, sin dependencias externas
   ============================================================ */

'use strict';

// ============================================================
// CONFIRM DIALOGS
// ============================================================
document.addEventListener('click', function (e) {
  const btn = e.target.closest('[data-confirm]');
  if (!btn) return;
  const msg = btn.dataset.confirm || '¿Estás seguro de esta acción?';
  if (!confirm(msg)) e.preventDefault();
});

// ============================================================
// AUTO-DISMISS ALERTS (5 seg)
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.alert').forEach(function (el) {
    setTimeout(function () {
      el.style.transition = 'opacity .5s ease';
      el.style.opacity = '0';
      setTimeout(function () { el.remove(); }, 500);
    }, 5000);
  });
});

// ============================================================
// COPY LICENSE KEY — con toast feedback
// ============================================================
document.addEventListener('click', function (e) {
  const btn = e.target.closest('[data-copy]');
  if (!btn) return;
  const text = btn.dataset.copy;

  if (!navigator.clipboard) {
    // Fallback para HTTP
    const ta = document.createElement('textarea');
    ta.value = text;
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    ta.remove();
    showCopyToast('✓ Copiado al portapapeles');
    return;
  }

  navigator.clipboard.writeText(text).then(function () {
    const orig = btn.textContent;
    btn.textContent = '✓ OK';
    btn.style.color = '#00c896';
    setTimeout(function () {
      btn.textContent = orig;
      btn.style.color = '';
    }, 1500);
    showCopyToast('✓ Licencia copiada al portapapeles');
  }).catch(function () {
    showCopyToast('Error al copiar');
  });
});

function showCopyToast(msg) {
  const existing = document.querySelector('.copy-toast');
  if (existing) existing.remove();

  const toast = document.createElement('div');
  toast.className = 'copy-toast';
  toast.textContent = msg;
  document.body.appendChild(toast);

  setTimeout(function () {
    toast.style.transition = 'opacity .4s ease';
    toast.style.opacity = '0';
    setTimeout(function () { toast.remove(); }, 400);
  }, 1800);
}

// ============================================================
// TOPBAR DATE
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
  const el = document.getElementById('topbar-date');
  if (!el) return;
  const now = new Date();
  const opts = { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' };
  el.textContent = now.toLocaleDateString('es-AR', opts);
});

// ============================================================
// HIGHLIGHT ACTIVE ROW ON TABLES (hover highlight persists on focus)
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('tbody tr').forEach(function(row) {
    row.addEventListener('click', function(e) {
      // Only if clicking the row itself (not buttons inside)
      if (e.target.closest('a, button, input, select')) return;
      // Toggle highlight
      const was = row.classList.contains('row-selected');
      document.querySelectorAll('tbody tr.row-selected').forEach(r => r.classList.remove('row-selected'));
      if (!was) row.classList.add('row-selected');
    });
  });
});

// ============================================================
// SEARCH: submit on Enter in search inputs (avoid double-submit)
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.search-bar input[type=text]').forEach(function(inp) {
    inp.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        inp.closest('form')?.submit();
      }
    });
  });
});

// ============================================================
// FORM: Prevent double-submit on any form button
// ============================================================
document.addEventListener('submit', function(e) {
  const form = e.target;
  if (form.dataset.submitting) { e.preventDefault(); return; }
  form.dataset.submitting = '1';
  const btns = form.querySelectorAll('button[type=submit], input[type=submit]');
  btns.forEach(function(btn) {
    setTimeout(function() { btn.disabled = true; }, 0);
  });
});

// ============================================================
// SIDEBAR: badge pulse animation if > 0 alerts
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
  const badge = document.querySelector('.nav-badge');
  if (badge && parseInt(badge.textContent) > 0) {
    badge.style.animation = 'pulse 2s infinite';
  }
});

// Keyboard shortcut: Ctrl+K → focus search
document.addEventListener('keydown', function(e) {
  if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
    e.preventDefault();
    const searchInput = document.querySelector('.search-bar input[type=text]');
    if (searchInput) {
      searchInput.focus();
      searchInput.select();
    }
  }
});
