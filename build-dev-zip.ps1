param(
    [ValidatePattern('^[^\\/:*?"<>|]+[.]zip$')]
    [string]$OutputFileName = 'wp-seed-events-dev.zip'
)

$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$dist = Join-Path $root 'dist'
$zipPath = Join-Path $dist $OutputFileName
$pluginFile = Join-Path $root 'wp-seed-events.php'
$pluginRuntimeDirs = @(
    'includes',
    'templates'
)
$moduleRuntimeRoot = 'includes/integrations/divi/event-dates-module/visual-builder'
$moduleJson = Join-Path $root "$moduleRuntimeRoot/src/module.json"
$moduleBundle = Join-Path $root "$moduleRuntimeRoot/build/wp-seed-events-event-dates.js"
$gutenbergRuntimeRoot = 'includes/integrations/gutenberg/event-dates-block'
$gutenbergBlockJson = Join-Path $root "$gutenbergRuntimeRoot/build/block.json"
$gutenbergBlockScript = Join-Path $root "$gutenbergRuntimeRoot/build/index.js"
$gutenbergBlockAsset = Join-Path $root "$gutenbergRuntimeRoot/build/index.asset.php"
$gutenbergVisualsRuntimeRoot = 'includes/integrations/gutenberg/event-visuals-block'
$gutenbergVisualsBlockJson = Join-Path $root "$gutenbergVisualsRuntimeRoot/build/block.json"
$gutenbergVisualsBlockScript = Join-Path $root "$gutenbergVisualsRuntimeRoot/build/index.js"
$gutenbergVisualsBlockAsset = Join-Path $root "$gutenbergVisualsRuntimeRoot/build/index.asset.php"
$gutenbergDocumentRuntimeRoot = 'includes/integrations/gutenberg/event-document-block'
$gutenbergDocumentBlockJson = Join-Path $root "$gutenbergDocumentRuntimeRoot/build/block.json"
$gutenbergDocumentBlockScript = Join-Path $root "$gutenbergDocumentRuntimeRoot/build/index.js"
$gutenbergDocumentBlockAsset = Join-Path $root "$gutenbergDocumentRuntimeRoot/build/index.asset.php"
$gutenbergPeopleRuntimeRoot = 'includes/integrations/gutenberg/event-people-block'
$gutenbergPeopleBlockJson = Join-Path $root "$gutenbergPeopleRuntimeRoot/build/block.json"
$gutenbergPeopleBlockScript = Join-Path $root "$gutenbergPeopleRuntimeRoot/build/index.js"
$gutenbergPeopleBlockAsset = Join-Path $root "$gutenbergPeopleRuntimeRoot/build/index.asset.php"
$gutenbergCollectionRuntimeRoot = 'includes/integrations/gutenberg/event-collection-query'
$gutenbergCollectionScript = Join-Path $root "$gutenbergCollectionRuntimeRoot/build/index.js"
$gutenbergCollectionAsset = Join-Path $root "$gutenbergCollectionRuntimeRoot/build/index.asset.php"
$gutenbergOccurrenceRuntimeRoot = 'includes/integrations/gutenberg/occurrence-collection-block'
$gutenbergOccurrenceBlockJson = Join-Path $root "$gutenbergOccurrenceRuntimeRoot/build/block.json"
$gutenbergOccurrenceScript = Join-Path $root "$gutenbergOccurrenceRuntimeRoot/build/index.js"
$gutenbergOccurrenceAsset = Join-Path $root "$gutenbergOccurrenceRuntimeRoot/build/index.asset.php"
$excludedRuntimePatterns = @(
    "$moduleRuntimeRoot/node_modules/*",
    "$moduleRuntimeRoot/package.json",
    "$moduleRuntimeRoot/package-lock.json",
    "$moduleRuntimeRoot/webpack.config.js",
    "$moduleRuntimeRoot/tests/*",
    "$moduleRuntimeRoot/src/index.jsx",
    "$moduleRuntimeRoot/src/divi-style-values.js",
    "$moduleRuntimeRoot/src/loop-preview-context.js",
    "$gutenbergRuntimeRoot/node_modules/*",
    "$gutenbergRuntimeRoot/tests/*",
    "$gutenbergRuntimeRoot/src/*",
    "$gutenbergVisualsRuntimeRoot/node_modules/*",
    "$gutenbergVisualsRuntimeRoot/tests/*",
    "$gutenbergVisualsRuntimeRoot/src/*",
    "$gutenbergDocumentRuntimeRoot/node_modules/*",
    "$gutenbergDocumentRuntimeRoot/tests/*",
    "$gutenbergDocumentRuntimeRoot/src/*",
    "$gutenbergPeopleRuntimeRoot/node_modules/*",
    "$gutenbergPeopleRuntimeRoot/tests/*",
    "$gutenbergPeopleRuntimeRoot/src/*",
    "$gutenbergCollectionRuntimeRoot/node_modules/*",
    "$gutenbergCollectionRuntimeRoot/tests/*",
    "$gutenbergCollectionRuntimeRoot/src/*",
    "$gutenbergOccurrenceRuntimeRoot/node_modules/*",
    "$gutenbergOccurrenceRuntimeRoot/tests/*",
    "$gutenbergOccurrenceRuntimeRoot/src/*"
)

