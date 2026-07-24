# FLUS Admin

Panel privado para gestionar clientes, licencias, pagos, vencimientos y descargas de FLUS.

## Instalacion local

1. Crear una base MySQL para el panel.
2. Importar `admin/database/schema.sql`.
3. Copiar `admin/config/config.local.php.example` como
   `admin/config/config.local.php`.
4. Configurar un usuario MySQL exclusivo, con permisos solo sobre la base del panel.
5. Crear el primer usuario administrador desde consola:

```powershell
& "C:\xampp\php\php.exe" "C:\xampp\htdocs\flus-web\admin\tools\create_admin.php" martin martin@flus.com.ar "ClaveSegura123" "Martin"
```

6. Entrar a `http://localhost/flus-web/admin/login.php`.

## Generador de licencias

Antes de descargar archivos de licencia firmados, configurar una passphrase
en `admin/config/config.local.php` y crear el par RSA:

```powershell
& "C:\xampp\php\php.exe" "C:\xampp\htdocs\flus-web\admin\tools\create_license_keys.php"
```

La clave privada queda en `admin/config/license-private.pem` y nunca debe
subirse al repositorio ni incluirse en paquetes publicos. La clave publica
debe coincidir con la incorporada en FLUS para validar la firma.

Los archivos descargados usan el contrato `FLUS-RSA-LICENSE-1` con RSA-SHA256.

## Validacion cloud

El endpoint `admin/api/license-check.php` recibe consultas POST de FLUS y
devuelve un documento `FLUS-CLOUD-LICENSE-1` firmado con RSA-SHA256. El estado
se toma de la tabla `licenses`:

- `activa`, `por_vencer` y `demo` responden `active`.
- `suspendida` responde `suspended`.
- licencias vencidas responden `expired`.
- una clave no registrada responde `revoked`.

Para exigir token compartido, configurar `license.cloud_api_token` en
`admin/config/config.local.php` y el mismo valor como `FLUS_LICENSE_CLOUD_TOKEN`
en la instalacion FLUS.

## Control operativo

El listado de licencias muestra el estado que va a recibir FLUS desde la API
cloud y permite suspender o reactivar con motivo administrativo. Cada cambio
queda registrado en `license_events` con usuario, motivo, estado anterior,
estado nuevo y fecha.

La tabla `license_events` esta incluida en `admin/database/schema.sql` para
instalaciones limpias. En instalaciones existentes, importar
`admin/database/license_events.sql` con un usuario MySQL con permisos de
esquema. El usuario normal de la aplicacion puede quedar limitado a operar
datos; el panel verifica que la tabla exista y solo intenta crearla como
compatibilidad para entornos de desarrollo.

Ejemplo local con XAMPP:

```powershell
& "C:\xampp82\mysql\bin\mysql.exe" -u root "flus-licenciadb" -e "SOURCE C:/xampp82/htdocs/flus-web/admin/database/license_events.sql;"
```

## Sincronizacion cloud de sucursales

La sincronizacion y el portal cliente son una capacidad de planes cloud. En el
alta o edicion de licencia usar:

- `Local mensual` / `Local anual`: FLUS opera en la PC del comercio sin portal.
- `Cloud mensual`: habilita portal, ventas recientes, stock y una sucursal.
- `Cloud multi-sucursal`: habilita portal y lectura consolidada por sucursal.

El endpoint rechaza eventos de licencias locales con `LICENSE_CLOUD_DISABLED`.
Esto permite vender FLUS Local y FLUS Cloud con precios distintos sin depender
solo de la configuracion de la PC del cliente.

El primer contrato para conectar sucursales vive en
`admin/api/sync-ingest.php`. FLUS local debe enviar eventos resumidos por POST,
usando el mismo token configurado en `license.cloud_api_token`:

```json
{
  "license_key": "FLUS-XXXX-XXXX-XXXX",
  "installation_id": "pc-caja-01",
  "app_version": "4.2.4",
  "device_label": "Caja principal",
  "branch": {
    "code": "casa-central",
    "name": "Casa central"
  },
  "events": [
    {
      "event_uid": "venta-123",
      "event_type": "sale_created",
      "occurred_at": "2026-07-23T12:00:00-03:00",
      "summary": {
        "total": 15600,
        "payment_method": "efectivo"
      }
    }
  ]
}
```

El endpoint:

- exige token cloud; si no esta configurado responde `CLOUD_TOKEN_NOT_CONFIGURED`;
- valida que la licencia exista y este operativa;
- asocia cada evento al `client_id` real de la licencia, nunca al navegador;
- registra sucursal e instalacion en forma idempotente;
- evita duplicados por `installation_id + event_uid`;
- guarda solo eventos recibidos, sin ejecutar acciones sobre la caja local.

Las tablas estan incluidas en `admin/database/schema.sql` y tambien en
`admin/database/cloud_sync.sql` para actualizar instalaciones existentes. La
base de usuarios de clientes queda separada en usuarios y membresias, para que
una cuenta pueda administrar uno o mas negocios sin mezclar datos. La pantalla
`admin/cloud-sync.php` funciona primero como indice operativo por cliente:
plan, sucursales, instalaciones, estado online y ultimo contacto. Los datos
comerciales no se muestran agrupados en la vista global; aparecen solo al
entrar a `Ver datos` de un cliente.

