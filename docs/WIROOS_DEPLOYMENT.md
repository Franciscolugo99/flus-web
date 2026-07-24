# Despliegue FLUS Web En Wiroos

Esta guia prepara `flus-web` para operar el panel admin, licencias cloud,
sincronizacion de sucursales y portal de clientes desde Wiroos.

## Regla Principal

Si la base ya tiene licencias reales, no hacer acciones destructivas.

No ejecutar:

- `DROP DATABASE`
- `DROP TABLE`
- importar `admin/database/schema.sql` encima de una base productiva existente;
- regenerar `admin/config/license-private.pem`;
- reemplazar `admin/config/license-public.pem` sin confirmar que FLUS local usa la misma clave publica;
- subir `config.local.php` del entorno local si contiene datos de prueba.

Antes de tocar produccion, descargar backup completo de base y archivos.

## Archivos Sensibles

Estos archivos viven solo en el servidor y no deben ir por Git:

- `admin/config/config.local.php`
- `admin/config/license-private.pem`
- `admin/config/license-public.pem`
- `admin/config/license.passphrase.local`
- `includes/config.local.php`

En Wiroos deben quedar protegidos por:

- `.htaccess` de la raiz, que bloquea `.pem`, `.sql`, zips y archivos ocultos;
- `admin/config/.htaccess`, que bloquea acceso web directo a la carpeta;
- `admin/database/.htaccess`;
- `admin/tools/.htaccess`.

## Instalacion Limpia

Usar solo cuando Wiroos todavia no tenga clientes ni licencias reales.

1. Crear base MySQL desde el panel de Wiroos.
2. Crear usuario MySQL exclusivo para FLUS Web.
3. Importar `admin/database/schema.sql`.
4. Subir el repo a la carpeta publica del dominio.
5. Crear `admin/config/config.local.php` desde `admin/config/config.local.php.example`.
6. Configurar DB, SMTP, `rate_limit_salt`, `cloud_api_token` y passphrase.
7. Crear o copiar el par RSA de licencias.
8. Crear el primer admin:

```powershell
php admin/tools/create_admin.php martin martin@flus.com.ar "ClaveLargaSegura123" "Martin"
```

9. Ejecutar preflight:

```powershell
php admin/tools/production_preflight.php
```

## Actualizacion De Una Instalacion Existente

Usar cuando Wiroos ya tiene licencias productivas.

1. Descargar backup de la base desde Wiroos.
2. Descargar backup de:
   - `admin/config/config.local.php`;
   - `admin/config/license-private.pem`;
   - `admin/config/license-public.pem`;
   - archivos subidos manualmente si existieran.
3. Subir solo archivos del repo actualizado.
4. No sobrescribir `admin/config/config.local.php` ni las llaves RSA.
5. Si falta una tabla nueva, importar solo el SQL puntual documentado para esa
   funcion. Para Cloud/portal existentes, usar `admin/database/cloud_sync.sql`.
6. Ejecutar:

```powershell
php admin/tools/production_preflight.php
```

7. Entrar al admin y comprobar:
   - cantidad de clientes;
   - cantidad de licencias;
   - estados `activa`, `suspendida`, `vencida`;
   - descarga de una licencia de prueba;
   - consulta cloud desde una PC FLUS de prueba;
   - portal cliente con usuario de prueba.

## Configuracion Minima De `admin/config/config.local.php`

No copiar valores literales de desarrollo. Completar con datos reales de Wiroos.

```php
<?php
declare(strict_types=1);

return [
    'db' => [
        'host' => 'localhost',
        'name' => 'BASE_WIROOS',
        'user' => 'USUARIO_WIROOS',
        'pass' => 'CLAVE_WIROOS',
        'charset' => 'utf8mb4',
    ],
    'license' => [
        'private_key_passphrase' => 'PASSPHRASE_REAL',
        'cloud_api_token' => 'TOKEN_LARGO_COMPARTIDO_CON_FLUS_LOCAL',
        'cloud_check_interval_sec' => 300,
    ],
    'mail' => [
        'host' => 'mail.flus.com.ar',
        'port' => 465,
        'secure' => 'ssl',
        'username' => 'soporte@flus.com.ar',
        'password' => 'CLAVE_CORREO_WIROOS',
        'from_email' => 'soporte@flus.com.ar',
        'from_name' => 'FLUS Soporte',
        'reply_to' => 'soporte@flus.com.ar',
        'public_base_url' => 'https://flus.com.ar',
        'timeout' => 15,
    ],
    'security' => [
        'rate_limit_salt' => 'VALOR_ALEATORIO_LARGO',
    ],
];
```

## Licencias

Las licencias descargadas y las respuestas cloud dependen del par RSA.

- La clave privada firma desde `flus-web`.
- La clave publica debe coincidir con la que valida FLUS local.
- Si se reemplaza el par RSA, las instalaciones que tienen la clave publica
  anterior pueden dejar de validar licencias nuevas.

Por eso, en produccion:

1. No regenerar llaves si ya hay clientes usando licencias.
2. No subir llaves locales de prueba.
3. Si alguna vez hay que rotar llaves, hacerlo como plan separado:
   version nueva de FLUS local, periodo de compatibilidad y prueba con licencia demo.

## Cloud Sync

Endpoints que deben responder en Wiroos:

- Admin: `https://flus.com.ar/admin/login.php`
- API licencia: `https://flus.com.ar/admin/api/license-check.php`
- API sincronizacion: `https://flus.com.ar/admin/api/sync-ingest.php`
- Portal cliente: `https://flus.com.ar/portal/login.php`

La PC local FLUS debe usar el mismo token configurado en:

- Wiroos: `admin/config/config.local.php`, clave `license.cloud_api_token`;
- FLUS local: `FLUS_LICENSE_CLOUD_TOKEN` y `FLUS_CLOUD_SYNC_URL`.

## Prueba Controlada En Produccion

Hacer primero con una licencia demo o un cliente interno.

1. Crear cliente demo.
2. Crear licencia `Cloud mensual` o `Cloud multi-sucursal`.
3. Descargar licencia.
4. Cargarla en una PC de prueba.
5. Hacer una venta chica.
6. Ejecutar envio cloud local.
7. Verificar en Wiroos:
   - `Clientes`: estado Cloud/Operacion;
   - `Clientes > Ver`: situacion cloud;
   - `Sucursales cloud > Ver datos`: operacion, ventas y tecnico;
   - `Portal`: ventas y stock visibles segun rol.

## Preflight De Produccion

El preflight es de solo lectura. No crea tablas y no modifica licencias.

```powershell
php admin/tools/production_preflight.php
```

Debe revisar:

- config local;
- conexion DB;
- tablas principales;
- llaves de licencia;
- token cloud;
- conteo de clientes, licencias e instalaciones.

Si marca `FAIL`, no avanzar hasta resolverlo.
Si marca `WARN`, se puede evaluar caso por caso, pero no ignorarlo sin motivo.

## Orden Seguro Para Primer Paso En Wiroos

1. Subir archivos.
2. Crear `config.local.php` en Wiroos.
3. Copiar o conservar llaves RSA reales.
4. Importar schema solo si la base esta vacia.
5. Ejecutar preflight.
6. Crear admin si es instalacion limpia.
7. Entrar al panel.
8. Probar licencia demo.
9. Recien despues conectar una instalacion FLUS real.
