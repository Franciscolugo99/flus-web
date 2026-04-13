# FLUS Admin

Panel privado para gestionar clientes, licencias, pagos, vencimientos y descargas de FLUS.

## Instalación local

1. Crear una base MySQL para el panel.
2. Importar `admin/database/schema.sql`.
3. Configurar la conexión en `admin/config/config.php`.
4. Crear el primer usuario administrador desde consola:

```powershell
& "C:\xampp\php\php.exe" "C:\xampp\htdocs\flus-web\admin\tools\create_admin.php" martin martin@flus.com.ar "ClaveSegura123" "Martin"
```

5. Entrar a `http://localhost/flus-web/admin/login.php`.

## Generador de licencias

Antes de descargar archivos de licencia firmados, crear el par de claves RSA:

```powershell
& "C:\xampp\php\php.exe" "C:\xampp\htdocs\flus-web\admin\tools\create_license_keys.php"
```

El admin genera un archivo `.license.json` desde cada licencia registrada. La clave privada queda en `admin/config/license-private.pem` y no debe subirse al repositorio. La clave pública `admin/config/license-public.pem` es la que después debería usar la app de escritorio para validar la firma.

## Seguridad

`admin/config`, `admin/database` y `admin/tools` tienen `.htaccess` para bloquear acceso web directo. Los scripts de `admin/tools` también validan ejecución por CLI.
