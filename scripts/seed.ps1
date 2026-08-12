# Run PHP scripts using XAMPP when `php` is not on PATH.
# Usage: .\scripts\seed.ps1

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

$php = @(
    'C:\xampp\php\php.exe',
    'C:\laragon\bin\php\php-8.3.12-Win32-vs16-x64\php.exe',
    'C:\laragon\bin\php\php-8.2.12-Win32-vs16-x64\php.exe'
) | Where-Object { Test-Path $_ } | Select-Object -First 1

if (-not $php) {
    $php = (Get-Command php -ErrorAction SilentlyContinue).Source
}

if (-not $php) {
    Write-Host 'PHP not found. Install XAMPP or add php.exe to PATH.' -ForegroundColor Red
    Write-Host 'Alternative: import sql/seed-import.sql in HostMaria phpMyAdmin (see README).' -ForegroundColor Yellow
    exit 1
}

Write-Host "Using: $php"

if (-not (Test-Path 'sql\seed-data.json')) {
    Write-Host 'Generating seed-data.json...'
    node scripts/export-packages-json.mjs
}

& $php scripts/seed-from-js.php