$visualsModuleRuntimeRoot = 'includes/integrations/divi/event-visuals-module/visual-builder'
$visualsModuleJson = Join-Path $root ($visualsModuleRuntimeRoot + '/src/module.json')
$visualsModuleBundle = Join-Path $root ($visualsModuleRuntimeRoot + '/build/wp-seed-events-event-visuals.js')
$excludedRuntimePatterns += @(
    ($visualsModuleRuntimeRoot + '/node_modules/*'),
    ($visualsModuleRuntimeRoot + '/package.json'),
    ($visualsModuleRuntimeRoot + '/package-lock.json'),
    ($visualsModuleRuntimeRoot + '/webpack.config.js'),
    ($visualsModuleRuntimeRoot + '/tests/*'),
    ($visualsModuleRuntimeRoot + '/src/index.jsx')
)

foreach ($visualsModuleAsset in @($visualsModuleJson, $visualsModuleBundle)) {
    if (-not (Test-Path -LiteralPath $visualsModuleAsset -PathType Leaf)) {
        throw ('Missing Divi visuals module asset. Run npm run build first: ' + $visualsModuleAsset)
    }
}

$documentModuleRuntimeRoot = 'includes/integrations/divi/event-document-module/visual-builder'
$documentModuleJson = Join-Path $root ($documentModuleRuntimeRoot + '/src/module.json')
$documentModuleBundle = Join-Path $root ($documentModuleRuntimeRoot + '/build/wp-seed-events-event-document.js')
$excludedRuntimePatterns += @(
    ($documentModuleRuntimeRoot + '/node_modules/*'),
    ($documentModuleRuntimeRoot + '/webpack.config.js'),
    ($documentModuleRuntimeRoot + '/tests/*'),
    ($documentModuleRuntimeRoot + '/src/index.jsx')
)
foreach ($documentModuleAsset in @($documentModuleJson, $documentModuleBundle)) {
    if (-not (Test-Path -LiteralPath $documentModuleAsset -PathType Leaf)) {
        throw ('Missing Divi document module asset. Run npm run build first: ' + $documentModuleAsset)
    }
}

$peopleModuleRuntimeRoot = 'includes/integrations/divi/event-people-module/visual-builder'
$peopleModuleJson = Join-Path $root ($peopleModuleRuntimeRoot + '/src/module.json')
$peopleModuleBundle = Join-Path $root ($peopleModuleRuntimeRoot + '/build/wp-seed-events-event-people.js')
$excludedRuntimePatterns += @(
    ($peopleModuleRuntimeRoot + '/node_modules/*'),
    ($peopleModuleRuntimeRoot + '/package.json'),
    ($peopleModuleRuntimeRoot + '/package-lock.json'),
    ($peopleModuleRuntimeRoot + '/webpack.config.js'),
    ($peopleModuleRuntimeRoot + '/tests/*'),
    ($peopleModuleRuntimeRoot + '/src/index.jsx')
)

foreach ($peopleModuleAsset in @($peopleModuleJson, $peopleModuleBundle)) {
    if (-not (Test-Path -LiteralPath $peopleModuleAsset -PathType Leaf)) {
        throw ('Missing Divi people module asset. Run npm run build first: ' + $peopleModuleAsset)
    }
}
$shareModuleRuntimeRoot = 'includes/integrations/divi/event-share-module/visual-builder'
$shareModuleJson = Join-Path $root ($shareModuleRuntimeRoot + '/src/module.json')
$shareModuleBundle = Join-Path $root ($shareModuleRuntimeRoot + '/build/wp-seed-events-event-share.js')
$excludedRuntimePatterns += @(
    ($shareModuleRuntimeRoot + '/node_modules/*'),
    ($shareModuleRuntimeRoot + '/package.json'),
    ($shareModuleRuntimeRoot + '/package-lock.json'),
    ($shareModuleRuntimeRoot + '/webpack.config.js'),
    ($shareModuleRuntimeRoot + '/tests/*'),
    ($shareModuleRuntimeRoot + '/src/index.jsx')
)

