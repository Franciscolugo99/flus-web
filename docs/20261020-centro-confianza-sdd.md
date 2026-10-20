# SDD — FLUS Centro de Confianza (20/10/2026)

## Objetivo y contexto
FLUS es una marca comercial utilizada por una persona que aún no tiene alta tributaria ni oficina comercial. El sitio `flus.com.ar` corre en Wiroos y la integración del bot usa infraestructura y servicios distintos. No se deben inventar domicilio, CUIT, razón social, certificaciones, medidas de seguridad ni plazos de conservación.

**Fuentes de partida:** rama `feature/flus-com-ar-ux-polish-20261005`, `includes/legal.php`, `includes/legal.local.php.example`, páginas legales existentes. La rama de referencia estaba 9 commits por delante de `main` al comenzar, sin commits posteriores en `main` según la comparación de ese día.

## Contrato de publicación
- `centro-de-confianza.php`: página informativa visible sin identidad fiscal. No es la política de privacidad exigida a una aplicación comercial.
- `flus-bot-eliminar-datos.php`: instrucciones públicas para solicitar acceso, corrección o supresión, sin prometer automatización. No recopila ni almacena nuevos datos en formularios.
- `politica-de-privacidad.php` y `aviso-legal.php`: mantienen la protección existente `flus_legal_require_ready()`. Si falta información, HTTP 503.
- `flus-bot-privacidad.php` y `flus-bot-terminos.php`: además de `flus_legal_ready()`, exigen aprobación explícita y datos operativos del bot, configurados fuera de GitHub. Hasta entonces HTTP 503 con `noindex` y `Retry-After`.
- El Centro no enlaza las políticas no publicadas. El footer no enlaza aviso legal ni privacidad general mientras falten sus datos. El sitemap solo agrega las páginas inmediatamente publicables, no las políticas del bot bloqueadas.
- No se cambia `main`, no se despliega el sitio y no se toca BoT-IA ni Meta.

## Configuración en Wiroos
1. En `public_html/includes/legal.local.php`, completar los datos reales requeridos según `legal.local.php.example`. Los campos corresponden al titular real, no a una sociedad inventada. No escribir valores falsos para sortear el bloqueo.
2. Para los documentos de FLUS Bot, agregar a `public_html/includes/config.local.php` claves locales (no versionadas):
   - `'bot_legal_approved' => false` (solo pasar a `true` cuando los textos y roles hayan sido revisados).
   - `'bot_infrastructure_description' => ''` (proveedores, regiones y transferencias reales del bot; no confundir Wiroos con Railway).
   - `'bot_retention_description' => ''` (plazos y purga verdaderos para todas las clases de datos, incluyendo mensajes, medios, backups y logs).
   - `'bot_responsibilities_description' => ''` (roles FLUS/comercio y legitimación de tratamiento acordados con el cliente).
3. Si falta cualquier valor, los documentos del bot seguirán temporalmente inaccesibles por diseño.
4. Completar fecha de revisión, contrato y responsables del canal de soporte. `contact_email` debe ser un buzón monitorizado.

## Pendientes bloqueantes para publicar una política de Meta
- Identificar al responsable con nombre/domicilio reales. La Ley 25.326, art. 6, requiere informar la identidad y domicilio de la persona responsable al recoger datos.
- Verificar y documentar la relación FLUS/comercio, el responsable concreto de cada tratamiento y, cuando aplique, una política del comercio que utiliza la cuenta WhatsApp.
- Auditar el despliegue activo de `Franciscolugo99/BoT-IA`, no solo README o una rama histórica: proveedores, almacenamiento, accesos, mensajería, DeepSeek, archivos multimedia, conservación y purga real.
- Confirmar los destinos internacionales, avisos, consentimientos y procedimientos de ejercicio de derechos.
- Revisar los textos con asesoramiento legal apropiado antes de activar `bot_legal_approved`.
- No suministrar a Meta una URL con HTTP 503. Comprobar respuesta 200 vía HTTPS sin iniciar sesión.

## Validación sugerida
- `php -l` en todos los nuevos archivos PHP y en `includes/footer.php`.
- HTML y accesibilidad: foco visible, contraste, enlaces, responsive móvil.
- Con configuración incompleta: Centro y eliminación HTTP 200; dos documentos bot y políticas generales HTTP 503; no aparecer como disponibles en la página central.
- Con configuración real y aprobación: políticas HTTP 200, con los datos y plazos correctos.
- Contacto: enviar una consulta real y verificar llegada al buzón, con datos mínimos.
- Integridad: navegación del inicio, consentimiento cookies, footer, formularios y HTTPS; inspeccionar si la infraestructura mantiene `X-Robots-Tag`.
- Revisar manualmente el despliegue en Wiroos y la relación entre rama de código y copia publicada.

## Fuentes públicas
- Política de mensajes WhatsApp Business: https://business.whatsapp.com/policy/preview?lang=es_LA
- Ley argentina 25.326: https://www.argentina.gob.ar/normativa/nacional/ley-25326-64790/texto

## Estado
Implementación en rama de trabajo. **NO-GO legal para URLs de Meta** y **NO-GO de despliegue de bot** hasta completar y verificar lo anterior.
