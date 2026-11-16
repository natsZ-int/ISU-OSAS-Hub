# render_uml.ps1 - render every Mermaid source to SVG and PNG.
# Requires Chrome for Puppeteer (no bundled Chromium in this environment).
$ErrorActionPreference = "Continue"
$env:PUPPETEER_EXECUTABLE_PATH = "C:\Program Files\Google\Chrome\Application\chrome.exe"

$root = Split-Path -Parent $PSScriptRoot          # documentation/
$src  = Join-Path $root "UML\source"
$out  = Join-Path $root "UML\export"
New-Item -ItemType Directory -Force -Path $out | Out-Null

$files = Get-ChildItem (Join-Path $src "*.mmd") | Sort-Object Name
"Rendering $($files.Count) diagrams"
foreach ($f in $files) {
    $base = $f.BaseName
    $svg = Join-Path $out "$base.svg"
    $png = Join-Path $out "$base.png"

    $r1 = npx -y @mermaid-js/mermaid-cli -i $f.FullName -o $svg -b white 2>&1
    $okSvg = (Test-Path $svg) -and ((Get-Item $svg).Length -gt 5000)

    $r2 = npx -y @mermaid-js/mermaid-cli -i $f.FullName -o $png -s 2 -b white 2>&1
    $okPng = (Test-Path $png) -and ((Get-Item $png).Length -gt 15000)

    if ($okSvg -and $okPng) {
        $sz = [math]::Round((Get-Item $png).Length / 1024, 1)
        "OK    $base  (png ${sz} KB)"
    } else {
        "FAIL  $base"
        "      svg=$okSvg png=$okPng"
        ($r1 | Select-String -Pattern "Error" | Select-Object -First 2) | ForEach-Object { "      $_" }
        ($r2 | Select-String -Pattern "Error" | Select-Object -First 2) | ForEach-Object { "      $_" }
    }
}