foreach ($shareModuleAsset in @($shareModuleJson, $shareModuleBundle)) {
    if (-not (Test-Path -LiteralPath $shareModuleAsset -PathType Leaf)) {
        throw ('Missing Divi share module asset. Run npm run build first: ' + $shareModuleAsset)
    }
}
$occurrenceModuleRuntimeRoot = 'includes/integrations/divi/occurrence-collection-module/visual-builder'
$occurrenceModuleJson = Join-Path $root ($occurrenceModuleRuntimeRoot + '/src/module.json')
$occurrenceModuleBundle = Join-Path $root ($occurrenceModuleRuntimeRoot + '/build/wp-seed-events-occurrence-collection.js')
$excludedRuntimePatterns += @(
    ($occurrenceModuleRuntimeRoot + '/node_modules/*'),
    ($occurrenceModuleRuntimeRoot + '/package.json'),
    ($occurrenceModuleRuntimeRoot + '/package-lock.json'),
    ($occurrenceModuleRuntimeRoot + '/webpack.config.js'),
    ($occurrenceModuleRuntimeRoot + '/tests/*'),
    ($occurrenceModuleRuntimeRoot + '/src/index.jsx')
)

foreach ($occurrenceModuleAsset in @($occurrenceModuleJson, $occurrenceModuleBundle)) {
    if (-not (Test-Path -LiteralPath $occurrenceModuleAsset -PathType Leaf)) {
        throw ('Missing Divi occurrence collection module asset. Run npm run build first: ' + $occurrenceModuleAsset)
    }
}

$pluginHeader = Get-Content -Path $pluginFile -Raw -Encoding UTF8
$versionMatch = [regex]::Match($pluginHeader, '^\s*\*\s*Version:\s*(?<version>[^\r\n]+)', [System.Text.RegularExpressions.RegexOptions]::Multiline)

if (-not $versionMatch.Success -or [string]::IsNullOrWhiteSpace($versionMatch.Groups['version'].Value)) {
    throw 'Unable to find a valid Version header in wp-seed-events.php.'
}

if (-not (Test-Path -LiteralPath $moduleJson -PathType Leaf)) {
    throw "Missing Divi module metadata: $moduleJson"
}

if (-not (Test-Path -LiteralPath $moduleBundle -PathType Leaf)) {
    throw "Missing Divi module bundle. Run npm run build first: $moduleBundle"
}

foreach ($gutenbergAsset in @($gutenbergBlockJson, $gutenbergBlockScript, $gutenbergBlockAsset)) {
    if (-not (Test-Path -LiteralPath $gutenbergAsset -PathType Leaf)) {
        throw "Missing Gutenberg block asset. Run npm run build:gutenberg first: $gutenbergAsset"
    }
}

foreach ($gutenbergVisualsAsset in @($gutenbergVisualsBlockJson, $gutenbergVisualsBlockScript, $gutenbergVisualsBlockAsset)) {
    if (-not (Test-Path -LiteralPath $gutenbergVisualsAsset -PathType Leaf)) {
        throw "Missing Gutenberg visuals block asset. Run npm run build:gutenberg first: $gutenbergVisualsAsset"
    }
}

foreach ($gutenbergDocumentAsset in @($gutenbergDocumentBlockJson, $gutenbergDocumentBlockScript, $gutenbergDocumentBlockAsset)) {
    if (-not (Test-Path -LiteralPath $gutenbergDocumentAsset -PathType Leaf)) {
        throw "Missing Gutenberg document block asset. Run npm run build:gutenberg first: $gutenbergDocumentAsset"
    }
}

foreach ($gutenbergPeopleAsset in @($gutenbergPeopleBlockJson, $gutenbergPeopleBlockScript, $gutenbergPeopleBlockAsset)) {
    if (-not (Test-Path -LiteralPath $gutenbergPeopleAsset -PathType Leaf)) {
        throw "Missing Gutenberg people block asset. Run npm run build:gutenberg first: $gutenbergPeopleAsset"
    }
}


foreach ($gutenbergCollectionBuildAsset in @($gutenbergCollectionScript, $gutenbergCollectionAsset)) {
    if (-not (Test-Path -LiteralPath $gutenbergCollectionBuildAsset -PathType Leaf)) {
        throw "Missing Gutenberg event collection asset. Run npm run build:gutenberg first: $gutenbergCollectionBuildAsset"
    }
}

foreach ($gutenbergOccurrenceBuildAsset in @($gutenbergOccurrenceBlockJson, $gutenbergOccurrenceScript, $gutenbergOccurrenceAsset)) {
    if (-not (Test-Path -LiteralPath $gutenbergOccurrenceBuildAsset -PathType Leaf)) {
        throw "Missing Gutenberg occurrence collection asset. Run npm run build:gutenberg first: $gutenbergOccurrenceBuildAsset"
    }
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
            $isExcluded = $false

            if (
                $relativePath -like '.git/*' -or
                $relativePath -like 'dist/*' -or
                $_.Name -like '.env*' -or
                $_.Name -match '[.](next|patch|tmp|bak)$' -or
                $_.Name -like '*.log'
            ) {
                $isExcluded = $true
            }

            foreach ($pattern in $excludedRuntimePatterns) {
                if ($relativePath -like $pattern) {
                    $isExcluded = $true
                    break
                }
            }

            if ($isExcluded) {
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
