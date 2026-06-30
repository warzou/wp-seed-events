$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$dist = Join-Path $root 'dist'
$zipPath = Join-Path $dist 'wp-seed-events-dev.zip'
$pluginFile = Join-Path $root 'wp-seed-events.php'

New-Item -ItemType Directory -Force -Path $dist | Out-Null

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$fileStream = [System.IO.File]::Open($zipPath, [System.IO.FileMode]::Create, [System.IO.FileAccess]::ReadWrite)
$zip = [System.IO.Compression.ZipArchive]::new($fileStream, [System.IO.Compression.ZipArchiveMode]::Create)

try {
    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $pluginFile, 'wp-seed-events/wp-seed-events.php') | Out-Null
}
finally {
    $zip.Dispose()
    $fileStream.Dispose()
}

Write-Output $zipPath