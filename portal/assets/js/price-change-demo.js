'use strict';

(function() {
  const sheet = document.querySelector('[data-price-change-sheet]');
  if (!sheet) return;

  const triggers = Array.from(document.querySelectorAll('[data-price-change-open]'));
  const closeButtons = Array.from(sheet.querySelectorAll('[data-price-change-close]'));
  const presetButtons = Array.from(sheet.querySelectorAll('[data-price-change-percent]'));
  const form = sheet.querySelector('[data-price-change-form]');
  const product = sheet.querySelector('[data-price-change-product]');
  const branch = sheet.querySelector('[data-price-change-branch]');
  const code = sheet.querySelector('[data-price-change-code]');
  const current = sheet.querySelector('[data-price-change-current]');
  const price = sheet.querySelector('[data-price-change-value]');
  const reason = sheet.querySelector('[data-price-change-reason]');
  const difference = sheet.querySelector('[data-price-change-difference]');
  const result = sheet.querySelector('[data-price-change-result]');
  const csrf = sheet.querySelector('[data-price-change-csrf]');
  const stockItemId = sheet.querySelector('[data-price-change-stock-id]');
  const requestUid = sheet.querySelector('[data-price-change-request-uid]');
  const submitButton = form ? form.querySelector('button[type="submit"]') : null;
  const commandUrl = form ? String(form.dataset.priceCommandUrl || '') : '';
  const commandEnabled = form ? form.dataset.priceCommandEnabled === '1' : false;

  if (!form || !product || !branch || !code || !current || !price || !reason || !difference || !result || !csrf || !stockItemId || !requestUid || !submitButton) return;

  const currencyFormatter = new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
  const percentFormatter = new Intl.NumberFormat('es-AR', { maximumFractionDigits: 2 });
  let currentPrice = 0;
  let opener = null;
  let statusTimer = 0;
  let statusAttempts = 0;
  let submitInFlight = false;
  let activeCommandUid = '';

  function newRequestUid() {
    if (window.crypto && typeof window.crypto.randomUUID === 'function') {
      return window.crypto.randomUUID();
    }
    const bytes = new Uint8Array(16);
    window.crypto.getRandomValues(bytes);
    return Array.from(bytes, function(value) { return value.toString(16).padStart(2, '0'); }).join('');
  }

  function setBusy(busy) {
    submitButton.disabled = busy;
    submitButton.textContent = busy ? 'Enviando...' : (commandEnabled ? 'Enviar cambio a la sucursal' : 'Simular cambio');
  }

  function commandStatusText(command) {
    const status = String(command.status || 'pending');
    if (status === 'applied') return 'Precio actualizado en ' + (command.branch_name || 'la sucursal') + ': ' + currencyFormatter.format(command.applied_price || command.new_price) + '.';
    if (status === 'conflict') return 'No se aplico: el precio local cambio antes de recibir la orden. Actualiza el inventario y vuelve a revisar.';
    if (status === 'rejected') return 'La sucursal rechazo la orden. Revisa el producto, la licencia y los permisos locales.';
    if (status === 'failed') return 'La sucursal no pudo aplicar el cambio. La orden quedo auditada para revision.';
    if (status === 'expired') return 'La orden vencio sin ser aplicada. Puedes generar una nueva despues de verificar la conexion.';
    if (status === 'processing') return 'La sucursal recibio la orden y esta validando el precio...';
    return 'Orden pendiente. Se aplicara cuando la sucursal vuelva a sincronizar.';
  }

  async function postCommand(data) {
    const response = await fetch(commandUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
      body: new URLSearchParams(data).toString()
    });
    const payload = await response.json().catch(function() { return null; });
    if (!response.ok || !payload || !payload.ok) {
      const message = payload && payload.message ? payload.message : 'No se pudo procesar la orden.';
      throw new Error(message);
    }
    return payload;
  }

  function stopStatusPolling() {
    if (statusTimer) window.clearTimeout(statusTimer);
    statusTimer = 0;
  }

  async function pollCommandStatus(commandUid) {
    stopStatusPolling();
    statusAttempts += 1;
    try {
      const payload = await postCommand({ action: 'status', _csrf: csrf.value, command_uid: commandUid });
      const command = payload.command || {};
      result.textContent = commandStatusText(command);
      result.hidden = false;
      const terminal = ['applied', 'rejected', 'conflict', 'failed', 'expired'].includes(String(command.status || ''));
      if (terminal) {
        if (command.status === 'applied') {
          currentPrice = Number(command.applied_price || command.new_price || currentPrice);
          current.textContent = currencyFormatter.format(currentPrice);
          if (opener) {
            opener.dataset.priceChangeCurrent = String(currentPrice);
            opener.dataset.priceChangeCurrentLabel = current.textContent;
          }
        }
        return;
      }
    } catch (error) {
      result.textContent = 'La orden fue creada, pero no se pudo actualizar su estado. Puedes cerrar y volver a consultar el producto.';
      result.hidden = false;
    }
    if (statusAttempts < 24 && !sheet.hidden) {
      statusTimer = window.setTimeout(function() { pollCommandStatus(commandUid); }, 5000);
    }
  }

  function parsePrice(value) {
    const normalized = String(value || '').trim().replace(',', '.');
    if (normalized === '') return null;
    const parsed = Number(normalized);
    return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
  }

  function updateDifference() {
    const nextPrice = parsePrice(price.value);
    difference.classList.remove('is-positive', 'is-negative');
    result.hidden = true;

    if (nextPrice === null) {
      difference.querySelector('strong').textContent = 'Ingresa el nuevo precio';
      return null;
    }

    const delta = nextPrice - currentPrice;
    let label = 'Sin variacion';
    if (currentPrice <= 0) {
      label = 'Precio inicial ' + currencyFormatter.format(nextPrice);
      difference.classList.add('is-positive');
    } else if (delta > 0) {
      label = 'Sube ' + currencyFormatter.format(delta) + ' (+' + percentFormatter.format((delta / currentPrice) * 100) + '%)';
      difference.classList.add('is-positive');
    } else if (delta < 0) {
      label = 'Baja ' + currencyFormatter.format(Math.abs(delta)) + ' (-' + percentFormatter.format((Math.abs(delta) / currentPrice) * 100) + '%)';
      difference.classList.add('is-negative');
    }
    difference.querySelector('strong').textContent = label;
    return { price: nextPrice, delta: delta, label: label };
  }

  function setSuggestedPrice(percent) {
    if (currentPrice <= 0) return;
    const suggested = Math.round(currentPrice * (1 + (percent / 100)) * 100) / 100;
    price.value = suggested.toFixed(2);
    updateDifference();
    price.focus();
  }

  function openSheet(trigger) {
    opener = trigger;
    currentPrice = Number(trigger.dataset.priceChangeCurrent || 0);
    product.textContent = trigger.dataset.priceChangeName || 'Producto';
    branch.textContent = trigger.dataset.priceChangeBranch || 'Sin sucursal';
    code.textContent = trigger.dataset.priceChangeCode || 'Sin codigo';
    current.textContent = trigger.dataset.priceChangeCurrentLabel || currencyFormatter.format(currentPrice);
    price.value = '';
    reason.value = 'supplier_cost';
    stockItemId.value = trigger.dataset.priceChangeStockId || '';
    requestUid.value = newRequestUid();
    statusAttempts = 0;
    submitInFlight = false;
    activeCommandUid = '';
    stopStatusPolling();
    setBusy(false);
    presetButtons.forEach(function(button) { button.disabled = currentPrice <= 0; });
    result.hidden = true;
    updateDifference();
    sheet.hidden = false;
    document.body.classList.add('portal-stock-count-open');
    price.focus();
  }

  function closeSheet() {
    sheet.hidden = true;
    document.body.classList.remove('portal-stock-count-open');
    result.hidden = true;
    stopStatusPolling();
    if (opener) opener.focus();
    opener = null;
  }

  triggers.forEach(function(trigger) {
    trigger.addEventListener('click', function() { openSheet(trigger); });
  });
  closeButtons.forEach(function(button) { button.addEventListener('click', closeSheet); });
  presetButtons.forEach(function(button) {
    button.addEventListener('click', function() {
      setSuggestedPrice(Number(button.dataset.priceChangePercent || 0));
    });
  });
  price.addEventListener('input', updateDifference);

  form.addEventListener('submit', async function(event) {
    event.preventDefault();
    if (submitInFlight || activeCommandUid !== '') return;
    const preview = updateDifference();
    if (!preview) {
      price.focus();
      return;
    }

    const reasonLabel = reason.options[reason.selectedIndex].text;
    if (!commandEnabled || commandUrl === '') {
      result.textContent = 'Simulacion lista: nuevo precio ' + currencyFormatter.format(preview.price) + '. ' + preview.label + '. Motivo: ' + reasonLabel + '. No se modifico el precio real.';
      result.hidden = false;
      return;
    }

    submitInFlight = true;
    setBusy(true);
    result.textContent = 'Creando una orden segura para ' + branch.textContent + '...';
    result.hidden = false;
    try {
      const payload = await postCommand({
        action: 'create',
        _csrf: csrf.value,
        stock_item_id: stockItemId.value,
        new_price: preview.price.toFixed(2),
        reason: reason.value,
        request_uid: requestUid.value
      });
      const command = payload.command || {};
      result.textContent = commandStatusText(command);
      activeCommandUid = String(command.command_uid || '');
      if (activeCommandUid) pollCommandStatus(activeCommandUid);
    } catch (error) {
      result.textContent = error instanceof Error ? error.message : 'No se pudo enviar el cambio.';
    } finally {
      submitInFlight = false;
      if (activeCommandUid) {
        submitButton.disabled = true;
        submitButton.textContent = 'Orden enviada';
      } else {
        setBusy(false);
      }
      result.hidden = false;
    }
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
    stopStatusPolling();
    document.body.classList.remove('portal-stock-count-open');
  });
})();
