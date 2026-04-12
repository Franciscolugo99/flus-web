<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Sistema de gestión comercial para ventas, stock, caja y facturación | FLUS';
$pageDescription = 'FLUS es un sistema de gestión comercial para comercios y pymes que necesitan ordenar ventas, stock, caja, clientes y facturación con más control diario y menos planillas.';
$pageSchemas = [
    [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'FLUS',
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Web',
        'description' => 'Sistema de gestión comercial para ventas, stock, caja, clientes y facturación.',
        'url' => page_url(),
    ],
    [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => [
            [
                '@type' => 'Question',
                'name' => '¿FLUS es solo un sistema POS?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'No. También ayuda a ordenar ventas, caja, stock, clientes y facturación dentro de una misma lógica comercial.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => '¿Para que tipo de negocio sirve mejor?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'Para comercios y pymes que necesitan menos planillas, más control diario y mejor trazabilidad operativa.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => '¿Que conviene mirar en una demo?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'El circuito real: cómo se vende, cómo se cobra, cómo impacta en stock y cómo se sostiene el seguimiento comercial.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => '¿Se puede coordinar una demo de FLUS?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'Sí. Desde la página de contacto se puede iniciar una conversación para evaluar FLUS sobre la operación real del negocio.',
                ],
            ],
        ],
    ],
];
require __DIR__ . '/includes/header.php';

$homeModules = [
    [
        'icon' => 'pos',
        'name' => 'Caja',
        'description' => 'Venta rápida, escaneo, cobro y cálculo de vuelto.',
        'tag' => 'POS',
    ],
    [
        'icon' => 'receipt',
        'name' => 'Ventas',
        'description' => 'Tickets, estados y revisión de operaciones del día.',
        'tag' => 'Comercial',
    ],
    [
        'icon' => 'cart',
        'name' => 'Compras',
        'description' => 'Registro de ingresos, costos y relación con proveedores.',
        'tag' => 'Abastecimiento',
    ],
    [
        'icon' => 'chart',
        'name' => 'Dashboard',
        'description' => 'Indicadores diarios, categorías y productos con más movimiento.',
        'tag' => 'Analisis',
    ],
    [
        'icon' => 'box',
        'name' => 'Productos',
        'description' => 'Alta, edición, precios y organización del catálogo.',
        'tag' => 'Catalogo',
    ],
    [
        'icon' => 'layers',
        'name' => 'Stock',
        'description' => 'Control actual, faltantes y alertas de inventario.',
        'tag' => 'Inventario',
    ],
    [
        'icon' => 'users',
        'name' => 'Clientes',
        'description' => 'Datos, historial y seguimiento comercial por cliente.',
        'tag' => 'CRM',
    ],
    [
        'icon' => 'wallet',
        'name' => 'Cuenta corriente',
        'description' => 'Saldos, movimientos y deuda por cliente.',
        'tag' => 'Cobranza',
    ],
    [
        'icon' => 'stamp',
        'name' => 'Facturacion',
        'description' => 'Comprobantes fiscales y emisión integrada al flujo.',
        'tag' => 'Fiscal',
    ],
    [
        'icon' => 'clock',
        'name' => 'Historial de caja',
        'description' => 'Aperturas, cierres y movimientos de caja registrados.',
        'tag' => 'Caja',
    ],
    [
        'icon' => 'tag',
        'name' => 'Precios',
        'description' => 'Listas, actualización y revisión de márgenes.',
        'tag' => 'Comercial',
    ],
    [
        'icon' => 'megaphone',
        'name' => 'Promociones',
        'description' => 'Reglas simples para vender con descuentos o combos.',
        'tag' => 'Ventas',
    ],
    [
        'icon' => 'clipboard',
        'name' => 'Inventario',
        'description' => 'Conteos, ajustes y control general de existencias.',
        'tag' => 'Inventario',
    ],
    [
        'icon' => 'checklist',
        'name' => 'Conteo fisico',
        'description' => 'Recuento en local para contrastar sistema y realidad.',
        'tag' => 'Control',
    ],
    [
        'icon' => 'refresh',
        'name' => 'Reposicion',
        'description' => 'Necesidades de compra según faltantes y rotación.',
        'tag' => 'Abastecimiento',
    ],
    [
        'icon' => 'swap',
        'name' => 'Movimientos',
        'description' => 'Entradas, salidas y ajustes con trazabilidad.',
        'tag' => 'Trazabilidad',
    ],
    [
        'icon' => 'truck',
        'name' => 'Proveedores',
        'description' => 'Datos, compras y consulta por proveedor.',
        'tag' => 'Compras',
    ],
    [
        'icon' => 'settings',
        'name' => 'Administracion',
        'description' => 'Usuarios, permisos y configuración operativa.',
        'tag' => 'Gestion',
    ],
    [
        'icon' => 'report',
        'name' => 'Reportes operativos',
        'description' => 'Ventas, caja y stock para seguimiento diario.',
        'tag' => 'Reportes',
    ],
];

