<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Página no encontrada | FLUS';
$pageDescription = 'La página que buscás no existe o fue movida. Podés volver al inicio o explorar FLUS desde otra sección.';
http_response_code(404);
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container" style="text-align:center; max-width:640px;">
    <span class="eyebrow">Error 404</span>
    <h1>Esta página no existe</h1>
    <p>
      La dirección que ingresaste no corresponde a ninguna página activa.
      Puede que haya cambiado o que el enlace esté incorrecto.
    </p>
    <div class="hero-actions" style="justify-content:center;">
      <a class="btn btn-primary" href="<?= e(site_url()) ?>">Ir al inicio</a>
      <a class="btn btn-secondary" href="<?= e(site_url('contacto.php')) ?>">Contacto</a>
    </div>

    <div style="margin-top:36px;">
      <p style="color:rgba(238,247,245,0.6); font-size:0.92rem;">También podés explorar:</p>
      <div style="display:flex; flex-wrap:wrap; gap:10px; justify-content:center; margin-top:12px;">
        <a class="btn btn-secondary" href="<?= e(site_url('sistema-de-gestion.php')) ?>" style="min-height:40px; font-size:0.88rem;">Sistema</a>
        <a class="btn btn-secondary" href="<?= e(site_url('sistema-pos.php')) ?>" style="min-height:40px; font-size:0.88rem;">POS</a>
        <a class="btn btn-secondary" href="<?= e(site_url('control-de-stock.php')) ?>" style="min-height:40px; font-size:0.88rem;">Stock</a>
        <a class="btn btn-secondary" href="<?= e(site_url()) ?>#precios" style="min-height:40px; font-size:0.88rem;">Precios</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
