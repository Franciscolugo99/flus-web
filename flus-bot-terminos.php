<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/bot-legal.php';

// Mantener inaccesible hasta aprobar condiciones y roles comerciales reales.
flus_bot_require_docs_ready();

$pageTitle = 'Condiciones de uso | FLUS Bot';
$pageDescription = 'Alcance y condiciones generales del servicio de atención automatizada FLUS Bot.';
$pageCanonical = page_url('flus-bot-terminos.php');
$pageStyles = ['css/trust-center.css'];
$pageBreadcrumbs = [
    ['name' => 'Inicio', 'url' => page_url()],
    ['name' => 'Centro de Confianza', 'url' => page_url('centro-de-confianza.php')],
    ['name' => 'Condiciones de FLUS Bot', 'url' => $pageCanonical],
];
require __DIR__ . '/includes/header.php';
?>
<section class="container tc-legal">
  <span class="tc-eyebrow">FLUS Bot · Documento específico</span>
  <h1>Condiciones generales de uso</h1>
  <p class="tc-legal__intro">FLUS Bot es una herramienta de apoyo a la atención comercial por WhatsApp. Las funciones disponibles y su alcance dependen de la configuración acordada con cada comercio.</p>

  <h2>1. Prestador</h2>
  <p>FLUS es una marca comercial utilizada por <?= e(flus_legal('legal_name')) ?>, con domicilio en <?= e(flus_legal('legal_address')) ?>. Contacto: <a href="mailto:<?= e((string)$site['contact_email']) ?>"><?= e((string)$site['contact_email']) ?></a>.</p>

  <h2>2. Alcance del servicio</h2>
  <p>El servicio puede asistir en la lectura de mensajes, búsqueda de información comercial y generación de respuestas. Las funciones concretas, las condiciones económicas y los niveles de soporte se acuerdan individualmente con la empresa contratante.</p>
  <p>Las respuestas automáticas, especialmente cuando interviene inteligencia artificial, pueden contener errores o interpretaciones incompletas. El comercio debe disponer de un canal de atención y revisar la información relevante antes de tomar decisiones.</p>

  <h2>3. Responsabilidades</h2>
  <p><?= e((string)$site['bot_responsibilities_description']) ?></p>
  <p>Las empresas usuarias deben atender las obligaciones que les correspondan sobre datos personales, consentimiento y mensajes comerciales. Ninguna parte está autorizada a utilizar el servicio para fines ilícitos o contrarios a las reglas de WhatsApp Business.</p>

  <h2>4. Privacidad y proveedores</h2>
  <p>El tratamiento de información se describe en la <a href="<?= e(site_url('flus-bot-privacidad.php')) ?>">política de privacidad de FLUS Bot</a> y en el aviso aplicable de cada comercio. La disponibilidad también depende de servicios de terceros y sus condiciones.</p>

  <h2>5. Consultas</h2>
  <p>Ante dudas sobre el servicio, dirigite a <a href="<?= e(site_url('contacto.php')) ?>">Contacto FLUS</a>. Las condiciones particulares de cada contrato prevalecen en las materias que regule expresamente.</p>
  <p class="tc-meta">Documento sujeto a aprobación comercial y legal · Versión técnica 20/10/2026</p>
  <p><a href="<?= e(site_url('centro-de-confianza.php')) ?>">← Volver al Centro de Confianza</a></p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