if (!function_exists('flus_home_module_icon')) {
    function flus_home_module_icon(string $icon): string
    {
        switch ($icon) {
            case 'pos':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 6.5A2.5 2.5 0 0 1 7.5 4h9A2.5 2.5 0 0 1 19 6.5v11A2.5 2.5 0 0 1 16.5 20h-9A2.5 2.5 0 0 1 5 17.5z"/><path d="M8 8.5h8"/><path d="M8 12h3"/><path d="M8 15.5h2"/><path d="M14 12h2"/><path d="M14 15.5h2"/></svg>';
            case 'receipt':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3.5h10v17l-2-1.4-2 1.4-2-1.4-2 1.4-2-1.4-2 1.4v-17z"/><path d="M9 8h6"/><path d="M9 11.5h6"/><path d="M9 15h4"/></svg>';
            case 'cart':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10" cy="18" r="1.6"/><circle cx="17" cy="18" r="1.6"/><path d="M3.5 5h2l1.5 8.2a1.5 1.5 0 0 0 1.5 1.3h8.2a1.5 1.5 0 0 0 1.4-1l2-5.5H7.3"/></svg>';
            case 'chart':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4.5 19.5h15"/><path d="M7.5 16V10"/><path d="M12 16V6.5"/><path d="M16.5 16V12"/></svg>';
            case 'box':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3.8 18.5 7v10L12 20.2 5.5 17V7z"/><path d="M12 3.8 5.5 7 12 10.2 18.5 7z"/><path d="M12 10.2v10"/></svg>';
            case 'layers':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4 4.5 8 12 12 19.5 8z"/><path d="M4.5 12 12 16l7.5-4"/><path d="M4.5 16 12 20l7.5-4"/></svg>';
            case 'users':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><path d="M16 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/><path d="M4.8 18.5a4.7 4.7 0 0 1 8.4 0"/><path d="M14 18.5a4 4 0 0 1 5.2-1.5"/></svg>';
            case 'wallet':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 7.5A2.5 2.5 0 0 1 7.5 5h9A2.5 2.5 0 0 1 19 7.5v9A2.5 2.5 0 0 1 16.5 19h-9A2.5 2.5 0 0 1 5 16.5z"/><path d="M15 12h4"/><path d="M17.5 12h.01"/></svg>';
            case 'stamp':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3.5h7l3 3v10A2.5 2.5 0 0 1 14.5 19h-7A2.5 2.5 0 0 1 5 16.5v-10A3 3 0 0 1 8 3.5z"/><path d="M14 3.5v4h4"/><path d="m9 14 1.5 1.5L14.5 11"/></svg>';
            case 'clock':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="7.5"/><path d="M12 8v4l2.8 1.8"/></svg>';
            case 'tag':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 4.5H6.8A1.8 1.8 0 0 0 5 6.3v4.2l7.8 7.8a1.7 1.7 0 0 0 2.4 0l3.1-3.1a1.7 1.7 0 0 0 0-2.4z"/><circle cx="8.3" cy="8.3" r="1.2"/></svg>';
            case 'megaphone':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12v-1.5a2 2 0 0 1 1.4-1.9L17.5 5v10l-11.1-3.6A2 2 0 0 1 5 9.5z"/><path d="M9 14.2V18"/><path d="M17.5 9.5h1.5a2 2 0 0 1 0 4h-1.5"/></svg>';
            case 'clipboard':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 4.5h6"/><path d="M9 6.5h6"/><path d="M8 4.5H7A2 2 0 0 0 5 6.5v11A2 2 0 0 0 7 19.5h10a2 2 0 0 0 2-2v-11a2 2 0 0 0-2-2h-1"/><path d="M9 11h6"/><path d="M9 14.5h6"/></svg>';
            case 'checklist':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8.5 7.5h9"/><path d="M8.5 12h9"/><path d="M8.5 16.5h9"/><path d="m5.5 7.5.8.8 1.6-1.6"/><path d="m5.5 12 .8.8 1.6-1.6"/><path d="m5.5 16.5.8.8 1.6-1.6"/></svg>';
            case 'refresh':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18.5 8.5V4.5h-4"/><path d="M18 9a6.5 6.5 0 0 0-10.8-2"/><path d="M5.5 15.5v4h4"/><path d="M6 15a6.5 6.5 0 0 0 10.8 2"/></svg>';
            case 'swap':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 7.5h10"/><path d="m13.5 4.5 3.5 3-3.5 3"/><path d="M17 16.5H7"/><path d="m10.5 13.5-3.5 3 3.5 3"/></svg>';
            case 'truck':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="8" cy="17.5" r="1.5"/><circle cx="17" cy="17.5" r="1.5"/><path d="M4.5 7.5h9v8H4.5z"/><path d="M13.5 10h3l2 2v3.5h-5"/></svg>';
            case 'settings':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M12 4.5v2"/><path d="M12 17.5v2"/><path d="M4.5 12h2"/><path d="M17.5 12h2"/><path d="m6.7 6.7 1.4 1.4"/><path d="m15.9 15.9 1.4 1.4"/><path d="m17.3 6.7-1.4 1.4"/><path d="m8.1 15.9-1.4 1.4"/></svg>';
            case 'report':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5.5 19.5h13"/><path d="M7.5 16V11"/><path d="M12 16V8"/><path d="M16.5 16v-3"/><path d="M7.5 9.5 12 6l4.5 3"/></svg>';
            default:
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="7.5"/></svg>';
        }
    }
}
?>
<section class="hero">
  <div class="container hero-grid">
    <div class="hero-copy">
      <span class="eyebrow">Sistema de gesti&oacute;n comercial para comercios y pymes</span>
      <h1>FLUS conecta venta, cobro, stock y seguimiento en un mismo flujo de trabajo</h1>
      <p>
        FLUS integra la operaci&oacute;n comercial del d&iacute;a a d&iacute;a: ventas, cobro, stock, clientes y facturaci&oacute;n
        dentro de un mismo flujo de trabajo. Menos herramientas sueltas, mejor control y m&aacute;s visibilidad sobre lo que pasa en el negocio.
      </p>

      <div class="hero-actions">
        <a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>">Solicitar demo</a>
        <a class="btn btn-secondary" href="<?= e(site_url('sistema-pos.php')) ?>">Ver sistema POS</a>
      </div>

      <ul class="hero-points">
        <li>Ticket visible y medios de pago claros para vender con m&aacute;s ritmo.</li>
        <li>Caja, stock y cobro dentro del mismo flujo operativo.</li>
        <li>Menos planillas y mejor seguimiento comercial despu&eacute;s de cada venta.</li>
      </ul>
    </div>

    <div class="hero-media hero-media--board">
      <div class="hero-system-board" aria-label="Resumen operativo de FLUS">
        <span class="hero-system-board__badge">Operaci&oacute;n conectada</span>

        <div class="hero-system-board__surface">
          <div class="hero-system-board__topline" aria-hidden="true">
            <span>Caja abierta</span>
            <span>Stock sincronizado</span>
            <span>Seguimiento diario</span>
          </div>

          <div class="hero-system-board__grid">
            <article class="hero-system-tile">
              <span class="hero-system-tile__kicker">Caja</span>
              <h3>Venta y cobro en la misma vista</h3>
              <p>Ticket claro, medios de pago visibles y total siempre a mano.</p>
            </article>

            <article class="hero-system-tile">
              <span class="hero-system-tile__kicker">Stock</span>
              <h3>Impacto directo en la operaci&oacute;n</h3>
              <p>La venta actualiza stock y deja trazabilidad sin salir del flujo.</p>
            </article>

            <article class="hero-system-tile">
              <span class="hero-system-tile__kicker">Clientes</span>
              <h3>Seguimiento sobre cada operaci&oacute;n</h3>
              <p>Historial, cuenta corriente y contexto comercial en un mismo sistema.</p>
            </article>

            <article class="hero-system-tile">
              <span class="hero-system-tile__kicker">Fiscal</span>
              <h3>Facturaci&oacute;n integrada</h3>
              <p>Comprobantes y control fiscal conectados al trabajo diario.</p>
            </article>
          </div>

          <div class="hero-system-board__summary">
            <strong>Una operaci&oacute;n impacta donde corresponde.</strong>
            <p>Ventas, caja, stock y facturación dentro de una misma estructura de trabajo.</p>
          </div>

          <div class="hero-system-board__flow" aria-hidden="true">
            <span>Venta</span>
            <span>Cobro</span>
            <span>Stock</span>
            <span>Fiscal</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="modules-band" aria-labelledby="home-modules-title">
  <div class="container">
    <div class="modules-band__intro">
      <span class="section-kicker">M&oacute;dulos conectados</span>
      <h2 id="home-modules-title">M&aacute;s que una caja: FLUS re&uacute;ne la operaci&oacute;n comercial en un solo sistema</h2>
      <p>
        Ventas, stock, clientes, caja, facturaci&oacute;n y seguimiento diario dentro de una estructura pensada para trabajar con m&aacute;s orden.
      </p>
    </div>

    <div class="modules-ticker" data-module-marquee data-speed="32">
      <div class="modules-ticker__viewport">
        <div class="modules-ticker__track">
          <div class="modules-ticker__group">
            <?php foreach ($homeModules as $module): ?>
              <article class="module-card" tabindex="0" role="group" aria-label="<?= e($module['name'] . ' | categoria ' . $module['tag']) ?>">
                <span class="module-card__icon" aria-hidden="true"><?= flus_home_module_icon($module['icon']) ?></span>
                <h3><?= e($module['name']) ?></h3>
                <p><?= e($module['description']) ?></p>
                <span class="module-card__tag"><?= e($module['tag']) ?></span>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="modules-band__footer">
      <p class="modules-band__hint">Cada m&oacute;dulo trabaja conectado al resto. Lo que pasa en caja impacta en stock, clientes y facturaci&oacute;n dentro del mismo sistema.</p>
      <a class="btn btn-secondary" href="<?= e(site_url('sistema-de-gestion.php')) ?>">Ver c&oacute;mo trabaja FLUS</a>
    </div>
  </div>
