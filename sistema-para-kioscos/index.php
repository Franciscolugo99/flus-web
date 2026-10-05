<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle = 'Sistema para kioscos: ventas, caja y stock | FLUS';
$pageDescription = 'Sistema para kioscos y comercios de mostrador con caja POS, códigos de barras, stock, compras y acceso desde PC o móvil con FLUS Web.';
$pageCanonical = page_url('sistema-para-kioscos/');
$pageBodyClass = 'page-sistema-kioscos';
$pageStyles = ['css/seo-growth.css', 'css/content-seo.css'];
$pageBreadcrumbs = [['name'=>'Inicio','url'=>page_url()],['name'=>'Sistema para kioscos','url'=>$pageCanonical]];

// La oferta se arma desde el registro de planes. Si FLUS Inicio todavia no tiene
// precio configurado NO se declara "price": no se publican numeros inventados.
$kioscoOffer = [
  '@type' => 'Offer',
  'name' => 'FLUS Inicio',
  'description' => 'Plan de entrada para kioscos: 1 local, 1 caja, 1 usuario, con las funciones centrales incluidas.',
  'category' => 'Suscripción mensual',
];
if (flus_plan_price_value('inicio') !== '') {
  // Valor numerico para el schema, no el texto con formato de moneda.
  $kioscoOffer['price'] = flus_plan_price_value('inicio');
  $kioscoOffer['priceCurrency'] = 'ARS';
}
$pageSchemas = [[
  '@context'=>'https://schema.org','@type'=>'SoftwareApplication','name'=>'FLUS para kioscos','applicationCategory'=>'BusinessApplication','operatingSystem'=>'Web browser, Windows, Android, iOS','description'=>$pageDescription,
  'offers'=>$kioscoOffer
]];
require __DIR__ . '/../includes/header.php';
?>
<div class="container seo-breadcrumbs"><a href="<?= e(site_url()) ?>">Inicio</a><span>›</span>Sistema para kioscos</div>
<section class="kiosk-hero"><div class="container kiosk-hero__grid"><div><span class="resource-eyebrow">FLUS para kioscos y almacenes</span><h1>Vendé rápido y mantené caja y stock bajo control.</h1><p>Una operación de mostrador necesita velocidad al cobrar y orden después de cada venta. FLUS conecta productos, medios de pago, caja y stock para evitar planillas paralelas.</p><div class="growth-hero__actions"><a class="btn btn-primary" href="<?= e((string)$site['demo_url']) ?>" target="_blank" rel="noopener" data-track-event="cta_primary_click" data-track-label="Kioscos hero demo">Probar FLUS</a><a class="btn btn-secondary" href="<?= e(site_url('contacto.php')) ?>#formulario-contacto" data-track-event="click_contact" data-track-label="Kioscos hero contacto">Consultar implementación</a></div><div class="kiosk-proof"><span>Códigos de barras</span><span>Stock</span><span>Compras</span><span>Efectivo / QR / tarjetas</span><span>PC + móvil</span></div></div><div class="kiosk-window"><div class="kiosk-window__bar"><b>FLUS · Caja POS</b></div><img src="<?= e(asset_url('img/seo/sistema-pos-caja-comercio-flus.webp')) ?>" alt="Sistema de caja POS FLUS para kioscos y comercios" width="1608" height="978"></div></div></section>
<section class="resource-section"><div class="container"><div class="resource-head"><h2>Lo que importa en un kiosco.</h2><p>No una lista infinita de módulos: las tareas que tienen que funcionar bien todos los días.</p></div><div class="kiosk-grid">
<div class="kiosk-card"><b>01</b><h3>Venta por código</h3><p>Buscá o escaneá productos y agregalos a la venta sin perder de vista ticket y total.</p></div>
<div class="kiosk-card"><b>02</b><h3>Medios de pago</h3><p>Separá efectivo, QR, débito, crédito y transferencia para que la caja tenga sentido al cierre.</p></div>
<div class="kiosk-card"><b>03</b><h3>Stock conectado</h3><p>La venta descuenta unidades y compras/ajustes explican por qué cambió cada existencia.</p></div>
<div class="kiosk-card"><b>04</b><h3>Compras</h3><p>Registrá ingresos de mercadería y proveedores en lugar de corregir cantidades a mano.</p></div>
<div class="kiosk-card"><b>05</b><h3>Cierre de caja</h3><p>Compará efectivo esperado y declarado y mantené trazabilidad del turno.</p></div>
<div class="kiosk-card"><b>06</b><h3>Desde el teléfono</h3><p>FLUS Web funciona por navegador/PWA para consultar y operar desde PC, tablet o móvil.</p></div>
</div></div></section>
<section class="resource-section resource-section--soft"><div class="container growth-split"><div><span class="resource-eyebrow">Precio de entrada</span><h2 style="font-size:clamp(34px,4vw,52px);margin:12px 0;letter-spacing:-.045em">Empezá con FLUS Web.</h2><p style="color:#6e6477;line-height:1.7">Para un kiosco pequeño o mediano, FLUS Web evita tener que montar un servidor en el local. La propuesta base contempla 1 local + 1 caja y la puesta en marcha se define según tu configuración.</p><a href="<?= e(site_url('flus-web.php')) ?>">Conocer la edición Web →</a></div><div class="kiosk-price"><span class="resource-eyebrow">FLUS Inicio</span><strong<?= flus_plan_price('inicio') === '' ? ' class="is-placeholder"' : '' ?>><?= e(flus_plan_price_text('inicio')) ?></strong><?php if (flus_plan_price('inicio') !== ''): ?><p>ARS / mes · 1 local + 1 caja.</p><?php else: ?><p>El plan de entrada para 1 local, 1 caja y 1 usuario. <a href="<?= e(site_url()) ?>#precios">Ver todos los planes →</a></p><?php endif; ?><a class="btn btn-primary" href="<?= e((string)$site['demo_url']) ?>" target="_blank" rel="noopener" data-track-event="cta_primary_click" data-track-label="Kioscos precio demo">Probar FLUS</a></div></div></section>
<section class="resource-section"><div class="container"><div class="resource-head"><h2>Antes de comprar un sistema, mirá cómo trabaja.</h2><p>Preparamos una guía específica con los puntos que conviene evaluar en un kiosco.</p></div><a class="resource-card" style="max-width:760px" href="<?= e(site_url('recursos/que-sistema-necesita-un-kiosco/')) ?>"><div class="resource-card__media"><img src="<?= e(asset_url('img/seo/sistema-pos-caja-comercio-flus.webp')) ?>" alt="Caja POS para kioscos" loading="lazy"></div><div class="resource-card__body"><div class="resource-card__meta"><span class="resource-card__category">Guía por rubro</span><span>·</span><span>9 min</span></div><h2>Qué sistema necesita un kiosco</h2><p>Venta rápida, códigos de barras, stock, compras, caja, medios de pago y acceso móvil.</p><span class="resource-card__link">Leer guía</span></div></a></div></section>
<section class="resource-section"><div class="container"><div class="growth-cta"><div><h2>¿Querés probar FLUS con una operación de mostrador?</h2><p>Abrí la demo de FLUS Web o contanos cuántos productos y cajas manejás.</p></div><div class="growth-cta__actions"><a class="btn btn-primary" href="<?= e((string)$site['demo_url']) ?>" target="_blank" rel="noopener" data-track-event="cta_primary_click" data-track-label="Kioscos CTA demo">Probar FLUS</a><a class="btn btn-secondary" href="<?= e(whatsapp_url('Hola, tengo un kiosco/almacén y quiero saber si FLUS me sirve.')) ?>" target="_blank" rel="noopener" data-track-event="click_whatsapp" data-track-label="Kioscos CTA WhatsApp">Hablar por WhatsApp</a></div></div></div></section>
<script>window.addEventListener('load',function(){if(window.FLUSTrack){window.FLUSTrack.track('view_industry_landing',{industry:'kiosco'});}if(typeof gtag==='function'){gtag('event','view_industry_landing',{industry:'kiosco'});}});</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
