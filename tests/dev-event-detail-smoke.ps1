param(
    [Parameter(Mandatory = $true)]
    [ValidatePattern('^https://dev[.]psychotherapiedeletre[.]com/')]
    [string] $Url
)

$ErrorActionPreference = 'Stop'

foreach ($name in @('WPSEED_DEV_HTTP_USER', 'WPSEED_DEV_HTTP_PASSWORD')) {
    if ([string]::IsNullOrWhiteSpace([Environment]::GetEnvironmentVariable($name))) {
        throw "Missing required environment variable: $name"
    }
}

$user = [Environment]::GetEnvironmentVariable('WPSEED_DEV_HTTP_USER')
$password = [Environment]::GetEnvironmentVariable('WPSEED_DEV_HTTP_PASSWORD')
$credential = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes("${user}:${password}"))

Add-Type -AssemblyName System.Net.Http
$handler = [Net.Http.HttpClientHandler]::new()
$handler.AllowAutoRedirect = $true
$client = [Net.Http.HttpClient]::new($handler)
$request = [Net.Http.HttpRequestMessage]::new([Net.Http.HttpMethod]::Get, $Url)
$request.Headers.Authorization = [Net.Http.Headers.AuthenticationHeaderValue]::new('Basic', $credential)

try {
    $response = $client.SendAsync($request).GetAwaiter().GetResult()
    $content = $response.Content.ReadAsStringAsync().GetAwaiter().GetResult()
}
finally {
    $request.Dispose()
    if ($null -ne $response) {
        $response.Dispose()
    }
    $client.Dispose()
    $handler.Dispose()
}

if (200 -ne [int] $response.StatusCode) {
    throw "Event detail returned HTTP $([int] $response.StatusCode)."
}
if ([string]::IsNullOrWhiteSpace($content)) {
    throw 'Event detail returned empty HTML.'
}
if ($content -match 'Il y a eu une erreur critique|There has been a critical error|PHP Fatal|Uncaught') {
    throw 'Event detail contains a critical error marker.'
}

$requiredMarkers = @(
    'wp-seed-event-section--dates',
    'wp-seed-event-people',
    'wp-seed-event-visuals'
)
foreach ($marker in $requiredMarkers) {
    if ($content -notmatch [regex]::Escape($marker)) {
        throw "Event detail is missing module marker: $marker"
    }
}

Write-Output 'EVENT_DETAIL_HTTP=PASS'
Write-Output "STATUS=$([int] $response.StatusCode)"
Write-Output "HTML_LENGTH=$([Text.Encoding]::UTF8.GetByteCount($content))"
