<?php
require_once __DIR__ . '/includes/bootstrap.php';
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

$contactForm = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'company' => '',
    'message' => '',
];
$contactErrors = [];
$contactNotice = null;
$contactNoticeType = 'success';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $contactForm = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'phone' => trim((string) ($_POST['phone'] ?? '')),
        'company' => trim((string) ($_POST['company'] ?? '')),
        'message' => trim((string) ($_POST['message'] ?? '')),
    ];
    $websiteField = trim((string) ($_POST['website'] ?? ''));

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

    if ($websiteField !== '') {
        $contactNotice = 'Gracias. Recibimos tu consulta.';
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
            'Mensaje:',
            $contactForm['message'],
        ];
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
            $contactForm = [
                'name' => '',
                'email' => '',
                'phone' => '',
                'company' => '',
                'message' => '',
            ];
        } else {
            $contactNotice = 'No pudimos enviar el mensaje en este momento. Probá por WhatsApp o escribinos a info@flus.com.ar.';
            $contactNoticeType = 'error';
        }
    } else {
        $contactNotice = 'Revisá los campos marcados para poder enviar la consulta.';
        $contactNoticeType = 'error';
    }
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
          <a class="btn btn-primary" href="<?= e(whatsapp_url('Hola, quiero conocer FLUS y coordinar una demo.')) ?>" target="_blank" rel="noopener">Hablar por WhatsApp</a>
        <?php endif; ?>
        <?php if ($site['contact_email'] !== ''): ?>
          <a class="btn btn-secondary" href="mailto:<?= e($site['contact_email']) ?>">Enviar correo</a>
        <?php endif; ?>
      </div>
    </div>

    <aside class="hero-panel">
      <h2>Qué podés esperar de la conversación</h2>
      <ul class="check-list">
        <li>Revisar si FLUS encaja con tu operación.</li>
        <li>Mirar ventas, stock, caja y facturación con foco real.</li>
        <li>Entender dónde habría más orden y trazabilidad.</li>
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
            <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle class="form-success-icon__circle" cx="24" cy="24" r="21" stroke="currentColor" stroke-width="2.5"/>
              <path class="form-success-icon__check" d="M14 24.5l7.5 7.5 12.5-14" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <h3 class="form-success-title">¡Consulta enviada!</h3>
          <p class="form-success-text"><?= e($contactNotice) ?></p>
          <?php if ($site['whatsapp_number'] !== ''): ?>
            <a class="btn btn-primary" href="<?= e(whatsapp_url('Hola, quiero conocer FLUS y coordinar una demo.')) ?>" target="_blank" rel="noopener">
              También podés escribir por WhatsApp
            </a>
          <?php endif; ?>
        </div>
      <?php else: ?>

      <form class="contact-form" data-contact-form action="<?= e(site_url('contacto.php')) ?>#formulario-contacto" method="post" novalidate>
        <div class="form-grid">

          <div class="form-field form-field--float<?= $contactForm['name'] !== '' ? ' has-value' : '' ?><?= isset($contactErrors['name']) ? ' has-error' : '' ?>">
            <input
              class="form-input<?= isset($contactErrors['name']) ? ' is-invalid' : '' ?>"
              type="text" name="name" id="field-name"
              maxlength="120" value="<?= e($contactForm['name']) ?>"
              autocomplete="name" required placeholder=" "
              data-validate="required" data-error-required="Ingresá tu nombre."
            >
            <label class="form-label" for="field-name">Nombre <span class="form-required" aria-hidden="true">*</span></label>
            <small class="form-error" aria-live="polite"><?= isset($contactErrors['name']) ? e($contactErrors['name']) : '' ?></small>
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
            >
            <label class="form-label" for="field-email">Correo <span class="form-required" aria-hidden="true">*</span></label>
            <small class="form-error" aria-live="polite"><?= isset($contactErrors['email']) ? e($contactErrors['email']) : '' ?></small>
          </div>

          <div class="form-field form-field--float<?= $contactForm['phone'] !== '' ? ' has-value' : '' ?><?= isset($contactErrors['phone']) ? ' has-error' : '' ?>">
            <input
              class="form-input<?= isset($contactErrors['phone']) ? ' is-invalid' : '' ?>"
              type="text" name="phone" id="field-phone"
              maxlength="60" value="<?= e($contactForm['phone']) ?>"
              autocomplete="tel" placeholder=" "
            >
            <label class="form-label" for="field-phone">Teléfono</label>
            <small class="form-error" aria-live="polite"><?= isset($contactErrors['phone']) ? e($contactErrors['phone']) : '' ?></small>
          </div>

          <div class="form-field form-field--float<?= $contactForm['company'] !== '' ? ' has-value' : '' ?><?= isset($contactErrors['company']) ? ' has-error' : '' ?>">
            <input
              class="form-input<?= isset($contactErrors['company']) ? ' is-invalid' : '' ?>"
              type="text" name="company" id="field-company"
              maxlength="120" value="<?= e($contactForm['company']) ?>"
              autocomplete="organization" placeholder=" "
            >
            <label class="form-label" for="field-company">Negocio</label>
            <small class="form-error" aria-live="polite"><?= isset($contactErrors['company']) ? e($contactErrors['company']) : '' ?></small>
          </div>

        </div>

        <div class="form-field form-field--float form-field--textarea<?= $contactForm['message'] !== '' ? ' has-value' : '' ?><?= isset($contactErrors['message']) ? ' has-error' : '' ?>">
          <textarea
            class="form-input form-textarea<?= isset($contactErrors['message']) ? ' is-invalid' : '' ?>"
            name="message" id="field-message"
            rows="5" maxlength="3000" required placeholder=" "
            data-validate="required" data-error-required="Contanos un poco sobre tu consulta."
            data-char-counter
          ><?= e($contactForm['message']) ?></textarea>
          <label class="form-label" for="field-message">Mensaje <span class="form-required" aria-hidden="true">*</span></label>
          <div class="form-field-footer">
            <small class="form-error" aria-live="polite"><?= isset($contactErrors['message']) ? e($contactErrors['message']) : '' ?></small>
            <span class="form-char-counter" data-max="3000">
              <span class="form-char-counter__current"><?= mb_strlen($contactForm['message']) ?></span>&nbsp;/ 3000
            </span>
          </div>
        </div>

        <div class="form-honeypot" aria-hidden="true">
          <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
        </div>

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
          <p><a class="contact-link" href="<?= e(whatsapp_url('Hola, quiero conocer FLUS y coordinar una demo.')) ?>" target="_blank" rel="noopener">Iniciar conversación</a></p>
          <p class="muted">La vía más rápida para una primera charla comercial.</p>
        </article>
      <?php endif; ?>

      <?php if ($site['contact_email'] !== ''): ?>
        <article class="contact-card">
          <h3>Correo</h3>
          <p><a class="contact-link" href="mailto:<?= e($site['contact_email']) ?>"><?= e($site['contact_email']) ?></a></p>
          <p class="muted">Útil para consultas, seguimiento y coordinación de demo.</p>
        </article>
      <?php endif; ?>

      <?php if ($site['contact_phone'] !== ''): ?>
        <article class="contact-card">
          <h3>Teléfono</h3>
          <p><a class="contact-link" href="tel:<?= e(phone_href($site['contact_phone'])) ?>"><?= e($site['contact_phone']) ?></a></p>
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
        <li><a href="<?= e(site_url('sistema-pos.php')) ?>">Sistema POS</a></li>
        <li><a href="<?= e(site_url('control-de-stock.php')) ?>">Control de stock</a></li>
        <li><a href="<?= e(site_url('facturacion.php')) ?>">Facturación</a></li>
      </ul>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
