<?php
/**
 * FLUS · Registro comercial de planes de FLUS Web.
 *
 * Todos los planes son FLUS Web: no son productos distintos, son la misma
 * plataforma con distinta capacidad. La diferenciacion es por locales, cajas,
 * usuarios, capacidad y nivel de soporte — NO por funciones centrales, que estan
 * presentes desde el plan de entrada.
 *
 * IMPORTANTE (verificado contra la superficie publica del repo):
 *   - Las funciones centrales (caja, ventas, productos, stock, compras,
 *     proveedores, clientes) estan documentadas en el sitio publico.
 *   - "Facturacion ARCA / integracion fiscal" NO se anuncia como incluida en
 *     ningun plan: el propio sitio la condiciona a la implementacion acordada
 *     (index.php, flus-web.php). Se mantiene esa cautela.
 *   - Cuenta corriente, precios mayoristas y backups NO tienen evidencia en la
 *     superficie publica: no se anuncian hasta confirmarlos.
 *
 * Los PRECIOS no viven aca: salen de $site['plans'][<id>]['price'] en
 * includes/config.local.php. Mientras no esten definidos, la UI no inventa
 * numeros (ver flus_plan_price_text()).
 */

if (!function_exists('flus_web_plans')) {
    /**
     * Definicion de los planes, en orden comercial ascendente.
     *
     * Cada plan:
     *   id, name, tagline (frase corta), audience (para quien es),
     *   specs (capacidades mostradas en la card, 3-5),
     *   price_from (bool: mostrar "desde"), price_fallback (texto si no hay
     *   precio configurado), cta (tipo de accion), highlight (etiqueta),
     *   comparison (fila de la tabla comparativa).
     */
    function flus_web_plans(): array
    {
        return [
            'inicio' => [
                'id' => 'inicio',
                'name' => 'FLUS Inicio',
                'tagline' => 'Empezá a ordenar tu negocio.',
                'audience' => 'Kiosco, almacén o emprendimiento atendido por su dueño. Para quien hoy usa cuaderno, Excel o un sistema muy básico.',
                'specs' => [
                    '1 local',
                    '1 caja',
                    '1 usuario',
                    'Acceso desde PC, tablet y móvil',
                    'Soporte estándar',
                ],
                'price_from' => true,
                'price_fallback' => 'A definir',
                'cta' => 'demo',
                'highlight' => null,
                'capacity' => ['locales' => '1', 'cajas' => '1', 'usuarios' => '1', 'productos' => '~2.000'],
                'comparison' => [
                    'locales' => '1',
                    'cajas' => '1',
                    'usuarios' => '1',
                    'productos' => '~2.000',
                    'soporte' => 'Estándar',
                    'crecimiento' => 'Sumás capacidad cuando lo necesitás',
                ],
            ],
            'negocio' => [
                'id' => 'negocio',
                'name' => 'FLUS Negocio',
                'tagline' => 'Para el comercio que ya tiene movimiento todos los días.',
                'audience' => 'Comercio con empleados y más de una persona operando. Cuando una sola caja y un solo usuario ya no alcanzan.',
                'specs' => [
                    '1 local',
                    'Hasta 2 cajas',
                    'Hasta 3 usuarios',
                    'Soporte estándar',
                ],
                'price_from' => true,
                'price_fallback' => 'A definir',
                'cta' => 'demo',
                'highlight' => 'Más elegido',
                'capacity' => ['locales' => '1', 'cajas' => 'hasta 2', 'usuarios' => 'hasta 3', 'productos' => '~6.000'],
                'comparison' => [
                    'locales' => '1',
                    'cajas' => 'Hasta 2',
                    'usuarios' => 'Hasta 3',
                    'productos' => '~6.000',
                    'soporte' => 'Estándar',
                    'crecimiento' => 'Sumás capacidad cuando lo necesitás',
                ],
            ],
            'pro' => [
                'id' => 'pro',
                'name' => 'FLUS Pro',
                'tagline' => 'Más cajas, más equipo, más control.',
                'audience' => 'Comercio con mayor volumen, varias cajas y varios usuarios. Uno o dos locales y necesidad de más control.',
                'specs' => [
                    'Hasta 2 locales',
                    'Hasta 4 cajas',
                    'Hasta 8 usuarios',
                    'Soporte prioritario',
                ],
                'price_from' => true,
                'price_fallback' => 'A definir',
                'cta' => 'demo',
                'highlight' => null,
                'capacity' => ['locales' => 'hasta 2', 'cajas' => 'hasta 4', 'usuarios' => 'hasta 8', 'productos' => '~20.000'],
                'comparison' => [
                    'locales' => 'Hasta 2',
                    'cajas' => 'Hasta 4',
                    'usuarios' => 'Hasta 8',
                    'productos' => '~20.000',
                    'soporte' => 'Prioritario',
                    'crecimiento' => 'Sumás capacidad cuando lo necesitás',
                ],
            ],
            'multi' => [
                'id' => 'multi',
                'name' => 'FLUS Multi',
                'tagline' => 'Cuando tu negocio ya opera en otra escala.',
                'audience' => 'Varias sucursales, múltiples cajas y una estructura que necesita configuración comercial según la operación.',
                'specs' => [
                    'Varias sucursales',
                    'Múltiples cajas y usuarios',
                    'Gran volumen de productos',
                    'Configuración según operación',
                ],
                'price_from' => false,
                'price_fallback' => 'Según operación',
                'cta' => 'consulta',
                'highlight' => null,
                'capacity' => ['locales' => 'varios', 'cajas' => 'múltiples', 'usuarios' => 'múltiples', 'productos' => 'gran volumen'],
                'comparison' => [
                    'locales' => 'Varias sucursales',
                    'cajas' => 'Múltiples',
                    'usuarios' => 'Múltiples',
                    'productos' => 'Gran volumen',
                    'soporte' => 'A convenir',
                    'crecimiento' => 'Configuración según operación',
                ],
            ],
        ];
    }
}