</section>


<section class="section">
  <div class="container">
    <span class="section-kicker">Pantallas reales</span>
    <h2>Una sola vista a la vez para entender mejor qu&eacute; resuelve FLUS</h2>
    <p class="section-lead">
      En lugar de apilar capturas, esta secci&oacute;n recorre los puntos m&aacute;s fuertes del sistema con una imagen y una explicaci&oacute;n por paso.
    </p>

    <div class="story-carousel" data-carousel data-interval="5200">
      <div class="story-carousel__nav" role="tablist" aria-label="Recorrido por pantallas de FLUS">
        <button class="story-dot is-active" type="button" role="tab" aria-selected="true" aria-controls="story-slide-1" id="story-tab-1" data-slide="0">Caja y cobro</button>
        <button class="story-dot" type="button" role="tab" aria-selected="false" aria-controls="story-slide-2" id="story-tab-2" data-slide="1">Panel general</button>
        <button class="story-dot" type="button" role="tab" aria-selected="false" aria-controls="story-slide-3" id="story-tab-3" data-slide="2">Control de stock</button>
        <button class="story-dot" type="button" role="tab" aria-selected="false" aria-controls="story-slide-4" id="story-tab-4" data-slide="3">Historial de ventas</button>
      </div>

      <div class="story-carousel__viewport">
        <article class="story-slide is-active" id="story-slide-1" role="tabpanel" aria-labelledby="story-tab-1">
          <div class="story-slide__copy">
            <span class="story-slide__eyebrow">Operaci&oacute;n en caja</span>
            <h3>Una pantalla clara para vender, cobrar y cerrar la operaci&oacute;n sin fricci&oacute;n</h3>
            <p>
              FLUS muestra ticket, medios de pago, monto recibido y total a cobrar dentro del mismo flujo para que el cajero trabaje con m&aacute;s ritmo y menos pasos sueltos.
            </p>
            <ul class="plain-list">
              <li>B&uacute;squeda de producto, ticket y cobro en la misma vista.</li>
              <li>Panel lateral con medios de pago y monto recibido.</li>
              <li>Total destacado para reducir errores al cerrar la venta.</li>
            </ul>
          </div>
          <figure class="story-slide__media product-shot">
            <img src="<?= e(asset_url('img/flus-caja-pos.png')) ?>" alt="Pantalla de caja de FLUS con ticket, medios de pago y total a cobrar" width="1608" height="978" loading="lazy" decoding="async">
          </figure>
        </article>

        <article class="story-slide" id="story-slide-2" role="tabpanel" aria-labelledby="story-tab-2" hidden>
          <div class="story-slide__copy">
            <span class="story-slide__eyebrow">Visi&oacute;n general</span>
            <h3>Indicadores visuales para detectar ventas, productos y categor&iacute;as con m&aacute;s movimiento</h3>
            <p>
              El panel general permite recorrer tendencias, top productos, m&eacute;todos de pago y comportamiento por categor&iacute;a sin salir a buscar datos por m&oacute;dulos separados.
            </p>
            <ul class="plain-list">
              <li>Gr&aacute;ficos simples para leer evoluci&oacute;n y distribuci&oacute;n.</li>
              <li>Top productos y categor&iacute;as con foco comercial.</li>
              <li>Buen apoyo para revisar actividad y tomar decisiones r&aacute;pidas.</li>
            </ul>
          </div>
          <figure class="story-slide__media product-shot">
            <img src="<?= e(asset_url('img/Dashboard.png')) ?>" alt="Dashboard de FLUS con ventas por d&iacute;a, top productos, m&eacute;todos de pago y ventas por categor&iacute;a" width="1536" height="868" loading="lazy" decoding="async">
          </figure>
        </article>

        <article class="story-slide" id="story-slide-3" role="tabpanel" aria-labelledby="story-tab-3" hidden>
          <div class="story-slide__copy">
            <span class="story-slide__eyebrow">Inventario operativo</span>
            <h3>Stock visible, filtros pr&aacute;cticos y acciones r&aacute;pidas para trabajar con m&aacute;s contexto</h3>
            <p>
              La pantalla de stock combina indicadores, alertas, filtros y tabla operativa para revisar disponibilidad y actuar sin depender de planillas externas.
            </p>
            <ul class="plain-list">
              <li>Resumen de productos, bajo stock y sin stock.</li>
              <li>Filtros por estado, categor&iacute;a y proveedor.</li>
              <li>Acciones directas para ajustar o revisar productos.</li>
            </ul>
          </div>
          <figure class="story-slide__media product-shot">
            <img src="<?= e(asset_url('img/Stock.png')) ?>" alt="Pantalla de control de stock de FLUS con indicadores, filtros y tabla de productos" width="1599" height="854" loading="lazy" decoding="async">
          </figure>
        </article>

        <article class="story-slide" id="story-slide-4" role="tabpanel" aria-labelledby="story-tab-4" hidden>
          <div class="story-slide__copy">
            <span class="story-slide__eyebrow">Seguimiento comercial</span>
            <h3>Historial de ventas para revisar tickets, medios de pago y rendimiento del per&iacute;odo</h3>
            <p>
              Desde una sola vista se pueden cruzar filtros, revisar indicadores y bajar al detalle de cada venta para entender mejor qu&eacute; pas&oacute; en el per&iacute;odo.
            </p>
            <ul class="plain-list">
              <li>KPIs del per&iacute;odo para leer rendimiento r&aacute;pido.</li>
              <li>Filtros por fecha, estado y m&aacute;s contexto comercial.</li>
              <li>Tabla con acciones para entrar al detalle sin perder continuidad.</li>
            </ul>
          </div>
          <figure class="story-slide__media product-shot">
            <img src="<?= e(asset_url('img/ventas.png')) ?>" alt="Pantalla de ventas de FLUS con indicadores, filtros y listado del historial comercial" width="1440" height="900" loading="lazy" decoding="async">
          </figure>
        </article>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <span class="section-kicker">Qu&eacute; resuelve</span>
    <h2>Cuando la operaci&oacute;n est&aacute; partida, el negocio pierde control</h2>
    <p class="section-lead">
      FLUS busca atacar un problema concreto: que ventas, caja, stock y facturaci&oacute;n no queden trabajando por separado.
      Esa dispersi&oacute;n siempre termina pegando en tiempo, control y seguimiento.
    </p>

    <div class="feature-grid">
      <article class="feature-card">
        <h3>Ventas con m&aacute;s contexto</h3>
        <p>La operaci&oacute;n no termina en el cobro. Despu&eacute;s importan caja, stock, cliente y comprobante.</p>
      </article>
      <article class="feature-card">
        <h3>Caja m&aacute;s clara</h3>
        <p>M&aacute;s visibilidad sobre medios de pago, movimientos diarios y cierres con criterio real.</p>
      </article>
      <article class="feature-card">
        <h3>Stock m&aacute;s confiable</h3>
        <p>Menos dudas sobre disponibilidad, movimientos y el impacto de cada venta.</p>
      </article>
      <article class="feature-card">
        <h3>Seguimiento comercial</h3>
        <p>Historial, clientes y facturaci&oacute;n con mejor continuidad operativa.</p>
      </article>
    </div>
  </div>
