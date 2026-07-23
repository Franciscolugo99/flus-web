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
instalaciones limpias. En instalaciones existentes, el panel la crea de forma
idempotente la primera vez que se abre el dashboard o se cambia una licencia.

## Sincronizacion cloud de sucursales

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
`admin/cloud-sync.php` permite ver instalaciones, sucursales, ultimo contacto y
eventos recientes desde el panel interno.

Para actualizar una instalacion existente, importar `admin/database/cloud_sync.sql`
con un usuario MySQL con permisos de esquema. Despues de eso, el usuario normal
de la aplicacion puede seguir limitado a operar datos; el panel solo verifica
que las tablas existan y no necesita crear tablas en cada request.

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
