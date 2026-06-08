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

## Seguridad

- `admin/config`, `admin/database` y `admin/tools` bloquean acceso web directo.
- Los secretos viven en archivos `config.local.php` ignorados por Git.
- Login, contacto y analitica tienen limites de frecuencia.
- Produccion debe forzar HTTPS y conservar HSTS activo.
