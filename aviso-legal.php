<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/legal.php';

// Fallo seguro: si falta cualquier dato legal requerido, esta pagina no se
// publica. Responde 503 en lugar de mostrar informacion incompleta.
flus_legal_require_ready();

$pageTitle = 'Aviso legal | FLUS';
$pageDescription = 'Aviso legal e identificación del titular del sitio flus.com.ar, condiciones de uso y alcance de la información publicada.';
$pageCanonical = page_url('aviso-legal.php');
$pageStyles = ['css/content-seo.css'];
$pageBreadcrumbs = [
    ['name' => 'Inicio', 'url' => page_url()],
    ['name' => 'Aviso legal', 'url' => $pageCanonical],
];

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container" style="max-width:820px;">
    <span class="eyebrow">Información legal</span>
    <h1>Aviso legal</h1>
    <p>
      Este aviso regula el uso del sitio <strong><?= e($site['domain']) ?></strong>.
      Al navegar por el sitio aceptás las condiciones que se describen a continuación.
    </p>
  </div>
</section>

<section class="section">
  <div class="container legal-doc">

    <h2>1. Titular del sitio</h2>
    <p>El sitio <?= e($site['domain']) ?> es operado por:</p>
    <ul class="plain-list">
      <li><strong>Razón social / titular:</strong> <?= e(flus_legal('legal_name')) ?></li>
      <li><strong>CUIT:</strong> <?= e(flus_legal('legal_cuit')) ?></li>
      <li><strong>Domicilio legal:</strong> <?= e(flus_legal('legal_address')) ?></li>
      <li><strong>Condición frente al IVA:</strong> <?= e(flus_legal('legal_tax_status')) ?></li>
      <li><strong>Correo de contacto:</strong> <?= e($site['contact_email']) ?></li>
      <li><strong>Teléfono / WhatsApp comercial:</strong> <?= e($site['contact_phone']) ?></li>
      <li><strong>Inscripción registral:</strong> <?= e(flus_legal('legal_registration')) ?></li>
    </ul>

    <h2>2. Objeto del sitio</h2>
    <p>
      El sitio tiene como finalidad presentar FLUS, un sistema de gestión comercial para
      comercios (ventas, caja, stock, compras, clientes y facturación), publicar
      información sobre sus ediciones <strong>FLUS Web</strong> y <strong>FLUS Server</strong>,
      y permitir que las personas interesadas soliciten información o una demostración.
    </p>
    <p>
      La información publicada tiene carácter informativo y comercial. No constituye una
      oferta vinculante ni un contrato: el alcance, los precios y las condiciones de cada
      implementación se acuerdan de forma particular con cada cliente.
    </p>

    <h2>3. Condiciones de uso</h2>
    <ul class="plain-list">
      <li>El usuario se compromete a utilizar el sitio de forma lícita y a no realizar acciones que puedan dañarlo, sobrecargarlo o interferir con su funcionamiento.</li>
      <li>No está permitido intentar acceder a áreas restringidas, ni extraer, reproducir o reutilizar el contenido del sitio sin autorización.</li>
      <li>Los datos que el usuario envíe a través de los formularios deben ser veraces y corresponder a su propia persona o a la organización que representa.</li>
    </ul>

    <h2>4. Propiedad intelectual</h2>
    <p>
      La marca FLUS, los textos, las imágenes, los gráficos y el código de este sitio
      pertenecen a su titular o se utilizan con autorización. Queda prohibida su
      reproducción total o parcial sin consentimiento previo por escrito.
    </p>

    <h2>5. Precios y disponibilidad</h2>
    <p>
      Los valores publicados son de referencia, están expresados en pesos argentinos (ARS)
      y pueden variar sin previo aviso por cambios en el alcance del servicio, en la
      configuración contratada o por condiciones de mercado. El precio aplicable es el que
      se informa al momento de contratar.
    </p>

    <h2>6. Exclusiones de responsabilidad</h2>
    <p>
      El titular procura que la información del sitio sea correcta y esté actualizada, pero
      no garantiza la ausencia de errores ni la disponibilidad ininterrumpida del servicio.
      No se responsabiliza por daños derivados del uso del sitio, de la imposibilidad de
      acceder a él, ni del contenido de sitios de terceros a los que se enlace.
    </p>

    <h2>7. Enlaces a sitios de terceros</h2>
    <p>
      El sitio puede incluir enlaces a servicios externos, como la aplicación de
      demostración, WhatsApp o perfiles en redes sociales. El titular no controla esos
      sitios y no responde por sus contenidos ni por sus políticas de privacidad.
    </p>

    <h2>8. Protección de datos personales</h2>
    <p>
      El tratamiento de los datos personales que el usuario proporciona se describe en la
      <a href="<?= e(site_url('politica-de-privacidad.php')) ?>">Política de privacidad</a>.
      El uso de cookies y tecnologías similares se describe en la
      <a href="<?= e(site_url('politica-de-cookies.php')) ?>">Política de cookies</a>.
    </p>

    <h2>9. Legislación aplicable y jurisdicción</h2>
    <p>
      Este aviso se rige por las leyes de la República Argentina. Cualquier controversia
      derivada del uso del sitio se someterá a los tribunales competentes del domicilio
      legal del titular indicado en el punto 1, salvo que la normativa de defensa del
      consumidor disponga otra cosa.
    </p>

    <h2>10. Modificaciones</h2>
    <p>
      El titular puede actualizar este aviso en cualquier momento. La versión vigente es la
      publicada en esta página. Última actualización: <strong>05/10/2026</strong>.
    </p>

    <p style="margin-top:36px;">
      Ante cualquier consulta sobre este aviso podés escribir a
      <a href="mailto:<?= e($site['contact_email']) ?>"><?= e($site['contact_email']) ?></a><?php if ($site['whatsapp_number'] !== ''): ?>
      o por <a href="<?= e(whatsapp_url('Hola, tengo una consulta sobre el aviso legal de FLUS.')) ?>" target="_blank" rel="noopener" data-track-event="click_whatsapp" data-track-label="Aviso legal WhatsApp">WhatsApp</a><?php endif; ?>.
    </p>

    <p class="legal-doc__back"><a href="<?= e(site_url()) ?>">← Volver al inicio</a></p>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
