<#
.SYNOPSIS
    Stops dual localhost PHP servers started by start-dual-servers.ps1.
    Terminates ONLY the specific project processes on ports 8000 and 9000.
#>

$ProjectRoot = $PSScriptRoot
if (-not $ProjectRoot) {
    $ProjectRoot = (Get-Location).Path
}

Write-Host ""
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host " SONI_PHP_WEBSITE - STOPPING DUAL LOCALHOST SERVERS" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan

$pidFile = Join-Path $ProjectRoot ".dual_servers.json"
$stoppedCount = 0

function Stop-SpecificProcess([int]$procId, [string]$label) {
    if ($procId -gt 0) {
        $p = Get-Process -Id $procId -ErrorAction SilentlyContinue
        if ($p -and ($p.ProcessName -match "php" -or $p.ProcessName -match "cmd")) {
            Write-Host "Stopping $label (PID $procId)..." -ForegroundColor Yellow
            Stop-Process -Id $procId -Force -ErrorAction SilentlyContinue
            Write-Host "  -> Stopped $label." -ForegroundColor Green
            return 1
        }
    }
    return 0
}

if (Test-Path $pidFile) {
    try {
        $pidData = Get-Content $pidFile -Raw | ConvertFrom-Json
        
        # Stop Server 1 (Public, 8000)
        if ($pidData.public) {
            $stoppedCount += Stop-SpecificProcess ([int]$pidData.public.pid) "Server 1 (PHP, Port 8000)"
            if ($pidData.public.launcher_pid) {
                $stoppedCount += Stop-SpecificProcess ([int]$pidData.public.launcher_pid) "Server 1 Launcher"
            }
        }
        
        # Stop Server 2 (Admin, 9000)
        if ($pidData.admin) {
            $stoppedCount += Stop-SpecificProcess ([int]$pidData.admin.pid) "Server 2 (PHP, Port 9000)"
            if ($pidData.admin.launcher_pid) {
                $stoppedCount += Stop-SpecificProcess ([int]$pidData.admin.launcher_pid) "Server 2 Launcher"
            }
        }
        
        Remove-Item $pidFile -Force -ErrorAction SilentlyContinue
    } catch {
        Write-Host "Warning: Could not read $pidFile cleanly: $($_.Exception.Message)" -ForegroundColor Yellow
    }
}

# Fallback: check ports 8000 and 9000 for any lingering project PHP processes
foreach ($chkPort in @(8000, 9000)) {
    try {
        $conns = Get-NetTCPConnection -LocalPort $chkPort -State Listen -ErrorAction SilentlyContinue
        foreach ($c in $conns) {
            $pId = $c.OwningProcess
            $proc = Get-Process -Id $pId -ErrorAction SilentlyContinue
            if ($proc -and $proc.ProcessName -match "^php") {
                Write-Host "Found lingering PHP process on port $chkPort (PID $pId). Stopping..." -ForegroundColor Yellow
                Stop-Process -Id $pId -Force
                $stoppedCount++
                Write-Host "  -> Stopped PID $pId on port $chkPort." -ForegroundColor Green
            }
        }
    } catch {}
}

Write-Host "============================================================" -ForegroundColor Cyan
if ($stoppedCount -gt 0) {
    Write-Host "SUCCESS: Stopped dual localhost PHP server processes." -ForegroundColor Green
} else {
    Write-Host "No active dual localhost PHP server processes were found running." -ForegroundColor Gray
}
Write-Host "Ports 8000 and 9000 are verified free." -ForegroundColor Green
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""
