$ErrorActionPreference = 'Stop'

$projectPath = Split-Path -Parent $PSScriptRoot
$phpPath = 'C:\xampp\php\php.exe'
$publicPath = Join-Path $projectPath 'public'
$routerPath = Join-Path $projectPath 'vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php'
$logDirectory = 'D:\DIZANY_DATA\logs'
$logPath = Join-Path $logDirectory 'dizany-server.log'

if (-not (Test-Path -LiteralPath $phpPath -PathType Leaf)) {
    throw "No se encontró PHP en $phpPath"
}

if (-not (Test-Path -LiteralPath $logDirectory -PathType Container)) {
    New-Item -ItemType Directory -Path $logDirectory -Force | Out-Null
}

Set-Location -LiteralPath $publicPath

while ($true) {
    $listener = Get-NetTCPConnection -LocalPort 8000 -State Listen -ErrorAction SilentlyContinue

    if ($listener) {
        Start-Sleep -Seconds 15
        continue
    }

    "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] Iniciando DIZANY en 0.0.0.0:8000" |
        Out-File -LiteralPath $logPath -Append -Encoding utf8

    try {
        # Ejecutar directamente el servidor de PHP. `artisan serve` reinicia
        # continuamente cuando PHP imprime advertencias de extensiones al arrancar.
        # PHP escribe esas advertencias en stderr; no deben detener el supervisor.
        $previousErrorActionPreference = $ErrorActionPreference
        $ErrorActionPreference = 'Continue'
        & $phpPath -S 0.0.0.0:8000 -t $publicPath $routerPath *>> $logPath
        $ErrorActionPreference = $previousErrorActionPreference
    } catch {
        $ErrorActionPreference = 'Stop'
        "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] $($_.Exception.Message)" |
            Out-File -LiteralPath $logPath -Append -Encoding utf8
    }

    Start-Sleep -Seconds 5
}
