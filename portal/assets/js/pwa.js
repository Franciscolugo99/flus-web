'use strict';

(function() {
  const installButtons = Array.from(document.querySelectorAll('[data-pwa-install]'));
  const helpMessages = Array.from(document.querySelectorAll('[data-pwa-install-help]'));
  const standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
  const isIos = /iphone|ipad|ipod/i.test(window.navigator.userAgent);
  let installPrompt = null;

  function setHelp(message) {
    helpMessages.forEach(function(element) {
      element.textContent = message;
      element.hidden = message === '';
    });
  }

  function showButtons() {
    if (standalone) return;
    installButtons.forEach(function(button) { button.hidden = false; });
  }

  if ('serviceWorker' in navigator && window.isSecureContext) {
    window.addEventListener('load', function() {
      navigator.serviceWorker.register('./sw.js', { scope: './' }).catch(function() {
        setHelp('No se pudo preparar la instalacion en este navegador. Podes seguir usando FLUS normalmente.');
      });
    });
  }

  window.addEventListener('beforeinstallprompt', function(event) {
    event.preventDefault();
    installPrompt = event;
    showButtons();
  });

  if (isIos && !standalone) {
    showButtons();
  }

  installButtons.forEach(function(button) {
    button.addEventListener('click', async function() {
      if (isIos && !installPrompt) {
        setHelp('En Safari, toca Compartir y luego Agregar a inicio.');
        return;
      }
      if (!installPrompt) {
        setHelp('Abri el menu del navegador y elegi Instalar aplicacion o Agregar a pantalla principal.');
        return;
      }

      button.disabled = true;
      installPrompt.prompt();
      const choice = await installPrompt.userChoice;
      installPrompt = null;
      button.disabled = false;
      if (choice.outcome !== 'accepted') {
        setHelp('La instalacion se cancelo. Podes volver a intentarlo cuando quieras.');
      }
    });
  });

  window.addEventListener('appinstalled', function() {
    installButtons.forEach(function(button) { button.hidden = true; });
    setHelp('FLUS quedo instalado en este dispositivo.');
  });
})();
