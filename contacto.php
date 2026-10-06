<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/security.php';
$pageTitle = 'Contacto y demo de FLUS | Sistema de gestión comercial';
$pageDescription = 'Solicitá una demo de FLUS y conocé cómo puede ayudarte a ordenar ventas, stock, caja, clientes y facturación con una operación más profesional.';
$pageBreadcrumbs = [
    ['name' => 'Inicio', 'url' => page_url()],
    ['name' => 'Contacto y demo', 'url' => page_url('contacto.php')],
];
$pageSchemas = [
    [
        '@context' => 'https://schema.org',
        '@type' => 'ContactPage',
        'name' => 'Contacto y demo — FLUS',
        'description' => 'Contacto comercial y solicitud de demo para FLUS, sistema de gestión comercial.',
        'url' => page_url('contacto.php'),
    ],
];

/**
 * Cualificacion comercial del formulario.
 * Las opciones estan copiadas EXACTAMENTE del formulario publicado en
 * produccion (/contacto.php) para no perder ni alterar datos que hoy se
 * reciben. No se invento ninguna opcion.
 */
$contactQualifiers = [
    'business_type' => [
        'label' => 'Rubro / tipo de negocio',
        'required' => true,
        'default' => 'Elegir…',
        'options' => ['Kiosco / almacén', 'Dietética', 'Vinoteca', 'Ferretería', 'Indumentaria', 'Bazar / regalería', 'Otro comercio minorista', 'Servicios / otro'],
    ],
    'current_system' => [
        'label' => '¿Cómo trabajás hoy?',
        'required' => true,
        'default' => 'Elegir…',
        'options' => ['Excel / planillas', 'Cuaderno / manual', 'Otro sistema', 'Estoy abriendo el negocio', 'Otro'],
    ],
    'locations' => [
        'label' => 'Locales',
        'required' => false,
        'default' => 'No informado',
        'options' => ['1', '2', '3 o más'],
    ],
    'registers' => [
        'label' => 'Cajas de cobro',
        'required' => false,
        'default' => 'No informado',
        'options' => ['1', '2', '3-5', '6 o más'],
    ],
    'products' => [
        'label' => 'Productos aprox.',
        'required' => false,
        'default' => 'No informado',
        'options' => ['Menos de 500', '500-2.000', '2.000-10.000', 'Más de 10.000'],
    ],
    'needs_arca' => [
        'label' => '¿Necesitás facturación electrónica con ARCA?',
        'required' => false,
        'default' => 'No informado',
        'options' => ['Sí', 'No', 'Más adelante', 'No estoy seguro'],
    ],
    'priority' => [
        'label' => '¿Qué querés resolver primero?',
        'required' => false,
        'default' => 'Elegir…',
        'options' => ['Caja y ventas', 'Stock e inventario', 'Compras y proveedores', 'Facturación', 'Ordenar todo el circuito', 'Reemplazar sistema actual'],
    ],
];

