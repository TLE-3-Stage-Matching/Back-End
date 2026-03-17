# Fix UTF-8 mojibake in docs/API.md
# Run from repo root: .\fix-api-doc-encoding.ps1

$path = Join-Path $PSScriptRoot "docs\API.md"
$content = [System.IO.File]::ReadAllText($path, [System.Text.Encoding]::UTF8)

$enc = [char]0x00E2
$eur = [char]0x20AC
$ldq = [char]0x201C
$rdq = [char]0x201D
$endash = [char]0x2013
$emdash = [char]0x2014
$dagger = [char]0x2020

# En-dash: mojibake â€" (â + € + ") -> –
$content = $content.Replace("$enc$eur$ldq", $endash)
# Em-dash: mojibake â€" (â + € + ") -> —
$content = $content.Replace("$enc$eur$rdq", $emdash)

# Left double quote mojibake â€œ (â + € + œ U+0153) -> straight "
$content = $content.Replace("$enc$eur" + [char]0x0153, '"')
# Right double quote: â€ž (â + € + ž U+017E) or â€ (â + € + U+009D) -> straight "
$content = $content.Replace("$enc$eur" + [char]0x017E, '"')
$content = $content.Replace([string]::Concat($enc, $eur, [char]0x009D), '"')

# Right single quote/apostrophe: â€™ (â + € + ™ U+2122) -> '
$content = $content.Replace("$enc$eur" + [char]0x2122, "'")

# Rightward arrow: â†' (â + † + U+2019 from Windows-1252 0x92) -> →
$arrowRight = [string]::Concat([char]0x00E2, [char]0x2020, [char]0x2019)
$content = $content.Replace($arrowRight, [System.String]::new([char]0x2192))
# Upward arrow: â†' (â + † + U+2018 from Windows-1252 0x91) -> ↑
$arrowUp = [string]::Concat([char]0x00E2, [char]0x2020, [char]0x2018)
$content = $content.Replace($arrowUp, [System.String]::new([char]0x2191))

[System.IO.File]::WriteAllText($path, $content, [System.Text.UTF8Encoding]::new($false))
Write-Host "Done. Fixed mojibake in docs\API.md"
