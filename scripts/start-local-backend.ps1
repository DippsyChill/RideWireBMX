$ErrorActionPreference = 'Stop'
$php = Join-Path $env:LOCALAPPDATA 'RideWireTools\php-8.5.12\php.exe'
if (!(Test-Path $php)) { throw 'Portable PHP is missing. Install it before starting the local backend.' }
Set-Location (Join-Path $PSScriptRoot '..')
Write-Host 'RideWire local backend: http://127.0.0.1:8001/staff/'
& $php -S 127.0.0.1:8001 -t .
