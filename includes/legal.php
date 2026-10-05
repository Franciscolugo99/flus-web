<?php
/**
 * FLUS · Datos legales centralizados.
 *
 * Los valores reales viven en includes/config.local.php, que NO se versiona
 * (este repositorio es publico). Las paginas legales los leen desde aca y nunca
 * los hardcodean.
 *
 * Fallo seguro: si falta cualquier valor requerido, las paginas legales no se
 * publican. Responden 503 con un mensaje neutro en lugar de mostrar datos
 * incompletos o marcadores tipo "[COMPLETAR]".
 *
 * El detalle de QUE falta nunca se expone al publico: solo queda en el log del
 * servidor, para no revelar informacion sobre la configuracion interna.
 */

if (!function_exists('flus_legal_fields')) {
    /**
     * Mapa de campos legales: tipo y etiqueta para el mensaje de error.
     * 'text' se exige no vacio; 'int' se exige entero positivo.
     */
    function flus_legal_fields(): array
    {
        return [
            'legal_name' => ['type' => 'text', 'label' => 'razon social o titular'],
            'legal_cuit' => ['type' => 'text', 'label' => 'CUIT'],
            'legal_address' => ['type' => 'text', 'label' => 'domicilio legal'],
            'legal_tax_status' => ['type' => 'text', 'label' => 'condicion frente al IVA'],
            'legal_registration' => ['type' => 'text', 'label' => 'inscripcion registral'],
            'hosting_provider' => ['type' => 'text', 'label' => 'proveedor de hosting'],
            'hosting_country' => ['type' => 'text', 'label' => 'pais del datacenter'],
            'form_retention_months' => ['type' => 'int', 'label' => 'retencion del formulario'],
            'analytics_retention_months' => ['type' => 'int', 'label' => 'retencion de analitica'],
        ];
    }

    /** Valor crudo de un dato legal, o '' si no esta definido. */
    function flus_legal(string $key): string
    {
        global $site;

        $value = $site[$key] ?? '';
        if (is_int($value)) {
            return (string) $value;
        }

        return trim((string) $value);
    }

    /** Valor entero de un dato legal, o 0 si no es valido. */
    function flus_legal_int(string $key): int
    {
        $raw = flus_legal($key);
        return ctype_digit($raw) ? (int) $raw : 0;
    }

    /**
     * Devuelve las claves requeridas que faltan. Vacio = configuracion completa.
     * No se debe mostrar al publico: solo para el log.
     */
    function flus_legal_missing(): array
    {
        $missing = [];

        foreach (flus_legal_fields() as $key => $def) {
            if ($def['type'] === 'int') {
                if (flus_legal_int($key) < 1) {
                    $missing[] = $key;
                }
                continue;
            }

            if (flus_legal($key) === '') {
                $missing[] = $key;
            }
        }

        return $missing;
    }

    /** ¿Esta completa la configuracion legal? */
    function flus_legal_ready(): bool
    {
        return flus_legal_missing() === [];
    }

    /**
     * Exige que la configuracion este completa antes de publicar una pagina
     * legal. Si falta algo: registra en el log QUE falta (lado servidor) y
     * responde 503 con una pagina neutra, sin filtrar datos internos.
     *
     * Se responde 503 (no 404) de forma deliberada: la pagina existe y va a
     * publicarse en cuanto el titular complete la configuracion. Es la senal que
     * corresponde para que un buscador reintente mas tarde en lugar de olvidar
     * la URL.
     *
     * Debe llamarse antes de incluir el header, porque envia cabeceras.
     */
    function flus_legal_require_ready(): void
    {
        $missing = flus_legal_missing();

        if ($missing === []) {
            return;
        }

        error_log('[FLUS Web] paginas legales no publicadas: faltan datos legales en '
            . 'includes/config.local.php -> ' . implode(', ', $missing));

        if (!headers_sent()) {
            http_response_code(503);
            header('Retry-After: 86400');
            header('Content-Type: text/html; charset=UTF-8');
        }

        // Pagina minima autocontenida: se evita depender del header del sitio
        // para no arriesgar salida a medio construir.
        ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, follow">
  <title>Información legal no disponible | FLUS</title>
  <style>
    :root { color-scheme: dark; }
    body {
      margin: 0;
      min-height: 100vh;
      display: grid;
      place-items: center;
      padding: 24px;
      background: #0b0715;
      color: #f8f5ec;
      font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
      line-height: 1.6;
    }
    .box { max-width: 560px; text-align: center; }
    h1 { font-size: 1.5rem; margin: 0 0 12px; letter-spacing: -0.02em; }
    p { margin: 0 0 12px; color: rgba(248, 245, 236, 0.82); }
    a { color: #45e0ae; }
    .note { font-size: 0.9rem; color: rgba(248, 245, 236, 0.6); margin-top: 24px; }
  </style>
</head>
<body>
  <div class="box">
    <h1>Información legal temporalmente no disponible</h1>
    <p>
      Estamos terminando de cargar los datos legales de FLUS. Esta página va a
      publicarse en cuanto estén completos.
    </p>
    <p>
      Podés volver al <a href="/">inicio</a> o
      <a href="/contacto.php">escribirnos</a> si necesitás algo mientras tanto.
    </p>
    <p class="note">Gracias por la paciencia.</p>
  </div>
</body>
</html>
        <?php
        exit;
    }

    /**
     * Meses de conservacion EFECTIVOS de la analitica propia.
     *
     * Precedencia, de mayor a menor:
     *   1. analytics_retention_months en includes/config.local.php (fuente central,
     *      la misma que declara la politica de privacidad);
     *   2. security/analytics en admin/config/config.php (respaldo para
     *      instalaciones que ya lo tuvieran configurado);
     *   3. 14 meses (maximo que ofrece GA4 para datos de eventos).
     *
     * La usa web_analytics_purge_expired_events() para que la politica y el
     * comportamiento real no puedan divergir.
     */
    function flus_analytics_retention_months(): int
    {
        $months = flus_legal_int('analytics_retention_months');
        if ($months > 0) {
            return $months;
        }

        if (function_exists('web_analytics_config')) {
            $fallback = (int) web_analytics_config('retention_months', 0);
            if ($fallback > 0) {
                return $fallback;
            }
        }

        return 14;
    }

    /**
     * Texto neutro para un dato opcional que no este cargado.
     * Evita exponer marcadores internos al publico.
     */
    function flus_legal_or(string $key, string $fallback): string
    {
        $value = flus_legal($key);
        return $value !== '' ? $value : $fallback;
    }
}
