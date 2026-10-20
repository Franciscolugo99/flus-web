<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/legal.php';
require_once __DIR__ . '/includes/bot-legal.php';

$sitePrivacyReady = flus_legal_ready();
$botDocumentsReady = flus_bot_docs_ready();
$pageTitle = 'Centro de Confianza | Privacidad y seguridad · FLUS';
$pageDescription = 'Información pública sobre privacidad, seguridad, inteligencia artificial y solicitudes de eliminación de datos en los servicios FLUS.';
$pageCanonical = page_url('centro-de-confianza.php');
$pageStyles = ['css/trust-center.css'];
$pageBreadcrumbs = [
    ['name' => 'Inicio', 'url' => page_url()],
    ['name' => 'Centro de Confianza', 'url' => $pageCanonical],
];
require __DIR__ . '/includes/header.php';
?>
<section class="tc-hero">
  <div class="container tc-grid">
    <div>
      <span class="tc-kicker">FLUS · Transparencia y confianza</span>
      <h1>La confianza también <em>se construye con información clara.</em></h1>
      <p class="tc-hero__lead">Conocé cómo abordamos la privacidad, qué información estamos documentando sobre FLUS Bot y dónde consultar o solicitar la eliminación de tus datos.</p>
      <div class="tc-actions">
        <a class="tc-actions__main" href="#documentacion">Explorar documentación ↓</a>
        <a class="tc-actions__sub" href="<?= e(site_url('contacto.php')) ?>">Contactar a FLUS</a>
      </div>
    </div>
    <aside class="tc-status" aria-label="Estado de documentación">
      <span class="tc-status__label">Estado del centro</span>
      <h2>Información pública, sin promesas vacías.</h2>
      <p>Algunos documentos requieren validar la identidad del responsable y el funcionamiento real del servicio antes de su publicación.</p>
      <ul class="tc-status__list">
        <li>Canal disponible para consultas sobre datos.</li>
        <li>Procedimiento de solicitud de eliminación.</li>
        <li>Políticas específicas de FLUS Bot sujetas a revisión.</li>
      </ul>
    </aside>
  </div>
</section>

<section class="tc-section" id="documentacion" aria-labelledby="tc-documentacion-titulo">
  <div class="container">
    <div class="tc-head">
      <span class="tc-eyebrow">Documentación FLUS</span>
      <h2 id="tc-documentacion-titulo">Privacidad y uso responsable, en un solo lugar.</h2>
      <p>Publicamos la información disponible e identificamos los documentos que todavía necesitan revisión. FLUS es una marca comercial; no se presenta aquí como una sociedad constituida.</p>
    </div>
    <div class="tc-cards">
      <article class="tc-card">
        <span class="tc-card__icon" aria-hidden="true">◈</span>
        <h3>Privacidad del sitio</h3>
        <p>Información sobre el uso de datos al navegar por flus.com.ar y contactar al equipo.</p>
        <div class="tc-card__footer">
        <?php if ($sitePrivacyReady): ?>
          <a href="<?= e(site_url('politica-de-privacidad.php')) ?>">Leer política →</a>
        <?php else: ?>
          <span class="tc-card__badge">Pendiente de completar datos del responsable</span>
        <?php endif; ?>
        </div>
      </article>
      <article class="tc-card">
        <span class="tc-card__icon" aria-hidden="true">◎</span>
        <h3>Privacidad de FLUS Bot</h3>
        <p>Mensajes de WhatsApp, datos de clientes, proveedores y alcance de la automatización.</p>
        <div class="tc-card__footer">
        <?php if ($botDocumentsReady): ?>
          <a href="<?= e(site_url('flus-bot-privacidad.php')) ?>">Leer política →</a>
        <?php else: ?>
          <span class="tc-card__badge">Documento en revisión legal y técnica</span>
        <?php endif; ?>
        </div>
      </article>
      <article class="tc-card">
        <span class="tc-card__icon" aria-hidden="true">↗</span>
        <h3>Eliminación de datos</h3>
        <p>Cómo enviar una solicitud, qué información incluir y cómo se verifica la identidad.</p>
        <div class="tc-card__footer"><a href="<?= e(site_url('flus-bot-eliminar-datos.php')) ?>">Ver instrucciones →</a></div>
      </article>
      <article class="tc-card">
        <span class="tc-card__icon" aria-hidden="true">⌘</span>
        <h3>Inteligencia artificial</h3>
        <p>FLUS Bot puede utilizar modelos externos como DeepSeek si la función está habilitada. La revisión humana y la verificación de información siguen siendo importantes.</p>
        <div class="tc-card__footer"><span class="tc-card__badge">Alcance documentado antes del lanzamiento</span></div>
      </article>
      <article class="tc-card">
        <span class="tc-card__icon" aria-hidden="true">◇</span>
        <h3>Condiciones del servicio</h3>
        <p>Condiciones de uso de FLUS Bot, responsabilidades de cada empresa y canales de asistencia.</p>
        <div class="tc-card__footer">
        <?php if ($botDocumentsReady): ?>
          <a href="<?= e(site_url('flus-bot-terminos.php')) ?>">Leer condiciones →</a>
        <?php else: ?>
          <span class="tc-card__badge">Pendiente de revisión y aprobación</span>
        <?php endif; ?>
        </div>
      </article>
      <article class="tc-card">
        <span class="tc-card__icon" aria-hidden="true">☷</span>
        <h3>Cookies y preferencias</h3>
        <p>Consultá cómo se usan las cookies del sitio y cómo controlar las opciones no esenciales.</p>
        <div class="tc-card__footer"><a href="<?= e(site_url('politica-de-cookies.php')) ?>">Ver política de cookies →</a></div>
      </article>
    </div>
  </div>
</section>

<section class="tc-section tc-section--soft" aria-labelledby="tc-roles-titulo">
  <div class="container">
    <div class="tc-head">
      <span class="tc-eyebrow">FLUS Bot · Información para comercios</span>
      <h2 id="tc-roles-titulo">¿Quién responde por los datos de una conversación?</h2>
      <p>La empresa que atiende a sus clientes mediante WhatsApp y FLUS como proveedor tecnológico pueden tener responsabilidades diferentes. Cada implementación debe informar sus roles reales.</p>
    </div>
    <div class="tc-split">
      <div class="tc-panel">
        <h3>Comercio que utiliza el bot</h3>
        <p>Debe identificar sus canales de atención, explicar el uso que hace de los datos de sus clientes y facilitar una vía para ejercer sus derechos.</p>
        <a href="<?= e(site_url('flus-bot-eliminar-datos.php')) ?>">Cómo consultar sobre datos →</a>
      </div>
      <div class="tc-panel">
        <h3>FLUS como proveedor</h3>
        <p>La documentación específica describirá cómo funciona el servicio y qué proveedores pueden intervenir, de acuerdo con su configuración real y los contratos aplicables.</p>
        <a href="<?= e(site_url('contacto.php')) ?>">Hablar con FLUS →</a>
      </div>
    </div>
    <div class="tc-note" style="margin-top:24px">
      <strong>Transparencia antes que marketing.</strong>
      Este centro no certifica que FLUS Bot esté aprobado por Meta, que se encuentre listo para producción ni que tenga certificaciones de seguridad. Las políticas de uso del bot permanecerán en preparación hasta su revisión técnica y legal.
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
