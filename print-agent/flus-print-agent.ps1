param(
    [string]$ConfigPath = "$PSScriptRoot\config.json",
    [switch]$ListPrinters,
    [switch]$TestPrint,
    [switch]$Once
)

$ErrorActionPreference = 'Stop'

function Get-FlusConfig {
    param([string]$Path)
    if (-not (Test-Path -LiteralPath $Path)) {
        throw "No existe el archivo de configuracion: $Path"
    }
    $raw = Get-Content -LiteralPath $Path -Raw -Encoding UTF8
    $cfg = $raw | ConvertFrom-Json
    foreach ($name in @('server_base_url','agent_uid','agent_token','printer_name')) {
        if ([string]::IsNullOrWhiteSpace([string]$cfg.$name)) {
            throw "Falta '$name' en config.json"
        }
    }
    if (-not $cfg.poll_seconds) { $cfg | Add-Member -NotePropertyName poll_seconds -NotePropertyValue 3 }
    return $cfg
}

function Get-InstalledPrinters {
    Get-Printer | Sort-Object Name | Select-Object Name, DriverName, PortName, PrinterStatus
}

function ConvertTo-TicketText {
    param([object]$Payload)

    if ($null -ne $Payload.text -and -not [string]::IsNullOrWhiteSpace([string]$Payload.text)) {
        return [string]$Payload.text
    }

    $lines = New-Object System.Collections.Generic.List[string]
    if ($Payload.business_name) { $lines.Add([string]$Payload.business_name) }
    if ($Payload.business_address) { $lines.Add([string]$Payload.business_address) }
    if ($Payload.ticket_number) { $lines.Add("Ticket: $($Payload.ticket_number)") }
    if ($Payload.created_at) { $lines.Add("Fecha: $($Payload.created_at)") }
    $lines.Add((''.PadLeft(32, '-')))

    if ($Payload.items) {
        foreach ($item in $Payload.items) {
            $qty = if ($null -ne $item.qty) { [string]$item.qty } else { '1' }
            $name = [string]$item.name
            $total = if ($null -ne $item.total) { ('{0:N2}' -f [double]$item.total) } else { '' }
            $lines.Add("$qty x $name")
            if ($total -ne '') { $lines.Add((''.PadLeft([Math]::Max(1, 32 - $total.Length), ' ') + $total)) }
        }
    }

    $lines.Add((''.PadLeft(32, '-')))
    if ($null -ne $Payload.total) { $lines.Add(('TOTAL'.PadRight(20) + ('{0:N2}' -f [double]$Payload.total))) }
    if ($Payload.payment_method) { $lines.Add("Pago: $($Payload.payment_method)") }
    if ($Payload.footer) { $lines.Add(''); $lines.Add([string]$Payload.footer) }
    $lines.Add('')
    $lines.Add('')
    return ($lines -join [Environment]::NewLine)
}

function Send-ToWindowsPrinter {
    param(
        [string]$PrinterName,
        [string]$Text
    )
    $printer = Get-Printer -Name $PrinterName -ErrorAction Stop
    if ($printer.PrinterStatus -eq 'Offline') {
        throw "La impresora '$PrinterName' figura offline"
    }
    $Text | Out-Printer -Name $PrinterName
}

function Invoke-FlusApi {
    param(
        [object]$Config,
        [string]$Path,
        [object]$Body
    )
    $uri = ($Config.server_base_url.TrimEnd('/') + '/' + $Path.TrimStart('/'))
    $headers = @{
        'X-FLUS-Agent-ID' = [string]$Config.agent_uid
        'Authorization'   = 'Bearer ' + [string]$Config.agent_token
    }
    $json = $Body | ConvertTo-Json -Depth 20 -Compress
    return Invoke-RestMethod -Method Post -Uri $uri -Headers $headers -ContentType 'application/json; charset=utf-8' -Body $json -TimeoutSec 30
}

if ($ListPrinters) {
    Get-InstalledPrinters | Format-Table -AutoSize
    exit 0
}

$config = Get-FlusConfig -Path $ConfigPath

if ($TestPrint) {
    $text = @"
FLUS PRINT AGENT
PRUEBA DE IMPRESION

Equipo: $env:COMPUTERNAME
Impresora: $($config.printer_name)
Fecha: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')

OK


"@
    Send-ToWindowsPrinter -PrinterName ([string]$config.printer_name) -Text $text
    Write-Host "Prueba enviada a '$($config.printer_name)'."
    exit 0
}

Write-Host "FLUS Print Agent iniciado"
Write-Host "Agente: $($config.agent_uid)"
Write-Host "Impresora: $($config.printer_name)"

while ($true) {
    try {
        $poll = Invoke-FlusApi -Config $config -Path '/print-poll.php' -Body @{}
        if ($poll.ok -and $null -ne $poll.job) {
            $job = $poll.job
            Write-Host "Trabajo recibido: $($job.job_uid)"
            try {
                $ticketText = ConvertTo-TicketText -Payload $job.payload
                Send-ToWindowsPrinter -PrinterName ([string]$config.printer_name) -Text $ticketText
                [void](Invoke-FlusApi -Config $config -Path '/print-ack.php' -Body @{
                    job_uid = [string]$job.job_uid
                    claim_token = [string]$job.claim_token
                    status = 'printed'
                    result = @{
                        printer_name = [string]$config.printer_name
                        computer_name = [string]$env:COMPUTERNAME
                        printed_at = (Get-Date).ToUniversalTime().ToString('o')
                    }
                })
                Write-Host "Impreso OK: $($job.job_uid)"
            }
            catch {
                $message = $_.Exception.Message
                Write-Warning "Error de impresion: $message"
                try {
                    [void](Invoke-FlusApi -Config $config -Path '/print-ack.php' -Body @{
                        job_uid = [string]$job.job_uid
                        claim_token = [string]$job.claim_token
                        status = 'failed'
                        result = @{
                            error = $message
                            printer_name = [string]$config.printer_name
                            computer_name = [string]$env:COMPUTERNAME
                        }
                    })
                } catch {
                    Write-Warning "No se pudo informar el fallo al servidor: $($_.Exception.Message)"
                }
            }
        }
    }
    catch {
        Write-Warning "Error consultando FLUS: $($_.Exception.Message)"
    }

    if ($Once) { break }
    Start-Sleep -Seconds ([Math]::Max(1, [int]$config.poll_seconds))
}
