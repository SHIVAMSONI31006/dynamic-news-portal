param(
    [switch]$NoBrowser
)

$ErrorActionPreference = "Stop"
$ProjectRoot = $PSScriptRoot
if (-not $ProjectRoot) {
    $ProjectRoot = (Get-Location).Path
}

Write-Host ""
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host " SONI_PHP_WEBSITE - DUAL LOCALHOST SERVERS INITIALIZER" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan

# 1. Verify PHP is installed
Write-Host "[1/6] Verifying PHP runtime..." -ForegroundColor Yellow
try {
    $phpCmd = Get-Command php -ErrorAction Stop
    $phpExe = $phpCmd.Source
    $phpVersionOutput = & $phpExe -v 2>&1
    if ($LASTEXITCODE -ne 0 -or -not $phpVersionOutput) {
        throw "PHP returned a non-zero exit code."
    }
    $phpVersionFirstLine = ($phpVersionOutput | Select-Object -First 1).Trim()
    Write-Host "      Found: $phpVersionFirstLine at $phpExe" -ForegroundColor Green
} catch {
    Write-Host "ERROR: PHP is not installed or not in PATH." -ForegroundColor Red
    Write-Host "Please ensure PHP 8+ is installed and accessible via terminal." -ForegroundColor Red
    exit 1
}

# 2. Verify project directories and entry points
Write-Host "[2/6] Verifying project files..." -ForegroundColor Yellow
$routerPublic = Join-Path $ProjectRoot "router_public.php"
$routerAdmin  = Join-Path $ProjectRoot "router_admin.php"
$logsDir      = Join-Path $ProjectRoot "logs"

if (-not (Test-Path $routerPublic)) {
    Write-Host "ERROR: Missing router_public.php in $ProjectRoot" -ForegroundColor Red
    exit 1
}
if (-not (Test-Path $routerAdmin)) {
    Write-Host "ERROR: Missing router_admin.php in $ProjectRoot" -ForegroundColor Red
    exit 1
}
if (-not (Test-Path $logsDir)) {
    New-Item -ItemType Directory -Path $logsDir -Force | Out-Null
}
Write-Host "      Project root and routers verified." -ForegroundColor Green

# 3. Check for existing servers / PID file
$pidFile = Join-Path $ProjectRoot ".dual_servers.json"
if (Test-Path $pidFile) {
    try {
        $existing = Get-Content $pidFile -Raw | ConvertFrom-Json
        $p8000Running = $false
        $p9000Running = $false

        if ($existing.public -and $existing.public.pid) {
            $proc = Get-Process -Id $existing.public.pid -ErrorAction SilentlyContinue
            if ($proc -and $proc.ProcessName -match "php") { $p8000Running = $true }
        }
        if ($existing.admin -and $existing.admin.pid) {
            $proc = Get-Process -Id $existing.admin.pid -ErrorAction SilentlyContinue
            if ($proc -and $proc.ProcessName -match "php") { $p9000Running = $true }
        }

        if ($p8000Running -and $p9000Running) {
            Write-Host "      Both servers are ALREADY running!" -ForegroundColor Yellow
            Write-Host "      Public: http://localhost:8000 (PID $($existing.public.pid))" -ForegroundColor Cyan
            Write-Host "      Admin : http://localhost:9000 (PID $($existing.admin.pid))" -ForegroundColor Cyan
            if (-not $NoBrowser) {
                Start-Process "http://localhost:8000"
                Start-Process "http://localhost:9000"
            }
            exit 0
        }
    } catch {
        # Corrupt PID file, ignore
    }
}

# 4. Check whether ports 8000 and 9000 are available
Write-Host "[3/6] Checking port availability for 8000 and 9000..." -ForegroundColor Yellow

function Test-PortOccupied([int]$chkPort) {
    try {
        $conn = Get-NetTCPConnection -LocalPort $chkPort -State Listen -ErrorAction SilentlyContinue
        if ($conn) {
            $ownerProc = Get-Process -Id $conn.OwningProcess[0] -ErrorAction SilentlyContinue
            return @{
                Occupied = $true
                Pid      = $conn.OwningProcess[0]
                Name     = if ($ownerProc) { $ownerProc.ProcessName } else { "Unknown" }
            }
        }
    } catch {}
    return @{ Occupied = $false }
}

$port8000 = Test-PortOccupied 8000
if ($port8000.Occupied) {
    Write-Host "ERROR: Port 8000 is occupied by process '$($port8000.Name)' (PID $($port8000.Pid))." -ForegroundColor Red
    Write-Host "Please stop the existing process or run .\stop-dual-servers.ps1 before launching." -ForegroundColor Red
    exit 1
}

$port9000 = Test-PortOccupied 9000
if ($port9000.Occupied) {
    Write-Host "ERROR: Port 9000 is occupied by process '$($port9000.Name)' (PID $($port9000.Pid))." -ForegroundColor Red
    Write-Host "Please stop the existing process or run .\stop-dual-servers.ps1 before launching." -ForegroundColor Red
    exit 1
}
Write-Host "      Ports 8000 and 9000 are free and ready." -ForegroundColor Green

# 5. Start servers independently via detached Win32 process creation
Write-Host "[4/6] Starting dual PHP servers..." -ForegroundColor Yellow
$logPublic = Join-Path $logsDir "server_public.log"
$logAdmin  = Join-Path $logsDir "server_admin.log"

Set-Content -Path $logPublic -Value ""
Set-Content -Path $logAdmin  -Value ""

