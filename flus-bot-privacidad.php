<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/bot-legal.php';

// NO PUBLICAR EN META hasta acreditar responsable, domicilio,
// infraestructuras, roles, plazos reales y aprobación jurídica.
flus_bot_require_docs_ready();

$pageTitle = 'Política de privacidad | FLUS Bot';
$pageDescription = 'Tratamiento de datos al utilizar FLUS Bot con WhatsApp Business Platform.';
$pageCanonical = page_url('flus-bot-privacidad.php');
$pageStyles = ['css/trust-center.css'];
$pageBreadcrumbs = [
    ['name' => 'Inicio', 'url' => page_url()],
    ['name' => 'Centro de Confianza', 'url' => page_url('centro-de-confianza.php')],
    ['name' => 'Privacidad de FLUS Bot', 'url' => $pageCanonical],
];
require __DIR__ . '/includes/header.php';
?>
<section class="container tc-legal">
  <span class="tc-eyebrow">FLUS Bot · Documento específico</span>
  <h1>Política de privacidad de FLUS Bot</h1>
  <p class="tc-legal__intro">Este documento explica qué datos pueden intervenir cuando un comercio utiliza FLUS Bot para atender consultas por WhatsApp Business Platform.</p>

  <h2>1. Identificación y responsables</h2>
  <p>FLUS es una marca comercial utilizada por <strong><?= e(flus_legal('legal_name')) ?></strong>, con domicilio en <?= e(flus_legal('legal_address')) ?>. Contacto: <a href="mailto:<?= e((string)$site['contact_email']) ?>"><?= e((string)$site['contact_email']) ?></a>.</p>
  <p><?= e((string)$site['bot_responsibilities_description']) ?></p>
  <p>Si conversás con un comercio que utiliza FLUS Bot, consultá también el aviso de privacidad de ese comercio.</p>

  <h2>2. Datos involucrados y finalidad</h2>
  <p>Según las funciones habilitadas para el comercio, el servicio puede recibir el número de teléfono del remitente, identificadores técnicos de WhatsApp, mensajes, fecha y hora, estados de entrega y contenido multimedia que la persona decida enviar.</p>
  <p>Estos datos se utilizan para tramitar la consulta, localizar productos o información autorizada, producir respuestas, realizar seguimiento cuando corresponda, solucionar errores y proteger la operación. No todas las funciones se encuentran necesariamente habilitadas en cada comercio.</p>

  <h2>3. Servicios externos y automatización</h2>
  <p>WhatsApp Business Platform es un servicio de Meta. Cuando la función de inteligencia artificial esté habilitada, determinados mensajes o extractos pertinentes pueden enviarse a un proveedor de modelos como DeepSeek para elaborar una respuesta. No debe enviarse información sensible innecesaria.</p>
  <p>Infraestructura, proveedores y ubicación aplicables a este despliegue: <?= e((string)$site['bot_infrastructure_description']) ?></p>

  <h2>4. Conservación y eliminación</h2>
  <p>Reglas efectivas de conservación aplicables a este despliegue: <?= e((string)$site['bot_retention_description']) ?></p>
  <p>Podés solicitar acceso, rectificación o supresión siguiendo las <a href="<?= e(site_url('flus-bot-eliminar-datos.php')) ?>">instrucciones de privacidad de FLUS Bot</a>. Una solicitud no supone eliminación instantánea: la identidad y las obligaciones aplicables deben verificarse.</p>

  <h2>5. Tus derechos y cambios</h2>
  <p>Las personas pueden ejercer los derechos que reconoce la normativa aplicable, incluida la Ley argentina 25.326 cuando corresponda. Para consultas escribí a <a href="mailto:<?= e((string)$site['contact_email']) ?>"><?= e((string)$site['contact_email']) ?></a>.</p>
  <p>Las modificaciones de esta política se indicarán en esta página antes de que se utilice la nueva versión para informar a usuarios.</p>
  <p class="tc-meta">Documento sujeto a datos reales y aprobación humana · Versión técnica 20/10/2026</p>
  <p><a href="<?= e(site_url('centro-de-confianza.php')) ?>">← Volver al Centro de Confianza</a></p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
