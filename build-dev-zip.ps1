$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$dist = Join-Path $root 'dist'
$zipPath = Join-Path $dist 'wp-seed-events-dev.zip'
$pluginFile = Join-Path $root 'wp-seed-events.php'
$pluginRuntimeDirs = @(
    'includes',
    'templates'
)

$pluginHeader = Get-Content -Path $pluginFile -Raw -Encoding UTF8
$versionMatch = [regex]::Match($pluginHeader, '^\s*\*\s*Version:\s*(?<version>[^\r\n]+)', [System.Text.RegularExpressions.RegexOptions]::Multiline)

if (-not $versionMatch.Success -or [string]::IsNullOrWhiteSpace($versionMatch.Groups['version'].Value)) {
    throw 'Unable to find a valid Version header in wp-seed-events.php.'
}

$pluginVersion = $versionMatch.Groups['version'].Value.Trim()

Write-Output 'Building WP Seed Events dev ZIP'
Write-Output "Version: $pluginVersion"

New-Item -ItemType Directory -Force -Path $dist | Out-Null

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$fileStream = [System.IO.File]::Open($zipPath, [System.IO.FileMode]::Create, [System.IO.FileAccess]::ReadWrite)
$zip = [System.IO.Compression.ZipArchive]::new($fileStream, [System.IO.Compression.ZipArchiveMode]::Create)

try {
    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $pluginFile, 'wp-seed-events/wp-seed-events.php') | Out-Null

    foreach ($runtimeDir in $pluginRuntimeDirs) {
        $runtimePath = Join-Path $root $runtimeDir

        if (-not (Test-Path $runtimePath)) {
            continue
        }

        Get-ChildItem -Path $runtimePath -File -Recurse | ForEach-Object {
            $relativePath = $_.FullName.Substring($root.Length + 1).Replace('\', '/')

            if ($relativePath -like '.git/*' -or $relativePath -like 'dist/*' -or $_.Name -like '.env*') {
                return
            }

            [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $_.FullName, "wp-seed-events/$relativePath") | Out-Null
        }
    }
}
finally {
    $zip.Dispose()
    $fileStream.Dispose()
}

Write-Output $zipPath
