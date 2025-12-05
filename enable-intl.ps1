# PowerShell script to enable intl extension in Laragon's Apache PHP
Write-Host "Enabling intl extension for Laragon Apache PHP..." -ForegroundColor Cyan

# Get Laragon PHP path (default installation)
$laragonPath = "C:\laragon"
if (-not (Test-Path $laragonPath)) {
    Write-Host "Error: Laragon not found at $laragonPath" -ForegroundColor Red
    exit 1
}

# Find PHP version directory
$phpPath = Get-ChildItem "$laragonPath\bin\php" -Directory | Sort-Object Name -Descending | Select-Object -First 1
if (-not $phpPath) {
    Write-Host "Error: PHP directory not found in Laragon" -ForegroundColor Red
    exit 1
}

$phpIniPath = Join-Path $phpPath.FullName "php.ini"
Write-Host "PHP ini path: $phpIniPath" -ForegroundColor Yellow

if (-not (Test-Path $phpIniPath)) {
    Write-Host "Error: php.ini not found at $phpIniPath" -ForegroundColor Red
    exit 1
}

# Read php.ini content
$content = Get-Content $phpIniPath -Raw

# Check if intl is already enabled
if ($content -match "^extension=intl") {
    Write-Host "intl extension is already enabled!" -ForegroundColor Green
} elseif ($content -match ";extension=intl") {
    Write-Host "Uncommenting intl extension..." -ForegroundColor Yellow

    # Create backup
    $backupPath = "$phpIniPath.backup"
    Copy-Item $phpIniPath $backupPath -Force
    Write-Host "Backup created at: $backupPath" -ForegroundColor Gray

    # Uncomment the extension
    $content = $content -replace "(?m)^;extension=intl", "extension=intl"
    Set-Content $phpIniPath $content -NoNewline

    Write-Host "intl extension enabled successfully!" -ForegroundColor Green
} else {
    Write-Host "Warning: intl extension line not found in php.ini" -ForegroundColor Yellow
    Write-Host "Adding extension=intl to php.ini..." -ForegroundColor Yellow

    # Add extension line after other extension entries
    if ($content -match "(?m)^extension=") {
        $content = $content -replace "((?m)^extension=.*\r?\n)", "`$1extension=intl`r`n"
    } else {
        $content += "`r`nextension=intl`r`n"
    }

    Set-Content $phpIniPath $content -NoNewline
    Write-Host "intl extension added to php.ini!" -ForegroundColor Green
}

# Restart Laragon Apache service
Write-Host "`nRestarting Apache..." -ForegroundColor Cyan

# Try using laragon_apache service name (common in Laragon)
$serviceName = "laragon_apache"
$service = Get-Service -Name $serviceName -ErrorAction SilentlyContinue

if ($service) {
    try {
        Restart-Service $serviceName -Force
        Write-Host "Apache service restarted successfully!" -ForegroundColor Green
    } catch {
        Write-Host "Failed to restart Apache service automatically." -ForegroundColor Yellow
        Write-Host "Please restart Laragon manually from the system tray." -ForegroundColor Yellow
    }
} else {
    Write-Host "Apache service not found as Windows service." -ForegroundColor Yellow
    Write-Host "Please restart Laragon manually:" -ForegroundColor Yellow
    Write-Host "1. Right-click Laragon icon in system tray" -ForegroundColor White
    Write-Host "2. Click 'Stop All'" -ForegroundColor White
    Write-Host "3. Click 'Start All'" -ForegroundColor White
}

Write-Host "`nDone! Test by accessing your Filament admin panel." -ForegroundColor Cyan
