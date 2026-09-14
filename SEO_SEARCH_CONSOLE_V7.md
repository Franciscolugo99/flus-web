# FLUS V7 — Search Console y medición

## 1. Google Search Console
1. Crear una propiedad de dominio para `flus.com.ar` (recomendado) o una propiedad URL-prefix `https://flus.com.ar/`.
2. Si usás verificación DNS, agregar en Wiroos el TXT que entregue Google. No hace falta tocar el código.
3. Si preferís verificación HTML/meta, copiar SOLO el token de `content` en `includes/config.local.php`:

```php
'google_site_verification' => 'TOKEN_QUE_DA_GOOGLE',
```

4. En Search Console enviar: `https://flus.com.ar/sitemap.xml`.
5. Inspeccionar manualmente estas URLs y solicitar indexación después del deploy:
   - `/`
   - `/flus-web.php`
   - `/flus-server.php`
   - `/sistema-de-gestion.php`
   - `/sistema-pos.php`
   - `/control-de-stock.php`
   - `/facturacion.php`

## 2. GA4 (opcional, preparado)
Crear una propiedad GA4 y agregar el Measurement ID en `includes/config.local.php`:

```php
'ga4_measurement_id' => 'G-XXXXXXXXXX',
```

Si queda vacío, no se carga ningún script de Google.

## 3. Eventos internos FLUS
El sitio ya registra page views, demo, WhatsApp, contacto y ahora también `lead_form_success`. Los UTM de primer contacto quedan persistidos durante la sesión para no perder atribución cuando el usuario navega antes de consultar.

## 4. Qué mirar semanalmente
- consultas no relacionadas con la marca (ej. sistema de gestión, POS, stock);
- páginas con muchas impresiones y CTR bajo;
- posiciones 5–30 para optimizar primero;
- demo/WhatsApp/formularios por landing;
- UTM source / medium / campaign.
