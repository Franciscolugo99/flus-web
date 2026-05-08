# FLUS Web

Sitio institucional de FLUS - sistema de gestion comercial.

## Como usarlo en local

1. Extrae el contenido dentro de `C:\xampp\htdocs\flus-web`
2. Abri `http://localhost/flus-web/`

## Antes de publicar en Wiroos

### 1. Configurar correo, contacto y captcha

Copia `includes/config.local.php.example` como `includes/config.local.php` y completa los datos reales:

- `mail_host` - servidor SMTP de Wiroos (ej: `mail.flus.com.ar`)
- `mail_username` y `mail_password` - credenciales del correo
- `contact_email`, `contact_phone`, `whatsapp_number`
- `turnstile_site_key` y `turnstile_secret_key` si queres activar Cloudflare Turnstile

`config.local.php` esta en `.gitignore` y no se sube al repo. Solo existe en el servidor.

### 2. Subir a Wiroos

- Subi todo el contenido a `public_html/`
- Verifica que `.htaccess` este subido (algunos clientes FTP lo ocultan)
- Activa el certificado SSL en el panel de Wiroos
- Cuando SSL este activo, descomenta el bloque de redirect HTTPS en `.htaccess`

### 3. Verificar despues de publicar

- `https://flus.com.ar/robots.txt` debe responder
- `https://flus.com.ar/sitemap.xml` debe responder
- Envia un mensaje de prueba desde el formulario de contacto
- Si Turnstile esta configurado, comproba que el captcha aparezca y que el formulario no envie sin validarlo

## Archivos principales

| Archivo | Funcion |
|---|---|
| `index.php` | Home principal |
| `sistema-de-gestion.php` | Pagina SEO - sistema de gestion |
| `sistema-pos.php` | Pagina SEO - POS |
| `control-de-stock.php` | Pagina SEO - control de stock |
| `facturacion.php` | Pagina SEO - facturacion |
| `contacto.php` | Contacto y formulario demo |
| `includes/bootstrap.php` | Configuracion base (sin credenciales) |
| `includes/config.local.php` | Credenciales reales (no en repo) |
| `assets/css/styles.css` | Estilos |
| `assets/js/main.js` | Scripts |
| `sitemap.xml` | Sitemap para Google |
