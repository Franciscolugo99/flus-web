# FLUS Web

Sitio institucional de FLUS — sistema de gestión comercial.

## Cómo usarlo en local

1. Extraé el contenido dentro de `C:\xampp\htdocs\flus-web`
2. Abrí `http://localhost/flus-web/`

## Antes de publicar en Wiroos

### 1. Configurar correo y contacto

Copiá `includes/config.local.php.example` como `includes/config.local.php` y completá los datos reales:

- `mail_host` — servidor SMTP de Wiroos (ej: `mail.flus.com.ar`)
- `mail_username` y `mail_password` — credenciales del correo
- `contact_email`, `contact_phone`, `whatsapp_number`

> ⚠️ `config.local.php` está en `.gitignore` y no se sube al repo. Solo existe en el servidor.

### 2. Subir a Wiroos

- Subí todo el contenido a `public_html/`
- Verificá que `.htaccess` esté subido (algunos clientes FTP lo ocultan)
- Activá el certificado SSL en el panel de Wiroos
- Cuando SSL esté activo, descomentá el bloque de redirect HTTPS en `.htaccess`

### 3. Verificar después de publicar

- `https://flus.com.ar/robots.txt` — debe responder
- `https://flus.com.ar/sitemap.xml` — debe responder
- Enviá un mensaje de prueba desde el formulario de contacto

## Archivos principales

| Archivo | Función |
|---|---|
| `index.php` | Home principal |
| `sistema-de-gestion.php` | Página SEO — sistema de gestión |
| `sistema-pos.php` | Página SEO — POS |
| `control-de-stock.php` | Página SEO — control de stock |
| `facturacion.php` | Página SEO — facturación |
| `contacto.php` | Contacto y formulario demo |
| `includes/bootstrap.php` | Configuración base (sin credenciales) |
| `includes/config.local.php` | Credenciales reales (no en repo) |
| `assets/css/styles.css` | Estilos |
| `assets/js/main.js` | Scripts |
| `sitemap.xml` | Sitemap para Google |
