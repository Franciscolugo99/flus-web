<?php
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Política de privacidad | FLUS';
$pageDescription = 'Qué datos recopila flus.com.ar, con qué finalidad, por cuánto tiempo se conservan y cómo ejercer tus derechos sobre ellos.';
$pageCanonical = page_url('politica-de-privacidad.php');
$pageStyles = ['css/content-seo.css'];
$pageBreadcrumbs = [
    ['name' => 'Inicio', 'url' => page_url()],
    ['name' => 'Política de privacidad', 'url' => $pageCanonical],
];

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container" style="max-width:820px;">
    <span class="eyebrow">Información legal</span>
    <h1>Política de privacidad</h1>
    <p>
      Esta política describe qué datos personales se recopilan a través de
      <strong><?= e($site['domain']) ?></strong>, con qué finalidad, sobre qué base legal,
      por cuánto tiempo se conservan y cómo podés ejercer tus derechos.
    </p>
  </div>
</section>

<section class="section">
  <div class="container legal-doc">

    <div class="form-alert form-alert-error" role="note" style="margin-bottom:28px;">
      <strong>Datos pendientes de completar por el titular.</strong>
      Lo marcado como <em>[COMPLETAR]</em> no fue inventado: debe cargarlo el propietario de
      FLUS antes de publicar esta página como definitiva.
    </div>

    <h2>1. Responsable del tratamiento</h2>
    <ul class="plain-list">
      <li><strong>Responsable:</strong> [COMPLETAR — razón social o nombre y apellido]</li>
      <li><strong>CUIT:</strong> [COMPLETAR — CUIT]</li>
      <li><strong>Domicilio:</strong> [COMPLETAR — domicilio legal completo]</li>
      <li><strong>Correo para ejercer derechos:</strong> <?= e($site['contact_email']) ?></li>
    </ul>

    <h2>2. Qué datos recopilamos realmente</h2>
    <p>
      Esta política describe únicamente el tratamiento que efectivamente realiza el sitio.
      No incluye categorías de datos que el sitio no recopila.
    </p>

    <h3>2.1 Datos que nos enviás en el formulario de contacto</h3>
    <p>Cuando completás el formulario de contacto, se recopilan los siguientes datos:</p>
    <ul class="plain-list">
      <li><strong>Nombre</strong> (obligatorio) — para dirigirnos a vos.</li>
      <li><strong>Correo electrónico</strong> (obligatorio) — para responderte.</li>
      <li><strong>Teléfono</strong> (opcional) — para contactarte si lo preferís.</li>
      <li><strong>Nombre del negocio</strong> (opcional) — para entender tu contexto comercial.</li>
      <li><strong>Mensaje</strong> (obligatorio) — para conocer tu consulta.</li>
    </ul>
    <p>
      Además, el servidor registra de forma transitoria la dirección IP del envío con el
      único fin de limitar la cantidad de envíos por minuto y prevenir abuso del formulario
      (ver punto 5).
    </p>

    <h3>2.2 Datos de contacto directo</h3>
    <p>
      Si nos escribís por WhatsApp, correo electrónico o teléfono, trataremos los datos que
      voluntariamente nos proporciones en esa conversación (por ejemplo tu nombre, tu número
      y el contenido de tu consulta). Esos canales tienen sus propias políticas de
      privacidad: WhatsApp es operado por Meta Platforms Inc. y el correo por tu proveedor.
    </p>

    <h3>2.3 Datos de uso y analítica (solo con tu consentimiento)</h3>
    <p>
      La analítica del sitio <strong>no se activa hasta que la aceptás expresamente</strong>
      en el aviso de cookies. Si la aceptás, se puede recopilar:
    </p>
    <ul class="plain-list">
      <li><strong>Analítica propia (en nuestro servidor):</strong> página visitada, título de la página, sitio de procedencia, parámetros de campaña (utm), tipo de dispositivo, identificador de sesión de la pestaña, agente de usuario y un <em>hash irreversible</em> de la dirección IP (no la IP en texto plano). Interacciones registradas: visitas de página, clics en WhatsApp, clics en enlaces de contacto, clics de demostración, envíos del formulario y consultas de precios.</li>
      <li><strong>Google Analytics 4 (Google Ireland Limited / Google LLC):</strong> identificadores de cliente y de sesión, páginas vistas, eventos de interacción, datos aproximados de ubicación (a nivel ciudad/país) y datos del dispositivo y del navegador. Google actúa como encargado del tratamiento.</li>
    </ul>
    <p>
      Si <strong>rechazás</strong> la analítica, no se carga Google Analytics, no se crean
      cookies de medición y no se envía ningún evento a la analítica propia. El sitio
      funciona igual sin la analítica.
    </p>

    <h3>2.4 Datos técnicos del servidor</h3>
    <p>
      El servidor de hosting puede registrar accesos (dirección IP, fecha y hora, recurso
      solicitado y agente de usuario) como parte de su funcionamiento normal y por razones de
      seguridad. [COMPLETAR — indicar el plazo de conservación de logs que aplica el hosting
      y si el titular accede a ellos.]
    </p>

    <h2>3. Finalidad y base legal del tratamiento</h2>
    <ul class="plain-list">
      <li><strong>Responder tu consulta comercial</strong> y coordinar una demostración. Base: tu consentimiento al enviar el formulario y el interés en una relación comercial.</li>
      <li><strong>Prevenir el abuso del formulario</strong> (spam, envíos automatizados). Base: interés legítimo en proteger el servicio.</li>
      <li><strong>Medir el uso del sitio para mejorarlo</strong>. Base: tu consentimiento explícito, otorgado desde el aviso de cookies, que podés retirar cuando quieras.</li>
      <li><strong>Cumplir obligaciones legales o contables</strong> que resulten aplicables a la facturación de servicios contratados.</li>
    </ul>

    <h2>4. Destinatarios y transferencias</h2>
    <p>No vendemos ni alquilamos datos personales. Los datos pueden ser tratados por:</p>
    <ul class="plain-list">
      <li><strong>Proveedor de hosting y correo</strong> del sitio (infraestructura donde se aloja la aplicación y donde se reciben las consultas). [COMPLETAR — nombre del proveedor de hosting y país donde se alojan los datos.]</li>
      <li><strong>Google</strong>, únicamente si aceptaste la analítica, y solo para las finalidades de medición descritas.</li>
      <li><strong>Cloudflare</strong>, si la verificación anti-spam del formulario está activa, para validar que el envío proviene de una persona.</li>
      <li><strong>Autoridades públicas</strong>, cuando exista un requerimiento legal fundado.</li>
    </ul>
    <p>
      Si alguno de estos proveedores trata datos fuera de la Argentina, se procura que la
      transferencia se realice a países o entidades con niveles de protección adecuados,
      conforme a la normativa aplicable.
    </p>

    <h2>5. Seguridad y limitación de envíos</h2>
    <p>
      Aplicamos medidas técnicas razonables para proteger los datos: conexión cifrada
      mediante HTTPS con HSTS en todo el sitio, cabeceras de seguridad (CSP, X-Frame-Options,
      X-Content-Type-Options, Referrer-Policy), protección contra falsificación de solicitudes
      (<em>token</em> CSRF), un campo trampa contra robots y un límite de envíos por
      dirección IP y ventana de tiempo. La analítica no se envía en texto claro fuera del
      sitio y utiliza el mismo origen.
    </p>

    <h2>6. Plazo de conservación</h2>
    <ul class="plain-list">
      <li><strong>Consultas del formulario:</strong> mientras sea necesario para atender tu consulta y el seguimiento comercial posterior. [COMPLETAR — plazo concreto que definirá el titular, por ejemplo 24 meses desde el último contacto.]</li>
      <li><strong>Eventos de analítica:</strong> [COMPLETAR — plazo, por ejemplo 14 meses.]</li>
      <li><strong>Registros técnicos y de seguridad:</strong> el tiempo necesario para prevenir abuso y cumplir obligaciones legales.</li>
    </ul>

    <h2>7. Tus derechos</h2>
    <p>
      Podés ejercer de forma gratuita los derechos de <strong>acceso, rectificación,
      actualización y supresión</strong> de tus datos personales, así como oponerte a su
      tratamiento y retirar tu consentimiento en cualquier momento.
    </p>
    <p>
      Para hacerlo, escribí a
      <a href="mailto:<?= e($site['contact_email']) ?>"><?= e($site['contact_email']) ?></a><?php if ($site['whatsapp_number'] !== ''): ?>
      o por <a href="<?= e(whatsapp_url('Hola, quiero ejercer mis derechos sobre mis datos personales.')) ?>" target="_blank" rel="noopener" data-track-event="click_whatsapp" data-track-label="Privacidad derechos WhatsApp">WhatsApp</a><?php endif; ?>.
      Indicá tu nombre y el pedido concreto. Responderemos en los plazos que establece la
      normativa.
    </p>
    <p>
      La <strong>Ley 25.326 de Protección de los Datos Personales</strong> de la República
      Argentina reconoce estos derechos. La autoridad de aplicación es la Agencia de Acceso
      a la Información Pública (AAIP), ante quien podés presentar una denuncia si considerás
      que tus derechos no fueron atendidos.
    </p>

    <h2>8. Menores de edad</h2>
    <p>
      El sitio está dirigido a comercios y personas adultas. No se recopilan
      deliberadamente datos de menores de edad. Si detectamos un dato de este tipo,
      procederemos a eliminarlo.
    </p>

    <h2>9. Cookies</h2>
    <p>
      El detalle de las cookies utilizadas, su finalidad y cómo gestionarlas está en la
      <a href="<?= e(site_url('politica-de-cookies.php')) ?>">Política de cookies</a>.
    </p>

    <h2>10. Cambios en esta política</h2>
    <p>
      Podemos actualizar esta política para reflejar cambios en el servicio o en la
      normativa. La versión vigente es la publicada en esta página. Última actualización:
      <strong>05/10/2026</strong>.
    </p>

    <p style="margin-top:36px;">
      Ver también el <a href="<?= e(site_url('aviso-legal.php')) ?>">Aviso legal</a>.
    </p>

    <p class="legal-doc__back"><a href="<?= e(site_url()) ?>">← Volver al inicio</a></p>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
