<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Sistema de gestión comercial para ventas, caja, stock y facturación | FLUS';
$pageDescription = 'FLUS centraliza ventas, caja, stock, clientes y facturación en un solo sistema para comercios y pymes que necesitan más control diario y menos planillas.';
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
                'name' => '¿FLUS es solo un sistema de caja?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'No. También reúne ventas, caja, stock, clientes, cuenta corriente y facturación dentro de la misma operación diaria.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => '¿Para qué tipo de negocio tiene más sentido?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'Para comercios y pymes con ventas diarias, caja, stock y necesidad de seguir clientes, deuda o facturación sin herramientas separadas.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => '¿Todos los planes incluyen los mismos módulos?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'Sí. Caja, ventas, stock, clientes, cuenta corriente, facturación y reportes están incluidos en los tres planes. La diferencia es la modalidad de pago y el soporte.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => '¿Qué diferencia hay entre el plan anual y el permanente?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'El anual incluye soporte y actualizaciones continuas con un ahorro de 2 meses. El permanente es un pago único con 3 meses de soporte incluidos; después, soporte y actualizaciones son opcionales.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => '¿Puedo cambiar de plan después?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'Sí. Podés pasar de mensual a anual o permanente en cualquier momento. Escribinos y te ayudamos a hacer la transición.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => '¿Se puede coordinar una demo de FLUS?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'Sí. Desde la página de contacto se puede coordinar una demo para ver FLUS funcionando con ejemplos de venta, caja, stock y seguimiento comercial.',
                ],
            ],
        ],
    ],
];
require __DIR__ . '/includes/header.php';

