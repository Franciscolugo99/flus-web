<?php
/**
 * FLUS Bot: puerta de publicación independiente de la documentación del sitio.
 *
 * Una política de privacidad general para flus.com.ar no cubre por sí sola
 * los mensajes de clientes de los comercios atendidos por FLUS Bot.
 *
 * Hasta completar la identidad del responsable, los términos operativos
 * y una revisión humana de los textos, los documentos no deben usarse en Meta.
 */
require_once __DIR__ . '/legal.php';

if (!function_exists('flus_bot_docs_ready')) {
    function flus_bot_docs_ready(): bool
    {
        global $site;

        if (!flus_legal_ready() || ($site['bot_legal_approved'] ?? false) !== true) {
            return false;
        }

        foreach (['bot_infrastructure_description', 'bot_retention_description', 'bot_responsibilities_description'] as $key) {
            if (trim((string)($site[$key] ?? '')) === '') {
                return false;
            }
        }

        return true;
    }

    function flus_bot_require_docs_ready(): void
    {
        if (flus_bot_docs_ready()) {
            return;
        }

        if (!headers_sent()) {
            http_response_code(503);
            header('Retry-After: 86400');
            header('Content-Type: text/html; charset=UTF-8');
            header('X-Robots-Tag: noindex, nofollow');
        }
        ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Documento en preparación | FLUS Bot</title>
  <style>
    body{min-height:100vh;margin:0;display:grid;place-items:center;padding:24px;box-sizing:border-box;background:#130c24;color:#f9f6ff;font:16px/1.6 system-ui,sans-serif}
    main{max-width:580px}a{color:#c5afff}h1{line-height:1.2}p{color:#e2d8f1}
  </style>
</head>
<body>
<main>
  <h1>Documentación de FLUS Bot en preparación</h1>
  <p>Estamos verificando la información operativa y legal antes de publicar este documento. Esta dirección todavía no debe utilizarse para la revisión de Meta ni para habilitar un servicio comercial.</p>
  <p>Podés consultar el <a href="/centro-de-confianza.php">Centro de Confianza</a> o escribirnos desde la <a href="/contacto.php">página de contacto</a>.</p>
</main>
</body>
</html>
        <?php
        exit;
    }
}
