<?php
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Solicitar acceso o eliminación de datos | FLUS Bot';
$pageDescription = 'Cómo solicitar acceso, corrección o eliminación de información vinculada a conversaciones con FLUS Bot.';
$pageCanonical = page_url('flus-bot-eliminar-datos.php');
$pageStyles = ['css/trust-center.css'];
$pageBreadcrumbs = [
    ['name' => 'Inicio', 'url' => page_url()],
    ['name' => 'Centro de Confianza', 'url' => page_url('centro-de-confianza.php')],
    ['name' => 'Solicitar eliminación de datos', 'url' => $pageCanonical],
];
require __DIR__ . '/includes/header.php';
?>
<section class="container tc-legal">
  <span class="tc-eyebrow">FLUS Bot · Privacidad</span>
  <h1>Solicitudes sobre tus datos personales</h1>
  <p class="tc-legal__intro">Si interactuaste con un comercio que utiliza FLUS Bot, podés consultar cómo solicitar acceso, corrección o eliminación de información relacionada con esa conversación.</p>

  <h2>1. Cómo presentar una solicitud</h2>
  <p>Escribí a <a href="mailto:<?= e((string)$site['contact_email']) ?>?subject=<?= e(rawurlencode('Solicitud de datos - FLUS Bot')) ?>"><?= e((string)$site['contact_email']) ?></a> con el asunto <strong>Solicitud de datos — FLUS Bot</strong>. También podés usar la <a href="<?= e(site_url('contacto.php')) ?>">página de contacto</a>.</p>
  <p>Indicá qué comercio atendió tu conversación, el número de WhatsApp involucrado y si solicitás acceso, rectificación o supresión. No envíes contraseñas, códigos de acceso ni documentación sensible en el primer mensaje.</p>

  <h2>2. Qué sucede después</h2>
  <ol>
    <li>Se identifica el comercio y la conversación a la que se refiere la solicitud.</li>
    <li>Cuando sea necesario, se verifica la legitimidad del pedido por un medio proporcionado.</li>
    <li>Se determina quién es responsable del tratamiento en esa implementación y se coordina la respuesta correspondiente.</li>
    <li>Se informa el resultado, los datos alcanzados y las eventuales restricciones legales aplicables.</li>
  </ol>
  <p>El envío de una solicitud <strong>no elimina los datos automáticamente</strong>. La supresión puede estar limitada por obligaciones legales u otros motivos previstos por la normativa aplicable. Las solicitudes se atienden dentro de los plazos legales que correspondan.</p>

  <h2>3. Si sos cliente de un comercio</h2>
  <p>También podés dirigirte directamente al comercio con el que hablaste. La empresa que atiende la conversación puede ser la responsable de decidir cómo trata los datos, mientras FLUS presta la tecnología. El papel concreto de cada parte depende de la implementación.</p>

  <div class="tc-rule">
    <strong>¿Necesitás ayuda con un pedido de privacidad?</strong>
    <p>Contactanos desde <a href="<?= e(site_url('contacto.php')) ?>">flus.com.ar/contacto.php</a>. No compartas aquí información de otras personas.</p>
  </div>
  <p class="tc-meta">Documento informativo para gestionar solicitudes · Actualizado el 20/10/2026</p>
  <p><a href="<?= e(site_url('centro-de-confianza.php')) ?>">← Volver al Centro de Confianza</a></p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