</section>

<section class="section section-dark">
  <div class="container">
    <span class="section-kicker">C&oacute;mo trabaja FLUS</span>
    <h2>Un circuito m&aacute;s ordenado para la gesti&oacute;n comercial diaria</h2>
    <div class="steps-grid">
      <article class="step-card">
        <span class="step-number">01</span>
        <h3>Venta y atenci&oacute;n</h3>
        <p>La operaci&oacute;n arranca con una base m&aacute;s prolija para vender, registrar y seguir cada movimiento.</p>
      </article>
      <article class="step-card">
        <span class="step-number">02</span>
        <h3>Caja y cobro</h3>
        <p>Los medios de pago y el control diario quedan m&aacute;s claros dentro del mismo circuito.</p>
      </article>
      <article class="step-card">
        <span class="step-number">03</span>
        <h3>Stock y disponibilidad</h3>
        <p>La venta conversa mejor con el stock y eso reduce incertidumbre en la operaci&oacute;n.</p>
      </article>
      <article class="step-card">
        <span class="step-number">04</span>
        <h3>Comprobante y seguimiento</h3>
        <p>Facturaci&oacute;n, cliente e historial comercial quedan mejor conectados para revisar lo que pas&oacute;.</p>
      </article>
    </div>
  </div>
</section>

