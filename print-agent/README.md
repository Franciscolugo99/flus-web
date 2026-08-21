# FLUS Print Agent MVP

Objetivo de esta primera etapa: permitir que una venta originada desde FLUS Web en un celular termine imprimiendo en una impresora termica USB instalada en una PC Windows del comercio.

## Alcance actual

- Windows 10/11 con PowerShell 5.1 o superior.
- Impresora ya instalada y funcional en Windows (por ejemplo POS-80C, XPrinter, 3nStar usando su driver).
- Consulta saliente por HTTPS; no requiere abrir puertos del router.
- Autenticacion por agente con UID + token propio.
- Claim con lease, ACK, reintentos y proteccion contra dos agentes tomando el mismo trabajo.
- Impresion inicial mediante el spooler de Windows (`Out-Printer`).

Esta version NO implementa todavia ESC/POS RAW, corte automatico, apertura de cajon, instalador EXE, servicio de Windows ni CUPS/Ubuntu.

## 1. Crear tablas

Ejecutar `admin/database/print_queue.sql` en la base administrativa de FLUS Web.

## 2. Crear un agente

Generar un token aleatorio largo y guardar solo su SHA-256 en `print_agents`.

Ejemplo PowerShell para generar token y hash:

```powershell
$bytes = New-Object byte[] 32
[Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($bytes)
$token = [Convert]::ToBase64String($bytes)
$sha = [Security.Cryptography.SHA256]::Create()
$hash = ([BitConverter]::ToString($sha.ComputeHash([Text.Encoding]::UTF8.GetBytes($token)))).Replace('-','').ToLowerInvariant()
"TOKEN=$token"
"HASH=$hash"
```

Insertar el agente reemplazando IDs y hash reales:

```sql
INSERT INTO print_agents
(agent_uid, client_id, branch_id, display_name, token_hash, platform, status)
VALUES
('agent-caja-01', 1, 1, 'PC Caja 01', 'HASH_SHA256_REAL', 'windows', 'active');
```

El TOKEN en texto plano se guarda solamente en `print-agent/config.json` de esa PC.

## 3. Configurar la PC

Copiar `config.example.json` como `config.json` y completar:

- `server_base_url`: URL del subdominio API de FLUS.
- `agent_uid`: el mismo UID registrado en base.
- `agent_token`: token en texto plano generado para esa PC.
- `printer_name`: nombre exacto con el que Windows conoce la impresora.

Listar impresoras:

```powershell
powershell -ExecutionPolicy Bypass -File .\flus-print-agent.ps1 -ListPrinters
```

Probar la impresora local antes de involucrar FLUS:

```powershell
powershell -ExecutionPolicy Bypass -File .\flus-print-agent.ps1 -TestPrint
```

Ejecutar una sola consulta al servidor:

```powershell
powershell -ExecutionPolicy Bypass -File .\flus-print-agent.ps1 -Once
```

Ejecutar continuamente:

```powershell
powershell -ExecutionPolicy Bypass -File .\flus-print-agent.ps1
```

## 4. Endpoints

- `POST /print-poll.php`: entrega como maximo un trabajo y genera un claim temporal.
- `POST /print-ack.php`: confirma `printed` o `failed` usando el token de claim.

Headers requeridos:

```text
X-FLUS-Agent-ID: agent-caja-01
Authorization: Bearer TOKEN_DEL_AGENTE
```

## 5. Formato MVP del ticket

La cola acepta un `payload_json` con `text` para una prueba directa, o un ticket estructurado con campos como:

```json
{
  "business_name": "Comercio Demo",
  "ticket_number": "0001-00000125",
  "created_at": "2026-08-20 21:30",
  "items": [
    {"qty": 2, "name": "Producto A", "total": 3000}
  ],
  "total": 3000,
  "payment_method": "EFECTIVO",
  "footer": "Gracias por su compra"
}
```

## Seguridad

- No reutilizar `cloud_api_token` como token de impresion.
- Un token por PC/agente.
- Guardar solo SHA-256 en servidor.
- Revocar un equipo poniendo `print_agents.status = 'revoked'`.
- El agente solo puede reclamar trabajos del mismo cliente y sucursal que tiene asignados.
- No exponer MySQL, puerto RAW 9100 ni la PC del comercio a Internet.
