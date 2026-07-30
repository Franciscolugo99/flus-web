'use strict';

(function() {
  const sheet = document.querySelector('[data-stock-count-sheet]');
  if (!sheet) return;

  const triggers = Array.from(document.querySelectorAll('[data-stock-count-open]'));
  const closeButtons = Array.from(sheet.querySelectorAll('[data-stock-count-close]'));
  const form = sheet.querySelector('[data-stock-count-form]');
  const product = sheet.querySelector('[data-stock-count-product]');
  const branch = sheet.querySelector('[data-stock-count-branch]');
  const code = sheet.querySelector('[data-stock-count-code]');
  const current = sheet.querySelector('[data-stock-count-current]');
  const quantity = sheet.querySelector('[data-stock-count-quantity]');
  const reason = sheet.querySelector('[data-stock-count-reason]');
  const difference = sheet.querySelector('[data-stock-count-difference]');
  const result = sheet.querySelector('[data-stock-count-result]');

  if (!form || !product || !branch || !code || !current || !quantity || !reason || !difference || !result) return;

  const numberFormatter = new Intl.NumberFormat('es-AR', { maximumFractionDigits: 3 });
  let currentStock = 0;
  let unit = 'unidad';
  let opener = null;

  function parseQuantity(value) {
    const normalized = String(value || '').trim().replace(',', '.');
    if (normalized === '') return null;
    const parsed = Number(normalized);
    return Number.isFinite(parsed) && parsed >= 0 ? parsed : null;
  }

  function quantityLabel(value) {
    const key = unit.toLowerCase();
    const singular = Math.abs(value - 1) < 0.000001;
    const labels = {
      unidad: singular ? 'unidad' : 'unidades',
      unidades: singular ? 'unidad' : 'unidades',
      litro: singular ? 'litro' : 'litros',
      litros: singular ? 'litro' : 'litros',
      paquete: singular ? 'paquete' : 'paquetes',
      paquetes: singular ? 'paquete' : 'paquetes',
      caja: singular ? 'caja' : 'cajas',
      cajas: singular ? 'caja' : 'cajas',
      kilo: 'kg',
      kilogramo: 'kg',
      kg: 'kg'
    };
    return numberFormatter.format(value) + ' ' + (labels[key] || key);
  }

  function updateDifference() {
    const counted = parseQuantity(quantity.value);
    difference.classList.remove('is-positive', 'is-negative');
    result.hidden = true;

    if (counted === null) {
      difference.querySelector('strong').textContent = 'Ingresa la cantidad contada';
      return null;
    }

    const delta = counted - currentStock;
    let label = 'Sin diferencia';
    if (delta > 0) {
      label = 'Sobrante de ' + quantityLabel(delta);
      difference.classList.add('is-positive');
    } else if (delta < 0) {
      label = 'Faltante de ' + quantityLabel(Math.abs(delta));
      difference.classList.add('is-negative');
    }
    difference.querySelector('strong').textContent = label;
    return { counted: counted, delta: delta, label: label };
  }

  function openSheet(trigger) {
    opener = trigger;
    currentStock = Number(trigger.dataset.stockCountCurrent || 0);
    unit = trigger.dataset.stockCountUnit || 'unidad';
    product.textContent = trigger.dataset.stockCountName || 'Producto';
    branch.textContent = trigger.dataset.stockCountBranch || 'Sin sucursal';
    code.textContent = trigger.dataset.stockCountCode || 'Sin codigo';
    current.textContent = trigger.dataset.stockCountCurrentLabel || quantityLabel(currentStock);
    quantity.step = trigger.dataset.stockCountStep || '1';
    quantity.value = '';
    reason.value = 'physical_count';
    result.hidden = true;
    updateDifference();
    sheet.hidden = false;
    document.body.classList.add('portal-stock-count-open');
    quantity.focus();
  }

  function closeSheet() {
    sheet.hidden = true;
    document.body.classList.remove('portal-stock-count-open');
    result.hidden = true;
    if (opener) opener.focus();
    opener = null;
  }

  triggers.forEach(function(trigger) {
    trigger.addEventListener('click', function() { openSheet(trigger); });
  });
  closeButtons.forEach(function(button) { button.addEventListener('click', closeSheet); });
  quantity.addEventListener('input', updateDifference);

  form.addEventListener('submit', function(event) {
    event.preventDefault();
    const preview = updateDifference();
    if (!preview) {
      quantity.focus();
      return;
    }

    const reasonLabel = reason.options[reason.selectedIndex].text;
    result.textContent = 'Simulacion lista: ' + preview.label + '. Motivo: ' + reasonLabel + '. No se modifico el stock real.';
    result.hidden = false;
  });

  sheet.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
      closeSheet();
      return;
    }
    if (event.key !== 'Tab') return;

    const focusable = Array.from(sheet.querySelectorAll('button:not([hidden]), input:not([hidden]), select:not([hidden])'))
      .filter(function(element) { return !element.disabled && element.offsetParent !== null; });
    if (focusable.length === 0) return;
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  });

  window.addEventListener('pagehide', function() {
    document.body.classList.remove('portal-stock-count-open');
  });
})();