$contactForm = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'company' => '',
    'message' => '',
] + array_map(static fn (array $q): string => '', $contactQualifiers);
$contactErrors = [];
$contactNotice = null;
$contactNoticeType = 'success';
$privacyAccepted = false;
$rateLimitUnavailable = false;
$turnstileEnabled = turnstile_enabled();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $contactForm = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'phone' => trim((string) ($_POST['phone'] ?? '')),
        'company' => trim((string) ($_POST['company'] ?? '')),
        'message' => trim((string) ($_POST['message'] ?? '')),
    ];
    // Solo se aceptan valores que existan en la lista de opciones: un valor
    // arbitrario enviado a mano se descarta en vez de guardarse.
    foreach ($contactQualifiers as $key => $def) {
        $raw = trim((string) ($_POST[$key] ?? ''));
        $contactForm[$key] = in_array($raw, $def['options'], true) ? $raw : '';
    }
    $websiteField = trim((string) ($_POST['website'] ?? ''));
    $turnstileToken = trim((string) ($_POST['cf-turnstile-response'] ?? ''));
    $privacyAccepted = (string) ($_POST['privacy'] ?? '') !== '';

    // Rate limit. Si no se puede consultar (por ejemplo la base de datos no
    // responde) NO se bloquea el envio: se registra en el log y se sigue. Antes
    // se agregaba un error que impedia enviar, de modo que una caida de la base
    // tumbaba el canal de contacto entero. La proteccion antispam efectiva sigue
    // siendo el honeypot (y Turnstile cuando esta configurado).
    $rateLimitUnavailable = false;
    try {
        $contactLimit = security_rate_limit('contact_form_ip', security_client_ip(), 5, 900);
        if (!$contactLimit['allowed']) {
            http_response_code(429);
            header('Retry-After: ' . $contactLimit['retry_after']);
            $contactErrors['rate_limit'] =
                'Esperá unos minutos antes de volver a enviar.';
        }
    } catch (Throwable $e) {
        $rateLimitUnavailable = true;
        error_log('[FLUS Web] rate limit no disponible, se permite el envio: ' . $e->getMessage());
    }

    // CSRF: exige token en el cuerpo + cookie coincidente (double submit).
    if (!security_csrf_validate()) {
        $contactErrors['csrf'] = 'Tu sesión de formulario expiró. Volvé a enviar la consulta.';
    }

    if (!$privacyAccepted) {
        $contactErrors['privacy'] = 'Para enviar la consulta tenés que aceptar la política de privacidad.';
    }

    if ($contactForm['name'] === '') {
        $contactErrors['name'] = 'Ingresá tu nombre.';
    }

    if ($contactForm['email'] === '') {
        $contactErrors['email'] = 'Ingresá un correo para responderte.';
    } elseif (!filter_var($contactForm['email'], FILTER_VALIDATE_EMAIL)) {
        $contactErrors['email'] = 'Ingresá un correo válido.';
    }

    if ($contactForm['message'] === '') {
        $contactErrors['message'] = 'Contanos un poco sobre tu consulta.';
    }

    if (mb_strlen($contactForm['name']) > 120) {
        $contactErrors['name'] = 'El nombre es demasiado largo.';
    }

    if (mb_strlen($contactForm['email']) > 160) {
        $contactErrors['email'] = 'El correo es demasiado largo.';
    }

    if (mb_strlen($contactForm['phone']) > 60) {
        $contactErrors['phone'] = 'El teléfono es demasiado largo.';
    }

    if (mb_strlen($contactForm['company']) > 120) {
        $contactErrors['company'] = 'El nombre del negocio es demasiado largo.';
    }

    if (mb_strlen($contactForm['message']) > 3000) {
        $contactErrors['message'] = 'El mensaje es demasiado largo.';
    }

    // Campos de cualificacion obligatorios (los mismos que exige produccion).
    foreach ($contactQualifiers as $key => $def) {
        if ($def['required'] && $contactForm[$key] === '') {
            $contactErrors[$key] = 'Elegí una opción.';
        }
    }

    if ($turnstileEnabled) {
        if ($turnstileToken === '') {
            $contactErrors['turnstile'] = 'Completá la verificación antes de enviar la consulta.';
        } else {
            $turnstileValidation = turnstile_validate_token($turnstileToken);
            if (empty($turnstileValidation['success'])) {
                $contactErrors['turnstile'] = 'No pudimos validar la verificación. Probá de nuevo.';
            }
        }
    }

    if ($websiteField !== '') {
        // Honeypot: se simula exito y no se envia nada. Hay que forzar el tipo
        // 'success' porque si alguna validacion previa marco error, el aviso
        // quedaria como error y delataria la trampa al bot.
        $contactNotice = 'Gracias. Recibimos tu consulta.';
        $contactNoticeType = 'success';
        $contactErrors = [];
    } elseif ($contactErrors === []) {
        $mailTo = trim((string) ($site['mail_to'] ?? $site['contact_email']));
        $mailFrom = trim((string) ($site['mail_from'] ?? $site['contact_email']));
        $replyTo = str_replace(["\r", "\n"], '', $contactForm['email']);
        $safeName = str_replace(["\r", "\n"], '', $contactForm['name']);
        $subject = 'Nueva consulta web | FLUS';
        $bodyLines = [
            'Nueva consulta recibida desde flus.com.ar',
            '',
            'Nombre: ' . $contactForm['name'],
            'Correo: ' . $contactForm['email'],
            'Telefono: ' . ($contactForm['phone'] !== '' ? $contactForm['phone'] : 'No informado'),
            'Empresa: ' . ($contactForm['company'] !== '' ? $contactForm['company'] : 'No informada'),
            '',
            'Cualificacion comercial:',
        ];
        foreach ($contactQualifiers as $key => $def) {
            $bodyLines[] = $def['label'] . ': '
                . ($contactForm[$key] !== '' ? $contactForm[$key] : 'No informado');
        }
        $bodyLines[] = '';
        $bodyLines[] = 'Mensaje:';
        $bodyLines[] = $contactForm['message'];

        $mailSent = false;
        if ($mailTo !== '') {
            $mailSent = smtp_send_mail([
                'to_email' => $mailTo,
                'from_email' => $mailFrom,
                'from_name' => 'FLUS',
                'reply_email' => $replyTo,
                'reply_name' => $safeName !== '' ? $safeName : 'Contacto web',
                'subject' => $subject,
                'body' => implode("\n", $bodyLines),
            ]);
        }

        if ($mailSent) {
            $contactNotice = 'Gracias. Tu mensaje fue enviado y te vamos a responder a la brevedad.';
            $contactNoticeType = 'success';
            $contactForm = [
                'name' => '',
                'email' => '',
                'phone' => '',
                'company' => '',
                'message' => '',
            ] + array_map(static fn (array $q): string => '', $contactQualifiers);
            $privacyAccepted = false;
        } else {
            error_log('[FLUS Web] envio SMTP de contacto fallo (mail_to=' . $mailTo . ').');
            $contactNotice = 'No pudimos enviar el mensaje en este momento. Probá por WhatsApp o escribinos a ' . ($site['contact_email'] ?? 'nuestro correo') . '.';
            $contactNoticeType = 'error';
        }
    } else {
        $contactNotice = 'Revisá los campos marcados para poder enviar la consulta.';
        $contactNoticeType = 'error';
    }

    // PRG: si el envio fue exitoso, redirigimos para que un refresh no reenvie.
    if ($contactNoticeType === 'success' && $contactNotice !== null && $contactErrors === []) {
        header('Location: ' . site_url('contacto.php') . '?enviado=1#formulario-contacto', true, 303);
        exit;
    }
}

