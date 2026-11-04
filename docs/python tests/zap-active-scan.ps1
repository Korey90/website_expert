param(
    [string]$TargetUrl = "https://tellsell.pl/",
    [int]$Minutes = 10,
    [switch]$ConfirmLiveTest
)

if (-not $ConfirmLiveTest) {
    Write-Error "To jest aktywny skan produkcji. Uruchom ponownie z -ConfirmLiveTest po sprawdzeniu okna testowego."
    exit 2
}

if ($Minutes -lt 1 -or $Minutes -gt 30) {
    Write-Error "Minutes musi mieścić się w zakresie 1-30."
    exit 2
}

$docker = Get-Command docker -ErrorAction SilentlyContinue
if (-not $docker) {
    Write-Error "Nie znaleziono docker.exe. Uruchom Docker Desktop i otwórz nowy terminal."
    exit 2
}

$reportPath = Join-Path (Get-Location) "zap-active-report.html"

Write-Warning "Aktywny skan może generować wiele żądań i wywołać rate limiting/WAF."
Write-Warning "Nie testuj endpointów formularzy ani zewnętrznego FormSubmit tym skryptem."

docker run --rm -t `
    -v "${PWD}:/zap/wrk/:rw" `
    ghcr.io/zaproxy/zaproxy:stable `
    zap-full-scan.py `
    -t $TargetUrl `
    -m $Minutes `
    -j `
    -r /zap/wrk/zap-active-report.html `
    -z "-config scanner.attackStrength=HIGH -config scanner.alertThreshold=LOW"

if ($LASTEXITCODE -ne 0) {
    Write-Warning "ZAP zakonczyl skan z kodem $LASTEXITCODE. Raport moze nadal zawierac wyniki."
}

if (Test-Path $reportPath) {
    Write-Output "Raport: $reportPath"
}