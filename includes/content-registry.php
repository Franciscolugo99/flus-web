<?php
/**
 * Registro editorial de FLUS.
 * Agregar un artículo acá hace que aparezca en el Centro de Recursos.
 * Los slugs tienen una carpeta /recursos/<slug>/ con su index.php.
 */
function flus_resource_articles(): array
{
    return [
        'controlar-stock-comercio' => [
            'title' => 'Cómo controlar el stock de un comercio sin depender de Excel',
            'short_title' => 'Cómo controlar el stock de un comercio',
            'description' => 'Método práctico para ordenar entradas, ventas, ajustes, inventarios y stock mínimo sin perder trazabilidad.',
            'category' => 'Stock e inventario',
            'published' => '2026-08-18',
            'updated' => '2026-08-18',
            'reading_time' => '8 min',
            'image' => 'assets/img/seo/control-stock-inventario-comercio-flus.webp',
            'image_alt' => 'Pantalla de control de stock e inventario de FLUS para comercios',
        ],
        'como-hacer-cierre-de-caja' => [
            'title' => 'Cómo hacer un cierre de caja correctamente y detectar diferencias',
            'short_title' => 'Cómo hacer un cierre de caja correctamente',
            'description' => 'Qué registrar al abrir y cerrar caja, cómo calcular el efectivo esperado y cómo investigar diferencias sin improvisar.',
            'category' => 'Caja y ventas',
            'published' => '2026-08-18',
            'updated' => '2026-08-18',
            'reading_time' => '7 min',
            'image' => 'assets/img/seo/sistema-pos-caja-comercio-flus.webp',
            'image_alt' => 'Pantalla de caja POS de FLUS para ventas y cierre de caja',
        ],
        'excel-vs-sistema-de-gestion' => [
            'title' => 'Excel vs. sistema de gestión: cuándo conviene cambiar en un comercio',
            'short_title' => 'Excel vs. sistema de gestión',
            'description' => 'Señales concretas de que las planillas ya no alcanzan y cómo migrar sin frenar la operación diaria del negocio.',
            'category' => 'Gestión del comercio',
            'published' => '2026-08-18',
            'updated' => '2026-08-18',
            'reading_time' => '8 min',
            'image' => 'assets/img/seo/dashboard-ventas-comercio-flus.webp',
            'image_alt' => 'Dashboard de ventas e indicadores de FLUS para comercios',
        ],
        'que-sistema-necesita-un-kiosco' => [
            'title' => 'Qué sistema necesita un kiosco para controlar ventas, caja y stock',
            'short_title' => 'Qué sistema necesita un kiosco',
            'description' => 'Las funciones que realmente importan en un kiosco: venta rápida, códigos de barras, stock, compras, caja y acceso móvil.',
            'category' => 'Guías por rubro',
            'published' => '2026-08-18',
            'updated' => '2026-08-18',
            'reading_time' => '9 min',
            'image' => 'assets/img/seo/sistema-pos-caja-comercio-flus.webp',
            'image_alt' => 'Sistema POS FLUS usado para ventas rápidas en un comercio',
        ],
    ];
}

function flus_resource_article(string $slug): ?array
{
    $articles = flus_resource_articles();
    if (!isset($articles[$slug])) {
        return null;
    }
    return array_merge(['slug' => $slug], $articles[$slug]);
}