// Retorno del PRG: mostramos la confirmacion y habilitamos el evento de lead.
$trackLeadSuccess = false;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' && isset($_GET['enviado'])) {
    $contactNotice = 'Gracias. Tu mensaje fue enviado y te vamos a responder a la brevedad.';
    $trackLeadSuccess = true;
}

// El token CSRF debe generarse (y por lo tanto emitir su cookie) ANTES de que
// el header empiece a imprimir HTML: setcookie() falla si ya hay salida.
security_csrf_token();

// Prioridad de mensajes: CSRF es lo unico que impide avanzar; si la validacion
// del rate limit no esta disponible pero todo lo demas esta bien, no alarmamos
// al visitante (el problema es interno y ya quedo en el log).
if (isset($contactErrors['csrf'])) {
    $contactNotice = $contactErrors['csrf'];
    $contactNoticeType = 'error';
} elseif ($rateLimitUnavailable && $contactErrors === [] && $contactNoticeType === 'success') {
    $contactNotice = null;
}

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero page-hero-contact">
  <div class="container page-hero-grid">
    <div>
      <span class="eyebrow">Contacto y demo</span>
      <h1>Coordiná una demo de FLUS y revisá cómo encaja en tu operación</h1>
      <p>
        La mejor forma de evaluar FLUS es verlo aplicado a tu dinámica diaria: ventas, caja, stock,
        clientes y facturación dentro del mismo flujo de trabajo.
      </p>
      <div class="hero-actions">
        <?php if ($site['whatsapp_number'] !== ''): ?>
          <a class="btn btn-primary" href="<?= e(whatsapp_url('Hola, quiero conocer FLUS y coordinar una demo.')) ?>" target="_blank" rel="noopener" data-track-event="click_whatsapp" data-track-label="Hero contacto WhatsApp">Hablar por WhatsApp</a>
        <?php endif; ?>
        <?php if ($site['contact_email'] !== ''): ?>
          <a class="btn btn-secondary" href="mailto:<?= e($site['contact_email']) ?>" data-track-event="click_contact" data-track-label="Hero contacto correo">Enviar correo</a>
        <?php endif; ?>
      </div>
    </div>

    <aside class="hero-panel">
      <h2>Qué podés esperar de la conversación</h2>
      <ul class="check-list">
        <li>Revisar si FLUS encaja con tu operación.</li>
        <li>Mirar ventas, stock, caja y facturación con foco real.</li>
        <li>Entender qué podés ordenar y llevar un mejor registro.</li>
        <li>Definir próximos pasos para una demo más profunda.</li>
      </ul>
    </aside>
  </div>
</section>

