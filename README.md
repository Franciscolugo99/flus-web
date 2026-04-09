# FLUS Web

Base inicial del sitio institucional de FLUS.

## Cómo usarlo en local

1. Extraé este contenido dentro de `C:\xampp\htdocs\flus-web`
2. Abrí `http://localhost/flus-web/`
3. Editá los datos de contacto en `includes/bootstrap.php`

## Archivos importantes

- `index.php`: home principal
- `sistema-de-gestion.php`: página SEO para sistema de gestión
- `sistema-pos.php`: página SEO para POS
- `control-de-stock.php`: página SEO para stock
- `facturacion.php`: página SEO para facturación
- `contacto.php`: página de contacto/demo
- `includes/bootstrap.php`: base path + datos de contacto

## Antes de publicar en Wiroos

- Cargar contacto real del dominio
- Reemplazar textos genéricos por capturas y beneficios reales de FLUS
- Subir el contenido a `public_html`
- Verificar que `robots.txt` y `sitemap.xml` queden accesibles