$cmdPublic = "cmd.exe /c `"`"$phpExe`" -S 127.0.0.1:8000 -t `"$ProjectRoot`" `"$routerPublic`" > `"$logPublic`" 2>&1`""
$cmdAdmin  = "cmd.exe /c `"`"$phpExe`" -S 127.0.0.1:9000 -t `"$ProjectRoot`" `"$routerAdmin`" > `"$logAdmin`" 2>&1`""

$resPublic = Invoke-CimMethod -ClassName Win32_Process -MethodName Create -Arguments @{ CommandLine = $cmdPublic; CurrentDirectory = $ProjectRoot }
$resAdmin  = Invoke-CimMethod -ClassName Win32_Process -MethodName Create -Arguments @{ CommandLine = $cmdAdmin;  CurrentDirectory = $ProjectRoot }

# 6. Verify server health and socket binding
Write-Host "[5/6] Verifying server health and connectivity..." -ForegroundColor Yellow
Start-Sleep -Milliseconds 1800

$conn8000 = Get-NetTCPConnection -LocalPort 8000 -State Listen -ErrorAction SilentlyContinue
$conn9000 = Get-NetTCPConnection -LocalPort 9000 -State Listen -ErrorAction SilentlyContinue

$publicHealthy = ($null -ne $conn8000)
$adminHealthy  = ($null -ne $conn9000)

if (-not $publicHealthy -or -not $adminHealthy) {
    Write-Host ""
    Write-Host "FATAL: One or more servers failed to bind or start!" -ForegroundColor Red
    if (-not $publicHealthy) {
        Write-Host "Server 1 (Port 8000) FAILED. Log excerpt:" -ForegroundColor Red
        if (Test-Path $logPublic) { Get-Content $logPublic -Tail 10 | Write-Host }
    }
    if (-not $adminHealthy) {
        Write-Host "Server 2 (Port 9000) FAILED. Log excerpt:" -ForegroundColor Red
        if (Test-Path $logAdmin) { Get-Content $logAdmin -Tail 10 | Write-Host }
    }

    # Clean up
    if ($conn8000) { Stop-Process -Id $conn8000.OwningProcess[0] -Force -ErrorAction SilentlyContinue }
    if ($conn9000) { Stop-Process -Id $conn9000.OwningProcess[0] -Force -ErrorAction SilentlyContinue }
    exit 1
}

$phpPid8000 = $conn8000.OwningProcess[0]
$phpPid9000 = $conn9000.OwningProcess[0]

$pidData = @{
    public = @{
        pid          = $phpPid8000
        launcher_pid = $resPublic.ProcessId
        port         = 8000
        url          = "http://localhost:8000"
        role         = "Public News Portal"
        log          = $logPublic
    }
    admin = @{
        pid          = $phpPid9000
        launcher_pid = $resAdmin.ProcessId
        port         = 9000
        url          = "http://localhost:9000"
        role         = "Admin Panel CMS"
        log          = $logAdmin
    }
    started_at = (Get-Date).ToString("yyyy-MM-dd HH:mm:ss")
}

$pidData | ConvertTo-Json -Depth 3 | Set-Content -Path $pidFile -Encoding UTF8

Write-Host "      Both servers are actively listening on their assigned ports." -ForegroundColor Green
Write-Host ""
Write-Host "============================================================" -ForegroundColor Green
Write-Host " DUAL LOCALHOST SERVERS ARE ONLINE AND READY!" -ForegroundColor Green
Write-Host "============================================================" -ForegroundColor Green
Write-Host ""
Write-Host " WEBSITE 1 - PUBLIC NEWS PORTAL" -ForegroundColor Cyan
Write-Host "   URL:         http://localhost:8000" -ForegroundColor White
Write-Host "   Entry:       http://localhost:8000/index.php" -ForegroundColor Gray
Write-Host "   PID:         $phpPid8000" -ForegroundColor Gray
Write-Host "   Session:     NEWS_PUBLIC_SESSION" -ForegroundColor Gray
Write-Host ""
Write-Host " WEBSITE 2 - ADMIN CMS & NEWS MANAGEMENT" -ForegroundColor Magenta
Write-Host "   URL:         http://localhost:9000" -ForegroundColor White
Write-Host "   Entry:       http://localhost:9000/admin/index.php (Login: /admin/login.php)" -ForegroundColor Gray
Write-Host "   PID:         $phpPid9000" -ForegroundColor Gray
Write-Host "   Session:     NEWS_ADMIN_SESSION" -ForegroundColor Gray
Write-Host ""
Write-Host " SHARED DATABASE:" -ForegroundColor Yellow
Write-Host "   Database:    news_portal (MySQL localhost:3306)" -ForegroundColor Gray
Write-Host ""
Write-Host " MANAGEMENT COMMANDS:" -ForegroundColor DarkCyan
Write-Host "   To Stop:     .\stop-dual-servers.ps1 (or stop-dual-servers.bat)" -ForegroundColor Gray
Write-Host "   Logs:        .\logs\server_public.log and .\logs\server_admin.log" -ForegroundColor Gray
Write-Host "============================================================" -ForegroundColor Green
Write-Host ""

# 7. Auto-open default browser
if (-not $NoBrowser) {
    Write-Host "[6/6] Launching default browser..." -ForegroundColor Yellow
    try {
        Start-Process "http://localhost:8000"
        Start-Sleep -Milliseconds 600
        Start-Process "http://localhost:9000"
        Write-Host "      Browser tabs opened successfully." -ForegroundColor Green
    } catch {
        Write-Host "      Could not auto-open browser: $($_.Exception.Message)" -ForegroundColor Yellow
    }
} else {
    Write-Host "[6/6] Browser auto-open skipped (-NoBrowser specified)." -ForegroundColor DarkGray
}

Write-Host ""
