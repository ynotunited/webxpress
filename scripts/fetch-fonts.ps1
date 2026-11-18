# Fetches the Plus Jakarta Sans subsets this site actually renders and writes the
# self-hosted @font-face rules in resources/css/fonts.css.
#
# Run once when the font needs updating, then commit public/fonts and
# resources/css/fonts.css together:
#
#     powershell -File scripts/fetch-fonts.ps1
#
# Requires network access. The output is committed so builds and deployments
# never depend on Google Fonts being reachable.

$ErrorActionPreference = 'Stop'

$ua      = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36'
$root    = Split-Path -Parent $PSScriptRoot
$fontDir = Join-Path $root 'public\fonts'
$cssOut  = Join-Path $root 'resources\css\fonts.css'

New-Item -ItemType Directory -Force -Path $fontDir | Out-Null

$css = (Invoke-WebRequest `
    -Uri 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap' `
    -Headers @{ 'User-Agent' = $ua } -TimeoutSec 30 -UseBasicParsing).Content

$blocks = [regex]::Matches($css, '@font-face\s*\{[^}]+\}')

$rules = @()
$seen  = @{}

foreach ($b in $blocks) {
    $t = $b.Value

    if ($t -notmatch 'unicode-range:\s*([^;]+);') { continue }
    $range = $Matches[1]

    # Google slices one family into four subsets per weight. Cyrillic is dead
    # weight on an English-language site; latin, latin-ext and vietnamese between
    # them cover the Yoruba sub-dotted vowels (o-underdot, e-underdot) that appear
    # in real Nigerian names, because Google files those ranges in its
    # "vietnamese" slice.
    if     ($range -like 'U+0000-00FF*') { $subset = 'latin' }
    elseif ($range -like 'U+0100-02BA*') { $subset = 'latin-ext' }
    elseif ($range -like 'U+0102-0103*') { $subset = 'vietnamese' }
    else { continue }

    if ($t -notmatch 'font-weight:\s*(\d+)') { continue }
    $weight = $Matches[1]

    if ($t -notmatch 'url\((https://[^)]+\.woff2)\)') { continue }
    $url = $Matches[1]

    $file = "plus-jakarta-sans-$weight-$subset.woff2"

    if (-not $seen.ContainsKey($file)) {
        Invoke-WebRequest -Uri $url -Headers @{ 'User-Agent' = $ua } -TimeoutSec 30 -OutFile (Join-Path $fontDir $file)
        $seen[$file] = (Get-Item (Join-Path $fontDir $file)).Length
    }

    $style = if ($t -match 'font-style:\s*(\w+)') { $Matches[1] } else { 'normal' }

    $rules += @"

/* $weight / $subset */
@font-face {
    font-family: 'Plus Jakarta Sans';
    font-style: $style;
    font-weight: $weight;
    font-display: swap;
    src: url('/fonts/$file') format('woff2');
    unicode-range: $range;
}
"@
}

$header = @'
/*
 * Plus Jakarta Sans, self-hosted.
 *
 * This used to come from fonts.googleapis.com and fonts.gstatic.com, which made
 * them the only two third-party origins the site depended on: every page view
 * handed Google the visitor's IP, user-agent and the exact URL they were on. It
 * also meant a CSP tight enough to matter had to permit those two origins.
 *
 * Self-hosting keeps the typography identical while leaving the policy able to
 * say "default-src 'self'" and mean it.
 *
 * Regenerate with: powershell -File scripts/fetch-fonts.ps1
 *
 * The rules keep Google's unicode-range subsetting, so a browser still downloads
 * one file per page instead of the whole family, and a glyph outside all three
 * ranges falls back to the system face rather than rendering as a box.
 *
 * Plus Jakarta Sans is licensed SIL OFL 1.1.
 */

'@

Set-Content -Path $cssOut -Value ($header + ($rules -join "`n")) -Encoding UTF8

$total = ($seen.Values | Measure-Object -Sum).Sum
Write-Host ("wrote {0} font files ({1} KB) to public/fonts" -f $seen.Count, [math]::Round($total / 1kb, 1))
Write-Host ("wrote {0} @font-face rules to resources/css/fonts.css" -f $rules.Count)