$homeModules = [
    [
        'icon' => 'pos',
        'name' => 'Caja POS',
        'description' => 'Venta, ticket y cobro en la misma pantalla para atender con más ritmo.',
        'tag' => 'POS',
    ],
    [
        'icon' => 'receipt',
        'name' => 'Ventas',
        'description' => 'Historial, tickets, estados y consulta por período.',
        'tag' => 'Comercial',
    ],
    [
        'icon' => 'layers',
        'name' => 'Stock',
        'description' => 'Disponibilidad, alertas y movimientos con trazabilidad.',
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
        'description' => 'Saldos, deuda, cobranzas y movimientos por cliente.',
        'tag' => 'Cobranza',
    ],
    [
        'icon' => 'stamp',
        'name' => 'Facturación',
        'description' => 'Comprobantes integrados a la operación comercial.',
        'tag' => 'Fiscal',
    ],
    [
        'icon' => 'cart',
        'name' => 'Compras y proveedores',
        'description' => 'Ingresos, costos y abastecimiento conectados al stock.',
        'tag' => 'Compras',
    ],
    [
        'icon' => 'report',
        'name' => 'Reportes',
        'description' => 'Ventas, caja, productos y categorías para seguir el negocio.',
        'tag' => 'Análisis',
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
      <span class="eyebrow hero-stagger hero-stagger--1">Sistema de gestión comercial para comercios y pymes</span>
      <h1 class="hero-stagger hero-stagger--2">Ventas, caja, stock, clientes y facturación en un solo sistema</h1>
      <p class="hero-stagger hero-stagger--3">
        FLUS está pensado para comercios y pymes que necesitan trabajar con menos planillas y más control.
        Desde la venta y el cobro hasta el stock, la caja, los clientes y la facturación, todo queda conectado en una misma base.
      </p>

      <div class="hero-actions hero-stagger hero-stagger--4">
        <a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>">Pedir demo</a>
        <a class="btn btn-secondary" href="<?= e(site_url('sistema-de-gestion.php')) ?>">Ver módulos principales</a>
      </div>

      <ul class="hero-points hero-stagger hero-stagger--5">
        <li>Venta, ticket y cobro en la misma pantalla.</li>
        <li>Stock, caja y cliente actualizados dentro del mismo circuito.</li>
        <li>Historial, cuenta corriente y reportes para seguir el día a día.</li>
      </ul>
    </div>

    <div class="hero-media hero-media--board">
      <div class="hero-dash" aria-label="Panel operativo de FLUS" data-hero-dash>
        <div class="hero-dash__bar">
          <span class="hero-dash__bar-dot"></span>
          <span class="hero-dash__bar-dot"></span>
          <span class="hero-dash__bar-dot"></span>
          <span class="hero-dash__bar-title">FLUS &mdash; Resumen del d&iacute;a</span>
        </div>

        <div class="hero-dash__body">
          <div class="hero-dash__kpis">
            <div class="hero-dash__kpi">
              <span class="hero-dash__kpi-label">Ventas hoy</span>
              <span class="hero-dash__kpi-value" data-count-to="47">0</span>
              <span class="hero-dash__kpi-trend hero-dash__kpi-trend--up">+12%</span>
            </div>
            <div class="hero-dash__kpi">
              <span class="hero-dash__kpi-label">Facturado</span>
              <span class="hero-dash__kpi-value" data-count-to="385200" data-prefix="$" data-format="money">$0</span>
              <span class="hero-dash__kpi-trend hero-dash__kpi-trend--up">+8%</span>
            </div>
            <div class="hero-dash__kpi">
              <span class="hero-dash__kpi-label">Productos vendidos</span>
              <span class="hero-dash__kpi-value" data-count-to="124">0</span>
            </div>
            <div class="hero-dash__kpi">
              <span class="hero-dash__kpi-label">Clientes atendidos</span>
              <span class="hero-dash__kpi-value" data-count-to="31">0</span>
            </div>
          </div>

          <div class="hero-dash__row">
            <div class="hero-dash__chart">
              <span class="hero-dash__chart-title">Ventas por hora</span>
              <div class="hero-dash__bars" aria-hidden="true">
                <div class="hero-dash__bar-col" style="--h:28%" data-hour="8h"></div>
                <div class="hero-dash__bar-col" style="--h:45%" data-hour="9h"></div>
                <div class="hero-dash__bar-col" style="--h:62%" data-hour="10h"></div>
                <div class="hero-dash__bar-col" style="--h:88%" data-hour="11h"></div>
                <div class="hero-dash__bar-col hero-dash__bar-col--accent" style="--h:100%" data-hour="12h"></div>
                <div class="hero-dash__bar-col" style="--h:72%" data-hour="13h"></div>
                <div class="hero-dash__bar-col" style="--h:54%" data-hour="14h"></div>
                <div class="hero-dash__bar-col" style="--h:40%" data-hour="15h"></div>
              </div>
            </div>

            <div class="hero-dash__activity">
              <span class="hero-dash__chart-title">Actividad reciente</span>
              <div class="hero-dash__feed">
                <div class="hero-dash__feed-item" data-feed-delay="0">
                  <span class="hero-dash__feed-dot hero-dash__feed-dot--sale"></span>
                  <span>Venta #1047 &mdash; $12.450</span>
                  <small>hace 2 min</small>
                </div>
                <div class="hero-dash__feed-item" data-feed-delay="1">
                  <span class="hero-dash__feed-dot hero-dash__feed-dot--stock"></span>
                  <span>Stock actualizado &mdash; 3 productos</span>
                  <small>hace 5 min</small>
                </div>
                <div class="hero-dash__feed-item" data-feed-delay="2">
                  <span class="hero-dash__feed-dot hero-dash__feed-dot--invoice"></span>
                  <span>Factura A-0042 emitida</span>
                  <small>hace 8 min</small>
                </div>
                <div class="hero-dash__feed-item" data-feed-delay="3">
                  <span class="hero-dash__feed-dot hero-dash__feed-dot--client"></span>
                  <span>Cliente Mar&iacute;a G. &mdash; pago $8.200</span>
                  <small>hace 12 min</small>
                </div>
              </div>
            </div>
          </div>

          <div class="hero-dash__methods">
            <div class="hero-dash__method">
              <span class="hero-dash__method-bar" style="--w:52%"></span>
              <span class="hero-dash__method-label">Efectivo</span>
              <span class="hero-dash__method-pct">52%</span>
            </div>
            <div class="hero-dash__method">
              <span class="hero-dash__method-bar hero-dash__method-bar--alt" style="--w:31%"></span>
              <span class="hero-dash__method-label">Transferencia</span>
              <span class="hero-dash__method-pct">31%</span>
            </div>
            <div class="hero-dash__method">
              <span class="hero-dash__method-bar hero-dash__method-bar--alt2" style="--w:17%"></span>
              <span class="hero-dash__method-label">Tarjeta</span>
              <span class="hero-dash__method-pct">17%</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="modules-band" data-reveal aria-labelledby="home-modules-title">
  <div class="container">
    <div class="modules-band__intro">
      <span class="section-kicker">Módulos principales</span>
      <h2 id="home-modules-title">FLUS reúne las áreas que más pesan en la operación diaria</h2>
      <p>
        Caja, ventas, stock, clientes, cuenta corriente, facturación y reportes dentro de la misma base operativa.
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
      <p class="modules-band__hint">Cada módulo comparte información con el resto. Lo que pasa en caja también impacta en stock, clientes, cuenta corriente y facturación.</p>
      <a class="btn btn-secondary" href="<?= e(site_url('sistema-de-gestion.php')) ?>">Ver módulos principales</a>
    </div>
  </div>
</section>


<section class="section" data-reveal>
  <div class="container">
    <span class="section-kicker">Pantallas reales</span>
    <h2>Así se ve FLUS cuando lo usás en el día a día</h2>
    <p class="section-lead">
      Estas pantallas muestran cómo se ve FLUS al vender, cobrar, revisar ventas y controlar stock sin ir saltando entre herramientas.
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
            <h3>Vendé y cobrá sin perder de vista el ticket</h3>
            <p>
              La caja reúne búsqueda, ticket, medios de pago y total a cobrar para que la operación avance con menos pasos y menos errores.
            </p>
            <ul class="plain-list">
              <li>Búsqueda de producto, ticket y cobro en la misma vista.</li>
              <li>Medios de pago visibles y monto recibido siempre a mano.</li>
              <li>Total destacado para cerrar la venta con más seguridad.</li>
            </ul>
          </div>
          <figure class="story-slide__media product-shot">
            <picture>
              <source srcset="<?= e(asset_url('img/flus-caja-pos.webp')) ?>" type="image/webp">
              <img src="<?= e(asset_url('img/flus-caja-pos.png')) ?>" alt="Pantalla de caja de FLUS con ticket, medios de pago y total a cobrar" width="1608" height="978" loading="lazy" decoding="async">
            </picture>
          </figure>
        </article>

        <article class="story-slide" id="story-slide-2" role="tabpanel" aria-labelledby="story-tab-2" hidden>
          <div class="story-slide__copy">
            <span class="story-slide__eyebrow">Visi&oacute;n general</span>
            <h3>Revisá rápido qué se vendió y cómo se está moviendo el negocio</h3>
            <p>
              El panel general resume ventas, productos, categorías y medios de pago para que puedas leer el día sin andar buscando datos en pantallas separadas.
            </p>
            <ul class="plain-list">
              <li>Gráficos simples para leer evolución y distribución.</li>
              <li>Top productos y categorías con foco comercial.</li>
              <li>Útil para detectar rápido qué está funcionando mejor.</li>
            </ul>
          </div>
          <figure class="story-slide__media product-shot">
            <picture>
              <source srcset="<?= e(asset_url('img/Dashboard.webp')) ?>" type="image/webp">
              <img src="<?= e(asset_url('img/Dashboard.png')) ?>" alt="Dashboard de FLUS con ventas por día, top productos, métodos de pago y ventas por categoría" width="1536" height="868" loading="lazy" decoding="async">
            </picture>
          </figure>
        </article>

        <article class="story-slide" id="story-slide-3" role="tabpanel" aria-labelledby="story-tab-3" hidden>
          <div class="story-slide__copy">
            <span class="story-slide__eyebrow">Inventario operativo</span>
            <h3>Controlá disponibilidad y actuá antes de quedarte sin stock</h3>
            <p>
              La vista de stock combina indicadores, alertas, filtros y acciones rápidas para revisar disponibilidad y tomar decisiones sin depender de planillas externas.
            </p>
            <ul class="plain-list">
              <li>Resumen de productos, bajo stock y sin stock.</li>
              <li>Filtros por estado, categoría y proveedor.</li>
              <li>Acciones directas para ajustar o revisar productos.</li>
            </ul>
          </div>
          <figure class="story-slide__media product-shot">
            <picture>
              <source srcset="<?= e(asset_url('img/Stock.webp')) ?>" type="image/webp">
              <img src="<?= e(asset_url('img/Stock.png')) ?>" alt="Pantalla de control de stock de FLUS con indicadores, filtros y tabla de productos" width="1599" height="854" loading="lazy" decoding="async">
            </picture>
          </figure>
        </article>

        <article class="story-slide" id="story-slide-4" role="tabpanel" aria-labelledby="story-tab-4" hidden>
          <div class="story-slide__copy">
            <span class="story-slide__eyebrow">Seguimiento comercial</span>
            <h3>Consultá ventas y bajá al detalle cuando lo necesites</h3>
            <p>
              El historial permite cruzar filtros, ver indicadores y entrar al detalle de cada venta para entender qué pasó en un período sin perder continuidad.
            </p>
            <ul class="plain-list">
              <li>KPIs del período para leer rendimiento rápido.</li>
              <li>Filtros por fecha, estado y medio de pago.</li>
              <li>Tabla con acciones para entrar al detalle sin romper el flujo.</li>
            </ul>
          </div>
          <figure class="story-slide__media product-shot">
            <picture>
              <source srcset="<?= e(asset_url('img/ventas.webp')) ?>" type="image/webp">
              <img src="<?= e(asset_url('img/ventas.png')) ?>" alt="Pantalla de ventas de FLUS con indicadores, filtros y listado del historial comercial" width="1440" height="900" loading="lazy" decoding="async">
            </picture>
          </figure>
        </article>
      </div>
    </div>
  </div>
</section>

<section class="section" data-reveal>
  <div class="container">
    <span class="section-kicker">Qué resuelve</span>
    <h2>Lo que más suele desordenarse en un comercio</h2>
    <p class="section-lead">
      FLUS apunta a los puntos que más suelen generar ruido: cobrar, controlar caja, saber qué stock hay y seguir clientes o comprobantes sin depender de sistemas sueltos.
    </p>

    <div class="feature-grid">
      <article class="feature-card">
        <h3>Cobro más ágil</h3>
        <p>La venta, el ticket y el medio de pago quedan dentro de la misma pantalla.</p>
      </article>
      <article class="feature-card">
        <h3>Caja del día bajo control</h3>
        <p>Más visibilidad sobre medios de pago, movimientos diarios y cierres.</p>
      </article>
      <article class="feature-card">
        <h3>Stock con menos sorpresas</h3>
        <p>Menos dudas sobre disponibilidad, movimientos y faltantes.</p>
      </article>
      <article class="feature-card">
        <h3>Clientes y comprobantes visibles</h3>
        <p>Historial, cuenta corriente y facturación con mejor continuidad operativa.</p>
      </article>
    </div>
  </div>
</section>

<section class="section section-dark" data-reveal>
  <div class="container">
    <span class="section-kicker">Cómo trabaja FLUS</span>
    <h2>Un circuito claro desde la venta hasta el seguimiento</h2>
    <div class="steps-grid">
      <article class="step-card">
        <span class="step-number">01</span>
        <h3>Registrás la venta</h3>
        <p>Cargás productos, armás el ticket y avanzás sin salir de la pantalla principal.</p>
      </article>
      <article class="step-card">
        <span class="step-number">02</span>
        <h3>Cobrás y cerrás la operación</h3>
        <p>El cobro queda registrado con más claridad dentro de la misma caja.</p>
      </article>
      <article class="step-card">
        <span class="step-number">03</span>
        <h3>El stock se actualiza</h3>
        <p>La operación impacta en existencias y deja trazabilidad para revisar después.</p>
      </article>
      <article class="step-card">
        <span class="step-number">04</span>
        <h3>Consultás cliente, historial y comprobante</h3>
        <p>La venta no queda aislada: podés seguir cliente, deuda, historial y facturación.</p>
      </article>
    </div>
  </div>
</section>

<section class="section" data-reveal>
  <div class="container split-grid">
    <div>
      <span class="section-kicker">Demo</span>
      <h2>Qué vas a ver cuando te mostramos FLUS</h2>
      <p class="section-lead">
        La idea no es contarte una promesa general. En una demo conviene mirar cómo se resuelve una operación real del negocio.
      </p>

      <div class="link-stack">
        <a class="stack-link" href="<?= e(site_url('sistema-pos.php')) ?>">
          <strong>Venta y cobro en caja</strong>
          <span>Cómo se arma el ticket, cómo se cobra y qué información queda registrada.</span>
        </a>
        <a class="stack-link" href="<?= e(site_url('control-de-stock.php')) ?>">
          <strong>Stock, productos y movimientos</strong>
          <span>Cómo se consulta disponibilidad y cómo impacta cada venta o ajuste.</span>
        </a>
        <a class="stack-link" href="<?= e(site_url('sistema-de-gestion.php')) ?>">
          <strong>Historial, clientes y seguimiento</strong>
          <span>Cómo revisar ventas, clientes, cuenta corriente y actividad del período.</span>
        </a>
        <a class="stack-link" href="<?= e(site_url('facturacion.php')) ?>">
          <strong>Facturación integrada</strong>
          <span>Cómo encaja el comprobante dentro del mismo circuito operativo.</span>
        </a>
      </div>
    </div>

    <div class="surface-card">
      <h3>Dónde tiene más sentido</h3>
      <p>
        FLUS encaja mejor en comercios y pymes donde ya hace falta centralizar ventas, caja, stock, clientes y facturación en una sola base.
      </p>
      <ul class="plain-list">
        <li>Kioscos, almacenes, dietéticas, vinotecas y otros comercios con mostrador.</li>
        <li>Negocios con stock, precios, reposición y seguimiento diario.</li>
        <li>Equipos que hoy dependen de planillas o memoria operativa.</li>
        <li>Operaciones que necesitan ver ventas, caja y clientes en un solo lugar.</li>
      </ul>
    </div>
  </div>
</section>


<section class="section section-dark" id="precios" data-reveal>
  <div class="container">
    <div class="pricing-intro">
      <span class="section-kicker">Planes y precios</span>
      <h2>Elegí el plan que mejor se adapte a tu negocio</h2>
      <p class="section-lead">Todos los planes incluyen los mismos módulos: caja, ventas, stock, clientes, cuenta corriente, facturación y reportes. La diferencia está en la modalidad de pago.</p>
    </div>

    <div class="pricing-grid">
      <article class="pricing-card">
        <div class="pricing-card__header">
          <span class="pricing-card__badge">Flexible</span>
          <h3>Mensual</h3>
          <p class="pricing-card__tagline">Ideal para empezar sin compromiso</p>
        </div>
        <div class="pricing-card__price">
          <span class="pricing-card__amount">$29.000</span>
          <span class="pricing-card__period">/ mes</span>
        </div>
        <ul class="pricing-card__features">
          <li>Todos los módulos incluidos</li>
          <li>Soporte técnico por WhatsApp y email</li>
          <li>Actualizaciones incluidas</li>
          <li>Cancelás cuando quieras</li>
        </ul>
        <a class="btn btn-secondary pricing-card__btn" href="<?= e(site_url('contacto.php')) ?>">Consultar</a>
      </article>

      <article class="pricing-card pricing-card--featured">
        <div class="pricing-card__highlight">Ahorrás 2 meses</div>
        <div class="pricing-card__header">
          <span class="pricing-card__badge">Recomendado</span>
          <h3>Anual</h3>
          <p class="pricing-card__tagline">El plan más elegido por comercios</p>
        </div>
        <div class="pricing-card__price">
          <span class="pricing-card__amount">$290.000</span>
          <span class="pricing-card__period">/ año</span>
        </div>
        <ul class="pricing-card__features">
          <li>Todos los módulos incluidos</li>
          <li>Soporte técnico prioritario</li>
          <li>Actualizaciones incluidas</li>
          <li>Equivale a $24.166 por mes</li>
        </ul>
        <a class="btn btn-primary pricing-card__btn" href="<?= e(site_url('contacto.php')) ?>">Consultar</a>
      </article>

      <article class="pricing-card">
        <div class="pricing-card__header">
          <span class="pricing-card__badge">Una vez</span>
          <h3>Permanente</h3>
          <p class="pricing-card__tagline">Licencia instalada, sin mensualidad</p>
        </div>
        <div class="pricing-card__price">
          <span class="pricing-card__amount">$590.000</span>
          <span class="pricing-card__period">pago único</span>
        </div>
        <ul class="pricing-card__features">
          <li>Todos los módulos incluidos</li>
          <li>3 meses de soporte incluidos</li>
          <li>Sin mensualidad obligatoria</li>
          <li>Soporte y actualizaciones opcionales</li>
        </ul>
        <a class="btn btn-secondary pricing-card__btn" href="<?= e(site_url('contacto.php')) ?>">Consultar</a>
      </article>
    </div>

    <p class="pricing-note">Todos los precios son en pesos argentinos e incluyen IVA. ¿Dudas sobre qué plan conviene? <a href="<?= e(site_url('contacto.php')) ?>">Escribinos</a> y te ayudamos a elegir.</p>
  </div>
</section>

<section class="section" data-reveal>
  <div class="container">
    <span class="section-kicker">Preguntas frecuentes</span>
    <h2>Preguntas que vale la pena resolver antes de arrancar</h2>
    <div class="faq-grid">
      <article class="faq-item">
        <h3>&iquest;FLUS es solo un sistema de caja?</h3>
        <p>No. También reúne ventas, caja, stock, clientes, cuenta corriente y facturación dentro de la operación diaria.</p>
      </article>
      <article class="faq-item">
        <h3>&iquest;Para qué tipo de negocio tiene más sentido?</h3>
        <p>Para comercios y pymes con ventas diarias, caja, stock y necesidad de seguir clientes, deuda o facturación sin herramientas separadas.</p>
      </article>
      <article class="faq-item">
        <h3>&iquest;Todos los planes incluyen los mismos módulos?</h3>
        <p>Sí. Caja, ventas, stock, clientes, cuenta corriente, facturación y reportes están incluidos en los tres planes. La diferencia es la modalidad de pago y el soporte.</p>
      </article>
      <article class="faq-item">
        <h3>&iquest;Qué diferencia hay entre el plan anual y el permanente?</h3>
        <p>El anual incluye soporte y actualizaciones continuas con un ahorro de 2 meses. El permanente es un pago único con 3 meses de soporte incluidos; después, soporte y actualizaciones son opcionales.</p>
      </article>
      <article class="faq-item">
        <h3>&iquest;Puedo cambiar de plan después?</h3>
        <p>Sí. Podés pasar de mensual a anual o permanente en cualquier momento. Escribinos y te ayudamos a hacer la transición.</p>
      </article>
      <article class="faq-item">
        <h3>&iquest;Se puede coordinar una demo de FLUS?</h3>
        <p>Sí. Desde la página de contacto se puede coordinar una demo para ver FLUS funcionando con ejemplos de venta, caja, stock y seguimiento comercial.</p>
      </article>
    </div>
  </div>
</section>

<section class="section section-dark" data-reveal>
  <div class="container">
    <div class="cta-box">
      <h2>Empezá a ordenar tu negocio con FLUS</h2>
      <p>
        Pedí una demo para ver cómo trabaja FLUS en una operación real, o elegí el plan que mejor se adapte a tu comercio.
      </p>
      <div class="inline-actions">
        <a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>">Pedir demo</a>
        <a class="btn btn-secondary" href="#precios">Ver planes y precios</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