if (!function_exists('flus_web_plan')) {
    function flus_web_plan(string $id): ?array
    {
        $plans = flus_web_plans();
        return $plans[$id] ?? null;
    }
}

if (!function_exists('flus_plans_have_prices')) {
    /**
     * ¿Hay algun precio real definido en configuracion?
     *
     * Sirve para no publicar precios inventados: si no hay ninguno, la UI usa el
     * texto neutro de cada plan y el schema Offer no declara precios.
     */
    function flus_plans_have_prices(): bool
    {
        global $site;

        foreach (array_keys(flus_web_plans()) as $id) {
            $price = trim((string) ($site['plans'][$id]['price'] ?? ''));
            if ($price !== '') {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('flus_plan_price')) {
    /** Precio configurado de un plan, o '' si todavia no se definio. */
    function flus_plan_price(string $id): string
    {
        global $site;
        return trim((string) ($site['plans'][$id]['price'] ?? ''));
    }
}

if (!function_exists('flus_plan_price_text')) {
    /**
     * Texto de precio para mostrar. NUNCA inventa un numero: si el plan no tiene
     * precio configurado, devuelve su texto neutro ("A definir" / "Según
     * operación"). Se elimina cualquier simbolo de moneda vacio o "$0".
     */
    function flus_plan_price_text(string $id): string
    {
        $plan = flus_web_plan($id);
        if ($plan === null) {
            return '';
        }

        $price = flus_plan_price($id);
        if ($price === '' || preg_match('/^\$?\s*0+([.,]0+)?$/', $price) === 1) {
            return (string) $plan['price_fallback'];
        }

        return $price;
    }
}

if (!function_exists('flus_plan_whatsapp_url')) {
    /**
     * Enlace de WhatsApp con el plan en el mensaje, para saber por cual consulta.
     * Usa el helper central whatsapp_url(): no hardcodea el numero.
     */
    function flus_plan_whatsapp_url(string $id): string
    {
        $plan = flus_web_plan($id);
        if ($plan === null) {
            return whatsapp_url('Hola, quiero conocer FLUS.');
        }

        return whatsapp_url('Hola, quiero consultar por ' . $plan['name'] . '.');
    }
}

if (!function_exists('flus_plan_cta_url')) {
    /** Destino del CTA del plan: demo para los planes Web, WhatsApp para Multi. */
    function flus_plan_cta_url(string $id): string
    {
        global $site;

        $plan = flus_web_plan($id);
        if ($plan === null) {
            return '#';
        }

        if (($plan['cta'] ?? 'demo') === 'consulta') {
            return flus_plan_whatsapp_url($id);
        }

        return (string) ($site['demo_url'] ?? site_url('contacto.php'));
    }
}

if (!function_exists('flus_plan_cta_label')) {
    function flus_plan_cta_label(string $id): string
    {
        $plan = flus_web_plan($id);
        if ($plan === null) {
            return 'Probar FLUS';
        }

        return ($plan['cta'] ?? 'demo') === 'consulta'
            ? 'Consultar ' . $plan['name']
            : 'Probar FLUS';
    }
}

if (!function_exists('flus_plan_capacity_label')) {
    /**
     * Texto de capacidad para el schema y las listas.
     * Ej: "1 local, 1 caja, 1 usuario".
     */
    function flus_plan_capacity_label(string $id): string
    {
        $plan = flus_web_plan($id);
        if ($plan === null) {
            return '';
        }

        $c = $plan['capacity'] ?? [];
        $parts = [];
        if (!empty($c['locales'])) { $parts[] = $c['locales'] . ' local' . ($c['locales'] === '1' ? '' : 'es'); }
        if (!empty($c['cajas'])) { $parts[] = $c['cajas'] . ' caja' . ($c['cajas'] === '1' ? '' : 's'); }
        if (!empty($c['usuarios'])) { $parts[] = $c['usuarios'] . ' usuario' . ($c['usuarios'] === '1' ? '' : 's'); }

        return implode(', ', $parts);
    }
}
