'use strict';

(function() {
  const form = document.querySelector('[data-stock-scanner]');
  if (!form) return;

  const openButton = form.querySelector('[data-stock-scan-open]');
  const closeButton = form.querySelector('[data-stock-scan-close]');
  const torchButton = form.querySelector('[data-stock-scan-torch]');
  const panel = form.querySelector('[data-stock-scanner-panel]');
  const video = form.querySelector('[data-stock-scanner-video]');
  const status = form.querySelector('[data-stock-scanner-status]');
  const search = form.querySelector('#portalStockSearch');
  const stockState = form.querySelector('#portalStockState');
  const zxingSource = form.dataset.zxingSrc || '';

  if (!openButton || !closeButton || !panel || !video || !status || !search || !stockState) return;

  const formats = ['ean_13', 'ean_8', 'upc_a', 'upc_e', 'code_128', 'qr_code'];
  let active = false;
  let stream = null;
  let detector = null;
  let nativeTimer = 0;
  let zxingControls = null;
  let torchEnabled = false;
  let lastCode = '';
  let zxingPromise = null;

  function setStatus(message, isError) {
    status.textContent = message;
    status.classList.toggle('is-error', Boolean(isError));
  }

  function stopMedia() {
    active = false;
    window.clearTimeout(nativeTimer);
    nativeTimer = 0;

    if (zxingControls && typeof zxingControls.stop === 'function') {
      zxingControls.stop();
    }
    zxingControls = null;

    if (stream) {
      stream.getTracks().forEach(function(track) { track.stop(); });
    }
    stream = null;
    video.pause();
    video.srcObject = null;
    torchEnabled = false;
    torchButton.hidden = true;
    torchButton.textContent = 'Encender luz';
  }

  function closeScanner() {
    stopMedia();
    panel.hidden = true;
    openButton.disabled = false;
    openButton.focus();
  }

  function submitCode(value) {
    const code = String(value || '').trim();
    if (!code || code === lastCode) return;

    lastCode = code;
    setStatus('Codigo detectado. Buscando producto...', false);
    stopMedia();
    panel.hidden = true;
    search.value = code;
    stockState.value = 'all';
    window.setTimeout(function() { form.requestSubmit(); }, 80);
  }

  function cameraMessage(error) {
    const name = error && error.name ? error.name : '';
    if (!window.isSecureContext) return 'La camara requiere una conexion HTTPS segura.';
    if (name === 'NotAllowedError' || name === 'SecurityError') return 'Permiso de camara bloqueado. Habilitalo desde el navegador y volve a intentar.';
    if (name === 'NotFoundError' || name === 'DevicesNotFoundError') return 'No encontramos una camara disponible en este dispositivo.';
    if (name === 'NotReadableError' || name === 'TrackStartError') return 'La camara esta siendo usada por otra aplicacion.';
    return 'No pudimos iniciar la camara. Podes escribir el codigo manualmente.';
  }

  function prepareTorch(mediaStream) {
    const track = mediaStream.getVideoTracks()[0];
    if (!track || typeof track.getCapabilities !== 'function') return;
    const capabilities = track.getCapabilities();
    if (!capabilities || !capabilities.torch) return;

    torchButton.hidden = false;
    torchButton.onclick = async function() {
      torchEnabled = !torchEnabled;
      try {
        await track.applyConstraints({ advanced: [{ torch: torchEnabled }] });
        torchButton.textContent = torchEnabled ? 'Apagar luz' : 'Encender luz';
      } catch (error) {
        torchEnabled = false;
        torchButton.textContent = 'Encender luz';
        setStatus('Este dispositivo no permite controlar la luz.', true);
      }
    };
  }

  async function nativeScanLoop() {
    if (!active || !detector) return;
    try {
      const codes = await detector.detect(video);
      if (codes.length > 0 && codes[0].rawValue) {
        submitCode(codes[0].rawValue);
        return;
      }
    } catch (error) {
      if (active) setStatus('Buscando un codigo visible...', false);
    }
    nativeTimer = window.setTimeout(nativeScanLoop, 180);
  }

  async function startNativeScanner() {
    let supported = [];
    if (typeof window.BarcodeDetector !== 'undefined' &&
        typeof window.BarcodeDetector.getSupportedFormats === 'function') {
      try {
        supported = await window.BarcodeDetector.getSupportedFormats();
      } catch (error) {
        return false;
      }
    }
    const usableFormats = formats.filter(function(format) { return supported.includes(format); });
    if (usableFormats.length === 0) return false;

    detector = new window.BarcodeDetector({ formats: usableFormats });
    stream = await navigator.mediaDevices.getUserMedia({
      audio: false,
      video: { facingMode: { ideal: 'environment' } }
    });
    video.srcObject = stream;
    await video.play();
    prepareTorch(stream);
    setStatus('Camara activa. Centra el codigo dentro del recuadro.', false);
    nativeScanLoop();
    return true;
  }

  function loadZxing() {
    if (window.ZXingBrowser) return Promise.resolve(window.ZXingBrowser);
    if (zxingPromise) return zxingPromise;
    if (!zxingSource) return Promise.reject(new Error('ZXING_SOURCE_MISSING'));

    zxingPromise = new Promise(function(resolve, reject) {
      const script = document.createElement('script');
      script.src = zxingSource;
      script.async = true;
      script.onload = function() {
        if (window.ZXingBrowser) resolve(window.ZXingBrowser);
        else reject(new Error('ZXING_LOAD_FAILED'));
      };
      script.onerror = function() { reject(new Error('ZXING_LOAD_FAILED')); };
      document.head.appendChild(script);
    });
    return zxingPromise;
  }

  async function startFallbackScanner() {
    const library = await loadZxing();
    const reader = new library.BrowserMultiFormatReader();
    setStatus('Camara activa. Centra el codigo dentro del recuadro.', false);
    const controls = await reader.decodeFromConstraints(
      { audio: false, video: { facingMode: { ideal: 'environment' } } },
      video,
      function(result) {
        if (result && typeof result.getText === 'function') submitCode(result.getText());
      }
    );
    zxingControls = controls;
    if (!active) {
      if (controls && typeof controls.stop === 'function') controls.stop();
      return;
    }
    stream = video.srcObject;
    if (stream) prepareTorch(stream);
  }

  async function openScanner() {
    if (active) return;
    if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
      panel.hidden = false;
      setStatus('Este navegador no permite usar la camara. Escribi el codigo manualmente.', true);
      return;
    }

    active = true;
    lastCode = '';
    panel.hidden = false;
    openButton.disabled = true;
    setStatus('Solicitando permiso para usar la camara...', false);
    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    try {
      const nativeStarted = await startNativeScanner();
      if (!nativeStarted && active) await startFallbackScanner();
    } catch (error) {
      stopMedia();
      openButton.disabled = false;
      setStatus(cameraMessage(error), true);
    }
  }

  openButton.addEventListener('click', openScanner);
  closeButton.addEventListener('click', closeScanner);
  window.addEventListener('pagehide', stopMedia);
  document.addEventListener('visibilitychange', function() {
    if (document.hidden && active) closeScanner();
  });
})();
