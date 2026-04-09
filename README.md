# FLUS Web

Base inicial del sitio institucional de FLUS.

## Como usarlo en local

1. Extrae este contenido dentro de `C:\xampp\htdocs\flus-web`
2. Abri `http://localhost/flus-web/`
3. Edita los datos de contacto en `includes/bootstrap.php`

## Archivos importantes

- `index.php`: home principal
- `sistema-de-gestion.php`: pagina SEO para sistema de gestion
- `sistema-pos.php`: pagina SEO para POS
- `control-de-stock.php`: pagina SEO para stock
- `facturacion.php`: pagina SEO para facturacion
- `contacto.php`: pagina de contacto/demo
- `includes/bootstrap.php`: base path + datos de contacto

## Antes de publicar en Wiroos

- Cargar contacto real del dominio
- Reemplazar textos genericos por capturas y beneficios reales de FLUS
- Subir el contenido a `public_html`
- Verificar que `robots.txt` y `sitemap.xml` queden accesibles
