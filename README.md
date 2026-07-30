# FLUS Web

Sitio institucional de FLUS - sistema de gestion comercial.

El portal de clientes incluye un listado unificado de ventas sincronizadas por
sucursal, periodo, producto, medio de pago y cajero. Muestra importe original,
anulaciones y neto vigente, permite abrir el detalle de productos y exportar el
resultado a CSV respetando los permisos de sucursal del usuario.

El portal tambien es instalable como aplicacion web (PWA) desde Chrome, Edge o
Safari. El service worker no guarda paginas autenticadas ni datos comerciales:
las consultas siguen saliendo siempre al servidor para mostrar informacion actual.
Para habilitar la instalacion en produccion se debe publicar completa la carpeta
`portal/` y acceder mediante HTTPS. En Android, Chrome ofrece `Instalar`; en iPhone,
Safari utiliza `Compartir > Agregar a inicio`.

La vista de stock puede leer codigos de barras con la camara del celular. Utiliza
`BarcodeDetector` cuando el navegador lo ofrece y carga localmente ZXing Browser
como alternativa. El lector solo completa la busqueda existente: no modifica stock
ni envia datos comerciales a un servicio externo. La copia distribuida de ZXing y
su licencia MIT estan en `portal/assets/vendor/zxing/`.

Dueno y encargado pueden abrir una demostracion de conteo desde cada producto. La
hoja movil calcula faltantes o sobrantes y permite elegir un motivo, pero no escribe
en la base ni envia comandos a las sucursales. La aplicacion real del ajuste queda
reservada para una futura cola auditada e idempotente en FLUS.

Dueno y encargado pueden enviar un cambio de precio a la sucursal del producto.
Wiroos valida permiso, CSRF, cliente, sucursal e instalacion, toma como referencia el
precio sincronizado y crea una orden idempotente. FLUS 4.2.10 vuelve a validar el
producto y el precio local, aplica el cambio dentro de una transaccion y registra el
historial una sola vez. Si el precio cambio localmente, la orden queda en conflicto y
no sobrescribe el valor mas nuevo.

## Meta y estado actual de precios remotos

La meta es operar cambios de precio desde el portal movil sin confiar en el
navegador y sin duplicar actualizaciones ante doble toque, timeout o reintento.
Cada orden queda limitada al cliente, sucursal, instalacion y licencia activos.

Al 30/07/2026, el flujo esta implementado y validado localmente junto con FLUS
4.2.10. Todavia no debe considerarse habilitado en produccion hasta crear la tabla
`cloud_commands`, publicar los endpoints de consulta y confirmacion, y completar
una prueba controlada con una sucursal piloto. No se requiere rotar el token cloud.

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
