# Web Analytics MVP para FLUS

## Archivos incluidos
- `track.php`
- `includes/web-analytics.php`
- `assets/js/site-analytics.js`
- `admin/database/web_events.sql`
- cambios en `admin/analytics.php`
- cambios en `includes/footer.php`, `includes/header.php` y CTAs públicos

## Pasos de instalación
1. Subir los archivos nuevos/modificados.
2. Importar `admin/database/web_events.sql` en la misma base usada por el admin.
3. Verificar que `track.php` responda con `405` si se abre por GET. Eso confirma que existe.
4. Abrir la web pública y navegar algunas páginas.
5. Entrar al admin en `Analíticas` y revisar el bloque `Web e interacciones`.

## Prueba manual rápida
1. Abrir la home y 2 o 3 páginas internas.
2. Hacer clic en:
   - Demo del menú
   - un botón de pedir demo
   - un enlace a contacto
   - un enlace de WhatsApp
3. En phpMyAdmin ejecutar:
   - `SELECT event_type, page_url, created_at FROM web_events ORDER BY id DESC LIMIT 20;`
4. Confirmar que aparecen eventos `page_view`, `click_demo`, `click_contact` y `click_whatsapp`.
5. Entrar al admin y revisar:
   - visitas hoy
   - visitas 7 días
   - clics WhatsApp 30 días
   - clics Contacto/Demo 30 días
   - páginas más vistas
   - gráfico diario últimos 30 días
