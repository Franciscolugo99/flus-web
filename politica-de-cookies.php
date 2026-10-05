<?php
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Política de cookies | FLUS';
$pageDescription = 'Qué cookies y almacenamiento local usa flus.com.ar, cuáles son necesarias, cuáles requieren tu consentimiento y cómo cambiar tu preferencia.';
$pageCanonical = page_url('politica-de-cookies.php');
$pageStyles = ['css/content-seo.css'];
$pageBreadcrumbs = [
    ['name' => 'Inicio', 'url' => page_url()],
    ['name' => 'Política de cookies', 'url' => $pageCanonical],
];

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container" style="max-width:820px;">
    <span class="eyebrow">Información legal</span>
    <h1>Política de cookies</h1>
    <p>
      Esta página explica qué cookies y tecnologías de almacenamiento local utiliza
      <strong><?= e($site['domain']) ?></strong>, para qué sirve cada una y cómo podés
      cambiar tu decisión en cualquier momento.
    </p>
  </div>
</section>

<section class="section">
  <div class="container legal-doc">

    <h2>1. Cómo gestionamos el consentimiento</h2>
    <p>
      Al ingresar por primera vez, el sitio muestra un aviso con dos opciones claras:
      <strong>Aceptar</strong> o <strong>Rechazar no esenciales</strong>. Hasta que tomes una
      decisión, <strong>no se carga Google Analytics ni se envía ningún evento de analítica</strong>.
      No usamos un aviso que diga gestionar cookies mientras mide igual: la analítica es
      opt-in real.
    </p>
    <p>
      Tu decisión se guarda durante <strong>180 días</strong>. Podés cambiarla cuando quieras
      desde el enlace <strong>«Preferencias de cookies»</strong> que está al pie de todas las
      páginas, o borrando las cookies del sitio en tu navegador.
    </p>
    <p>
      <button type="button" class="btn btn-secondary" data-consent-reopen>
        Abrir preferencias de cookies
      </button>
    </p>

    <h2>2. Cookies y almacenamiento necesarios (no requieren consentimiento)</h2>
    <p>
      Son imprescindibles para que el sitio funcione y para protegerlo. No se usan para
      perfilarte ni para publicidad.
    </p>
    <div class="legal-doc__tablewrap">
      <table class="legal-doc__table">
        <thead>
          <tr>
            <th scope="col">Nombre</th>
            <th scope="col">Tipo</th>
            <th scope="col">Finalidad</th>
            <th scope="col">Duración</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <th scope="row"><code>flus_csrf</code></th>
            <td>Cookie propia</td>
            <td>Protege el formulario de contacto contra envíos falsificados desde otros sitios (<em>Cross-Site Request Forgery</em>). Contiene un valor aleatorio, no identifica a la persona.</td>
            <td>2 horas</td>
          </tr>
          <tr>
            <th scope="row"><code>flus_consent</code></th>
            <td>Cookie propia</td>
            <td>Guarda tu decisión sobre las cookies no esenciales (<code>granted</code> o <code>denied</code>) para no volver a preguntarte en cada visita.</td>
            <td>180 días</td>
          </tr>
        </tbody>
      </table>
    </div>
    <p>
      El sitio <strong>no emite cookies de sesión de servidor para visitantes</strong>: las
      cookies de sesión existen únicamente en el panel interno y en el portal de clientes,
      que son áreas privadas ajenas a la navegación pública.
    </p>

    <h2>3. Cookies y almacenamiento no esenciales (requieren tu consentimiento)</h2>
    <p>
      Solo se activan si elegís <strong>Aceptar</strong>. Si rechazás, no se crean ni se
      cargan.
    </p>
    <div class="legal-doc__tablewrap">
      <table class="legal-doc__table">
        <thead>
          <tr>
            <th scope="col">Nombre</th>
            <th scope="col">Tipo</th>
            <th scope="col">Finalidad</th>
            <th scope="col">Duración</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <th scope="row"><code>_ga</code></th>
            <td>Terceros — Google Analytics 4</td>
            <td>Distingue usuarios para medir cuántas personas visitan el sitio y cómo lo recorren.</td>
            <td>Hasta 24 meses</td>
          </tr>
          <tr>
            <th scope="row"><code>_ga_&lt;ID&gt;</code></th>
            <td>Terceros — Google Analytics 4</td>
            <td>Mantiene el estado de la sesión de medición asociada a la propiedad de FLUS.</td>
            <td>Hasta 24 meses</td>
          </tr>
          <tr>
            <th scope="row"><code>flus_analytics_session_id</code></th>
            <td>Almacenamiento de sesión (primera parte)</td>
            <td>Identificador de la pestaña actual para agrupar las acciones de una misma visita. Se borra al cerrar la pestaña. No es una cookie ni persiste entre visitas.</td>
            <td>Hasta cerrar la pestaña</td>
          </tr>
          <tr>
            <th scope="row"><code>flus_analytics_first_touch</code></th>
            <td>Almacenamiento de sesión (primera parte)</td>
            <td>Recuerda durante la pestaña la página de entrada y los parámetros de campaña (<code>utm_*</code>) para atribuir correctamente el origen de la visita.</td>
            <td>Hasta cerrar la pestaña</td>
          </tr>
        </tbody>
      </table>
    </div>
    <p>
      La analítica propia se envía a <code><?= e($site['domain']) ?>/track.php</code>, dentro
      del mismo sitio, y guarda un <em>hash</em> irreversible de la dirección IP en lugar de
      la IP en texto plano.
    </p>

    <h2>4. Qué NO usamos</h2>
    <ul class="plain-list">
      <li>No usamos cookies de publicidad ni de <em>retargeting</em>.</li>
      <li>No vendemos ni compartimos datos de navegación con terceros con fines publicitarios.</li>
      <li>No usamos Google Tag Manager ni píxeles de redes sociales.</li>
      <li>No creamos perfiles publicitarios ni tomamos decisiones automatizadas sobre las personas.</li>
    </ul>

    <h2>5. Cómo controlar las cookies desde tu navegador</h2>
    <p>
      Además del aviso del sitio, podés bloquear o eliminar cookies desde la configuración de
      tu navegador. Tené en cuenta que bloquear las cookies necesarias puede impedir que el
      formulario de contacto funcione correctamente.
    </p>
    <ul class="plain-list">
      <li><strong>Chrome:</strong> Configuración → Privacidad y seguridad → Cookies y otros datos de sitios.</li>
      <li><strong>Firefox:</strong> Ajustes → Privacidad y seguridad → Cookies y datos del sitio.</li>
      <li><strong>Safari:</strong> Preferencias → Privacidad → Gestionar datos de sitios web.</li>
      <li><strong>Edge:</strong> Configuración → Cookies y permisos del sitio → Cookies y datos del sitio.</li>
    </ul>
    <p>
      Para desactivar específicamente la medición de Google Analytics en todos los sitios,
      Google ofrece un complemento oficial de inhabilitación para navegadores.
    </p>

    <h2>6. Cambios en esta política</h2>
    <p>
      Podemos actualizar esta política si incorporamos o quitamos cookies o herramientas de
      medición. La versión vigente es la publicada en esta página. Última actualización:
      <strong>05/10/2026</strong>.
    </p>

    <p style="margin-top:36px;">
      Ver también el <a href="<?= e(site_url('politica-de-privacidad.php')) ?>">Política de privacidad</a>
      y el <a href="<?= e(site_url('aviso-legal.php')) ?>">Aviso legal</a>.
    </p>

    <p class="legal-doc__back"><a href="<?= e(site_url()) ?>">← Volver al inicio</a></p>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