<section class="section" data-reveal>
  <div class="container contact-form-shell">
    <div class="surface-card contact-form-card" id="formulario-contacto">
      <span class="section-kicker">Formulario de contacto</span>
      <h2>Escribinos y coordinamos una demo</h2>
      <p class="section-lead">
        Dejanos tus datos y contanos un poco sobre tu operación. Te respondemos a la brevedad.
      </p>

      <?php if ($contactNotice !== null && $contactNoticeType === 'error'): ?>
        <div class="form-alert form-alert-error" role="alert">
          <?= e($contactNotice) ?>
        </div>
      <?php endif; ?>

      <?php if ($contactNotice !== null && $contactNoticeType === 'success'): ?>
        <div class="form-success-panel" role="status" aria-live="polite">
          <div class="form-success-icon" aria-hidden="true">
            <svg viewBox="0 0 48 48" fill="none">
              <circle class="form-success-icon__circle" cx="24" cy="24" r="21" stroke="currentColor" stroke-width="2.5"/>
              <path class="form-success-icon__check" d="M14 24.5l7.5 7.5 12.5-14" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <h3 class="form-success-title">¡Consulta enviada!</h3>
          <p class="form-success-text"><?= e($contactNotice) ?></p>
          <?php if ($site['whatsapp_number'] !== ''): ?>
            <a class="btn btn-primary" href="<?= e(whatsapp_url('Hola, quiero conocer FLUS y coordinar una demo.')) ?>" target="_blank" rel="noopener" data-track-event="click_whatsapp" data-track-label="Post formulario WhatsApp">
              También podés escribir por WhatsApp
            </a>
          <?php endif; ?>
        </div>
      <?php else: ?>

      <form class="contact-form" data-contact-form data-track-event="contact_submit" action="<?= e(site_url('contacto.php')) ?>#formulario-contacto" method="post" novalidate>
        <?= security_csrf_input() ?>
        <div class="form-grid">

          <div class="form-field form-field--float<?= $contactForm['name'] !== '' ? ' has-value' : '' ?><?= isset($contactErrors['name']) ? ' has-error' : '' ?>">
            <input
              class="form-input<?= isset($contactErrors['name']) ? ' is-invalid' : '' ?>"
              type="text" name="name" id="field-name"
              maxlength="120" value="<?= e($contactForm['name']) ?>"
              autocomplete="name" required placeholder=" "
              data-validate="required" data-error-required="Ingresá tu nombre."
              <?= isset($contactErrors['name']) ? 'aria-invalid="true"' : '' ?>
              aria-describedby="field-name-error"
            >
            <label class="form-label" for="field-name">Nombre <span class="form-required" aria-hidden="true">*</span></label>
            <small class="form-error" id="field-name-error" aria-live="polite"><?= isset($contactErrors['name']) ? e($contactErrors['name']) : '' ?></small>
          </div>

          <div class="form-field form-field--float<?= $contactForm['email'] !== '' ? ' has-value' : '' ?><?= isset($contactErrors['email']) ? ' has-error' : '' ?>">
            <input
              class="form-input<?= isset($contactErrors['email']) ? ' is-invalid' : '' ?>"
              type="email" name="email" id="field-email"
              maxlength="160" value="<?= e($contactForm['email']) ?>"
              autocomplete="email" required placeholder=" "
              data-validate="required|email"
              data-error-required="Ingresá un correo para responderte."
              data-error-email="Ingresá un correo válido."
              <?= isset($contactErrors['email']) ? 'aria-invalid="true"' : '' ?>
              aria-describedby="field-email-error"
            >
            <label class="form-label" for="field-email">Correo <span class="form-required" aria-hidden="true">*</span></label>
            <small class="form-error" id="field-email-error" aria-live="polite"><?= isset($contactErrors['email']) ? e($contactErrors['email']) : '' ?></small>
          </div>

          <div class="form-field form-field--float<?= $contactForm['phone'] !== '' ? ' has-value' : '' ?><?= isset($contactErrors['phone']) ? ' has-error' : '' ?>">
            <input
              class="form-input<?= isset($contactErrors['phone']) ? ' is-invalid' : '' ?>"
              type="text" name="phone" id="field-phone"
              maxlength="60" value="<?= e($contactForm['phone']) ?>"
              autocomplete="tel" placeholder=" "
              <?= isset($contactErrors['phone']) ? 'aria-invalid="true"' : '' ?>
              aria-describedby="field-phone-error"
            >
            <label class="form-label" for="field-phone">Teléfono</label>
            <small class="form-error" id="field-phone-error" aria-live="polite"><?= isset($contactErrors['phone']) ? e($contactErrors['phone']) : '' ?></small>
          </div>

          <div class="form-field form-field--float<?= $contactForm['company'] !== '' ? ' has-value' : '' ?><?= isset($contactErrors['company']) ? ' has-error' : '' ?>">
            <input
              class="form-input<?= isset($contactErrors['company']) ? ' is-invalid' : '' ?>"
              type="text" name="company" id="field-company"
              maxlength="120" value="<?= e($contactForm['company']) ?>"
              autocomplete="organization" placeholder=" "
              <?= isset($contactErrors['company']) ? 'aria-invalid="true"' : '' ?>
              aria-describedby="field-company-error"
            >
            <label class="form-label" for="field-company">Negocio</label>
            <small class="form-error" id="field-company-error" aria-live="polite"><?= isset($contactErrors['company']) ? e($contactErrors['company']) : '' ?></small>
          </div>

        </div>

        <div class="form-grid contact-qualifier-grid">
          <?php foreach ($contactQualifiers as $key => $def):
            $fieldId = 'field-' . str_replace('_', '-', $key);
            $errorId = $fieldId . '-error';
            $hasError = isset($contactErrors[$key]);
            $value = (string) $contactForm[$key];
            $fullWidth = $key === 'priority';
          ?>
          <div class="form-field"<?= $fullWidth ? ' style="grid-column:1/-1"' : '' ?>>
            <label class="form-label form-label--static" for="<?= e($fieldId) ?>">
              <?= e($def['label']) ?><?php if ($def['required']): ?> <span class="form-required" aria-hidden="true">*</span><?php endif; ?>
            </label>
            <select
              class="form-input form-select<?= $hasError ? ' is-invalid' : '' ?>"
              name="<?= e($key) ?>" id="<?= e($fieldId) ?>"
              <?php if ($def['required']): ?>required data-validate="required" data-error-required="Elegí una opción."<?php endif; ?>
              <?= $hasError ? 'aria-invalid="true"' : '' ?>
              aria-describedby="<?= e($errorId) ?>"
            >
              <option value=""><?= e($def['default']) ?></option>
              <?php foreach ($def['options'] as $option): ?>
                <option value="<?= e($option) ?>"<?= $value === $option ? ' selected' : '' ?>><?= e($option) ?></option>
              <?php endforeach; ?>
            </select>
            <small class="form-error" id="<?= e($errorId) ?>" aria-live="polite"><?= $hasError ? e($contactErrors[$key]) : '' ?></small>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="form-field form-field--float form-field--textarea<?= $contactForm['message'] !== '' ? ' has-value' : '' ?><?= isset($contactErrors['message']) ? ' has-error' : '' ?>">
          <textarea
            class="form-input form-textarea<?= isset($contactErrors['message']) ? ' is-invalid' : '' ?>"
            name="message" id="field-message"
            rows="5" maxlength="3000" required placeholder=" "
            data-validate="required" data-error-required="Contanos un poco sobre tu consulta."
            data-char-counter
            <?= isset($contactErrors['message']) ? 'aria-invalid="true"' : '' ?>
            aria-describedby="field-message-error"
          ><?= e($contactForm['message']) ?></textarea>
          <label class="form-label" for="field-message">Mensaje <span class="form-required" aria-hidden="true">*</span></label>
          <div class="form-field-footer">
            <small class="form-error" id="field-message-error" aria-live="polite"><?= isset($contactErrors['message']) ? e($contactErrors['message']) : '' ?></small>
            <span class="form-char-counter" data-max="3000">
              <span class="form-char-counter__current"><?= mb_strlen($contactForm['message']) ?></span>&nbsp;/ 3000
            </span>
          </div>
        </div>

        <div class="form-consent<?= isset($contactErrors['privacy']) ? ' has-error' : '' ?>">
          <label class="form-consent__label" for="field-privacy">
            <input
              type="checkbox" name="privacy" id="field-privacy" value="1"
              required
              <?= $privacyAccepted ? 'checked' : '' ?>
              <?= isset($contactErrors['privacy']) ? 'aria-invalid="true"' : '' ?>
              aria-describedby="field-privacy-error"
            >
            <span>
              Acepto la
              <a href="<?= e(site_url('politica-de-privacidad.php')) ?>" target="_blank" rel="noopener">política de privacidad</a>
              y el tratamiento de mis datos para responder esta consulta.
            </span>
          </label>
          <small class="form-error" id="field-privacy-error" aria-live="polite"><?= isset($contactErrors['privacy']) ? e($contactErrors['privacy']) : '' ?></small>
        </div>

        <div class="form-honeypot" aria-hidden="true">
          <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
        </div>

        <?php if ($turnstileEnabled): ?>
          <div class="turnstile-shell<?= isset($contactErrors['turnstile']) ? ' has-error' : '' ?>">
            <div
              class="cf-turnstile"
              data-sitekey="<?= e(turnstile_site_key()) ?>"
              data-theme="light"
              data-language="es"
            ></div>
            <?php if (isset($contactErrors['turnstile'])): ?>
              <p class="form-error" aria-live="polite"><?= e($contactErrors['turnstile']) ?></p>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <div class="form-actions">
          <button class="btn btn-primary contact-submit" type="submit" data-contact-submit>
            <span class="contact-submit__spinner" aria-hidden="true"></span>
            <span class="contact-submit__label" data-idle-label="Enviar consulta" data-loading-label="Enviando…">Enviar consulta</span>
          </button>
          <p class="form-note">Si preferís una respuesta más rápida, también podés escribir por WhatsApp.</p>
        </div>
      </form>

      <?php endif; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <span class="section-kicker">Canales de contacto</span>
    <h2>Elegí la vía más cómoda para avanzar</h2>
    <p class="section-lead">
      La página está pensada para que la conversación arranque rápido y con contexto. Cuanto más nos cuentes del negocio, mejor.
    </p>

    <div class="contact-grid">
      <?php if ($site['whatsapp_number'] !== ''): ?>
        <article class="contact-card">
          <h3>WhatsApp</h3>
          <p><a class="contact-link" href="<?= e(whatsapp_url('Hola, quiero conocer FLUS y coordinar una demo.')) ?>" target="_blank" rel="noopener" data-track-event="click_whatsapp" data-track-label="Tarjeta contacto WhatsApp">Iniciar conversación</a></p>
          <p class="muted">La vía más rápida para una primera charla comercial.</p>
        </article>
      <?php endif; ?>

      <?php if ($site['contact_email'] !== ''): ?>
        <article class="contact-card">
          <h3>Correo</h3>
          <p><a class="contact-link" href="mailto:<?= e($site['contact_email']) ?>" data-track-event="click_contact" data-track-label="Tarjeta contacto correo"><?= e($site['contact_email']) ?></a></p>
          <p class="muted">Útil para consultas, seguimiento y coordinación de demo.</p>
        </article>
      <?php endif; ?>

      <?php if ($site['contact_phone'] !== ''): ?>
        <article class="contact-card">
          <h3>Teléfono</h3>
          <p><a class="contact-link" href="tel:<?= e(phone_href($site['contact_phone'])) ?>" data-track-event="click_contact" data-track-label="Tarjeta contacto telefono"><?= e($site['contact_phone']) ?></a></p>
          <p class="muted">Si preferís una primera conversación directa.</p>
        </article>
      <?php endif; ?>

      <article class="contact-card">
        <h3>Qué conviene contarnos</h3>
        <p>Tu rubro, cómo trabajan hoy ventas y stock, y qué parte de la operación quieren ordenar primero.</p>
      </article>
    </div>
  </div>
</section>

<section class="section section-dark">
  <div class="container split-grid">
    <div class="surface-card">
      <h3>Qué revisar antes de la demo</h3>
      <ul class="plain-list">
        <li>Cómo venden hoy y qué pasa en caja.</li>
        <li>Qué nivel de control real tienen sobre stock.</li>
        <li>Cómo siguen clientes y comprobantes.</li>
        <li>Cuánto trabajo manual depende de planillas.</li>
      </ul>
    </div>

    <div class="surface-card">
      <h3>Páginas que te conviene mirar</h3>
      <p>
        Si querés llegar mejor orientado a la conversación, podés revisar primero las páginas que explican
        cómo trabaja FLUS en cada parte de la operación.
      </p>
      <ul class="link-list">
        <li><a href="<?= e(site_url('sistema-de-gestion.php')) ?>">Sistema de gestión</a></li>
        <li><a href="<?= e(site_url('sistema-pos.php')) ?>">Caja y ventas</a></li>
        <li><a href="<?= e(site_url('control-de-stock.php')) ?>">Control de stock</a></li>
        <li><a href="<?= e(site_url('facturacion.php')) ?>">Facturación</a></li>
      </ul>
    </div>
  </div>
</section>

<?php if ($turnstileEnabled): ?>
  <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
