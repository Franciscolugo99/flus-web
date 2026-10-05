<?php
/**
 * FLUS · Gestion de consentimiento de cookies y analitica.
 *
 * Contexto: el sitio carga Google Analytics 4 y una analitica propia
 * (assets/js/site-analytics.js -> track.php). Ambos son NO esenciales, por lo
 * que no deben activarse hasta que la persona acepte.
 *
 * Modelo:
 *   - Se guarda la decision en la cookie de primera parte "flus_consent".
 *   - Valores: "granted" | "denied".
 *   - GA4 NO se inyecta desde el HTML: analytics.js decide si cargarlo.
 *   - Sin decision previa no se carga nada (opt-in real).
 *
 * El endpoint de la analitica propia se publica siempre (es solo una URL),
 * pero analytics.js no envia nada sin consentimiento.
 */

if (!function_exists('flus_consent_state')) {
    /**
     * Lee la decision guardada. Devuelve 'granted', 'denied' o '' si no hay.
     */
    function flus_consent_state(): string
    {
        $raw = (string) ($_COOKIE['flus_consent'] ?? '');
        return in_array($raw, ['granted', 'denied'], true) ? $raw : '';
    }

    /** GA4 solo se carga con consentimiento explicito. */
    function flus_analytics_allowed(): bool
    {
        return flus_consent_state() === 'granted';
    }

    /**
     * Config para el JS de consentimiento/analitica.
     * No incluye secretos: el Measurement ID es un identificador publico.
     */
    function flus_consent_config(): array
    {
        global $site;

        $ga4 = trim((string) ($site['ga4_measurement_id'] ?? ''));

        return [
            'state' => flus_consent_state(),
            'ga4' => preg_match('/^G-[A-Z0-9]+$/', $ga4) === 1 ? $ga4 : '',
            'analyticsEndpoint' => site_url('track.php'),
            'cookiePolicyUrl' => site_url('politica-de-cookies.php'),
            'version' => 1,
        ];
    }

    /** Script de configuracion. Debe emitirse temprano en el <head>. */
    function flus_consent_bootstrap_script(): string
    {
        $json = json_encode(
            flus_consent_config(),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        return '<script>window.FLUS_CONSENT_CONFIG = ' . $json . ';</script>';
    }
}