<section class="section">
  <div class="container split-grid">
    <div>
      <span class="section-kicker">Soluciones</span>
      <h2>Explor&aacute; FLUS desde la parte de la operaci&oacute;n que m&aacute;s te preocupa</h2>
      <p class="section-lead">
        Cada p&aacute;gina interna est&aacute; pensada para bajar el problema a tierra y mostrar c&oacute;mo encaja FLUS en la gesti&oacute;n diaria.
      </p>

      <div class="link-stack">
        <a class="stack-link" href="<?= e(site_url('sistema-de-gestion.php')) ?>">
          <strong>Sistema de gesti&oacute;n</strong>
          <span>Visi&oacute;n completa para ventas, stock, caja, clientes y facturaci&oacute;n.</span>
        </a>
        <a class="stack-link" href="<?= e(site_url('sistema-pos.php')) ?>">
          <strong>Sistema POS</strong>
          <span>Mostrador, caja y cobro con m&aacute;s orden y mejor continuidad.</span>
        </a>
        <a class="stack-link" href="<?= e(site_url('control-de-stock.php')) ?>">
          <strong>Control de stock</strong>
          <span>Disponibilidad y movimientos con mejor contexto operativo.</span>
        </a>
        <a class="stack-link" href="<?= e(site_url('facturacion.php')) ?>">
          <strong>Facturaci&oacute;n</strong>
          <span>Comprobantes integrados a la operaci&oacute;n comercial.</span>
        </a>
      </div>
    </div>

    <div class="surface-card">
      <h3>D&oacute;nde tiene m&aacute;s sentido</h3>
      <p>
        FLUS encaja mejor en comercios y pymes donde la operaci&oacute;n ya no entra c&oacute;moda en planillas,
        memoria operativa o herramientas desconectadas.
      </p>
      <ul class="plain-list">
        <li>Comercios con ventas diarias, caja y stock.</li>
        <li>Equipos que necesitan menos tareas repetidas.</li>
        <li>Negocios que buscan m&aacute;s orden comercial y m&aacute;s trazabilidad.</li>
        <li>Operaciones que necesitan crecer con una base m&aacute;s seria.</li>
      </ul>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <span class="section-kicker">Preguntas frecuentes</span>
    <h2>Lo que suele evaluarse antes de pedir una demo</h2>
    <div class="faq-grid">
      <article class="faq-item">
        <h3>&iquest;FLUS es solo un sistema POS?</h3>
        <p>No. Tambi&eacute;n ayuda a ordenar ventas, caja, stock, clientes y facturaci&oacute;n dentro de una misma l&oacute;gica comercial.</p>
      </article>
      <article class="faq-item">
        <h3>&iquest;Para qu&eacute; tipo de negocio sirve mejor?</h3>
        <p>Para comercios y pymes que necesitan menos planillas, m&aacute;s control diario y mejor trazabilidad operativa.</p>
      </article>
      <article class="faq-item">
        <h3>&iquest;Qu&eacute; conviene mirar en una demo?</h3>
        <p>El circuito real: c&oacute;mo se vende, c&oacute;mo se cobra, c&oacute;mo impacta en stock y c&oacute;mo se sostiene el seguimiento comercial.</p>
      </article>
      <article class="faq-item">
        <h3>&iquest;Se puede coordinar una demo de FLUS?</h3>
        <p>S&iacute;. Desde la p&aacute;gina de contacto se puede iniciar una conversaci&oacute;n para evaluar FLUS sobre la operaci&oacute;n real del negocio.</p>
      </article>
    </div>
  </div>
</section>

<section class="section section-dark">
  <div class="container">
    <div class="cta-box">
      <h2>Si tu operaci&oacute;n ya necesita m&aacute;s orden, conviene mirar FLUS sobre el flujo real del negocio</h2>
      <p>
        La mejor demo es la que se eval&uacute;a con ventas, caja, stock y facturaci&oacute;n como parte de una misma conversaci&oacute;n.
      </p>
      <div class="inline-actions">
        <a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>">Solicitar demo</a>
        <a class="btn btn-secondary" href="<?= e(site_url('sistema-pos.php')) ?>">Ver sistema POS</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