Dentro del detalle filtrado por cliente, `admin/cloud-sync.php` separa la
lectura en:

- `Operacion`: sucursales e instalaciones conectadas.
- `Ventas`: actividad comercial de las ultimas 24 hs y medios de pago.
- `Tecnico`: eventos recibidos para auditoria de sincronizacion.

La ficha `admin/client-view.php` tambien muestra las sucursales cloud activas
del cliente y enlaza al detalle filtrado de sus datos.

## Portal de clientes

El portal de clientes vive en `portal/` y permite que cada comercio vea solo
sus datos sincronizados:

- ventas e importe de las ultimas 24 hs;
- medios de pago;
- stock por sucursal, de solo lectura;
- productos sin stock o bajo minimo;
- sucursales e instalaciones conectadas;
- licencia vigente;
- ultimas ventas recibidas.

El portal no importa historico automaticamente. Las ventas, stock y estados se
muestran desde que la instalacion local queda actualizada, con Cloud activo y
empieza a enviar eventos. Para traer datos anteriores haria falta una carga
historica controlada aparte.

El aislamiento se hace por `client_portal_memberships.client_id`. El admin ve
todos los clientes desde `admin/cloud-sync.php`, pero un cliente del portal solo
consulta el negocio asociado a su membresia activa.

Para crear o actualizar un acceso de cliente desde consola:

```powershell
& "C:\xampp\php\php.exe" "C:\xampp\htdocs\flus-web\admin\tools\create_client_portal_user.php" 1 cliente@negocio.com "ClaveSegura123" "Nombre Cliente"
```

Tambien se puede administrar desde la ficha del cliente, en `Clientes > Ver`.
El bloque `Accesos al portal` permite crear o actualizar el acceso, activar o
desactivar la membresia y resetear la contraseña. FLUS Admin solo permite crear
o activar accesos si el cliente tiene un plan cloud.

Roles del portal:

- `owner` / Dueño: ve ventas, importes, medios de pago, sucursales y stock.
- `manager` / Encargado: ve ventas, importes, medios de pago, sucursales y stock.
- `viewer` / Consulta operativa: ve sucursales, estado de conexion y stock; no ve ventas ni importes.

Luego entrar a:

```text
http://localhost/flus-web/portal/login.php
```

Para actualizar una instalacion existente, importar `admin/database/cloud_sync.sql`
con un usuario MySQL con permisos de esquema. Despues de eso, el usuario normal
de la aplicacion puede seguir limitado a operar datos; el panel solo verifica
que las tablas existan y no necesita crear tablas en cada request.

Checklist para conectar una instalacion FLUS nueva:

1. Crear cliente y licencia en este panel con plan `Cloud mensual` o
   `Cloud multi-sucursal`.
2. Dejar configurado `license.cloud_api_token` en `admin/config/config.local.php`.
3. Cargar la licencia en la PC local de FLUS.
4. En el `src/config.php` local, configurar `FLUS_LICENSE_CLOUD_URL`,
   `FLUS_LICENSE_CLOUD_TOKEN`, `FLUS_CLOUD_SYNC_ENABLED` y
   `FLUS_CLOUD_SYNC_URL`.
5. Ejecutar migraciones locales de FLUS.
6. Hacer una venta de prueba y enviar pendientes desde el panel tecnico local.
7. Confirmar aca, en `admin/cloud-sync.php`, que el cliente aparezca en
   `Clientes cloud`.
8. Entrar con `Ver datos` y revisar:
   - `Operacion`: sucursales e instalacion.
   - `Ventas`: venta recibida y medios de pago.
   - `Tecnico`: eventos aceptados.
9. Entrar a la ficha del cliente y confirmar el bloque `Sucursales cloud`.
10. Desde el tecnico local de FLUS, usar `Enviar stock actual` para cargar el
   primer inventario visible en el portal.
11. Para automatizar, programar en Windows el script local
   `scripts/cloud_sync_tick.php` cada 5 minutos.

Este portal queda pensado para planes cloud o multi-sucursal. El cliente puede
consultar ventas, sucursales y stock desde el celular, pero no modifica datos
operativos de la sucursal.

## Avisos por email

Configurar SMTP en `admin/config/config.local.php`, dentro de la clave `mail`.
Para probar sin enviar:

```powershell
& "C:\xampp\php\php.exe" "C:\xampp\htdocs\flus-web\admin\tools\send_license_notifications.php" --mode=due --dry-run
```

Para enviar un correo de prueba a una licencia concreta:

```powershell
& "C:\xampp\php\php.exe" "C:\xampp\htdocs\flus-web\admin\tools\send_license_notifications.php" --mode=test --license-key=FLUS-XXXX-XXXX-XXXX
```

Para enviar avisos reales de vencimientos:

```powershell
& "C:\xampp\php\php.exe" "C:\xampp\htdocs\flus-web\admin\tools\send_license_notifications.php" --mode=due --days=15,7,3,1,0
```

Cada envio queda registrado en `license_notifications` para evitar duplicados
del mismo vencimiento.

## Seguridad

- `admin/config`, `admin/database` y `admin/tools` bloquean acceso web directo.
- Los secretos viven en archivos `config.local.php` ignorados por Git.
- Login, contacto y analitica tienen limites de frecuencia.
- Produccion debe forzar HTTPS y conservar HSTS activo.
