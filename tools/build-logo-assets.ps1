# Builds the site logo and favicon files from the two brand source images.
# Usage: powershell -File tools/build-logo-assets.ps1 <header-logo.png> <profile-logo.png>

param(
    [Parameter(Mandatory = $true)][string]$HeaderLogo,
    [Parameter(Mandatory = $true)][string]$ProfileLogo
)

Add-Type -AssemblyName System.Drawing

$outDir = Join-Path $PSScriptRoot "..\assets\logo"
if (-not (Test-Path $outDir)) { New-Item -ItemType Directory -Path $outDir | Out-Null }
$outDir = (Resolve-Path $outDir).Path

function Save-Resized {
    param([System.Drawing.Bitmap]$Source, [int]$Size, [string]$Path)

    $bmp = New-Object System.Drawing.Bitmap($Size, $Size)
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    $g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
    $g.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
    $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
    $g.DrawImage($Source, (New-Object System.Drawing.Rectangle(0, 0, $Size, $Size)))
    $g.Dispose()
    $bmp.Save($Path, [System.Drawing.Imaging.ImageFormat]::Png)
    $bmp.Dispose()
    Write-Output ("  {0}  {1}x{1}" -f (Split-Path $Path -Leaf), $Size)
}

# Wordmark: trim the transparent padding so the lockup can be sized by height in CSS.
$src = [System.Drawing.Bitmap]::FromFile((Resolve-Path $HeaderLogo).Path)
$minX = $src.Width; $maxX = -1; $minY = $src.Height; $maxY = -1
for ($y = 0; $y -lt $src.Height; $y++) {
    for ($x = 0; $x -lt $src.Width; $x++) {
        if ($src.GetPixel($x, $y).A -gt 12) {
            if ($x -lt $minX) { $minX = $x }
            if ($x -gt $maxX) { $maxX = $x }
            if ($y -lt $minY) { $minY = $y }
            if ($y -gt $maxY) { $maxY = $y }
        }
    }
}
$rect = New-Object System.Drawing.Rectangle($minX, $minY, ($maxX - $minX + 1), ($maxY - $minY + 1))
$wordmark = $src.Clone($rect, [System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
$wordmarkPath = Join-Path $outDir "logo-wordmark.png"
$wordmark.Save($wordmarkPath, [System.Drawing.Imaging.ImageFormat]::Png)
Write-Output ("  logo-wordmark.png  {0}x{1}" -f $wordmark.Width, $wordmark.Height)

# Text half of the lockup in white. The pin has to stay green wherever it is used,
# because its bird is knocked out in white and disappears in an all-white version.
$gapStart = -1; $gapEnd = -1; $runStart = -1
for ($x = 0; $x -lt $wordmark.Width; $x++) {
    $empty = $true
    for ($y = 0; $y -lt $wordmark.Height; $y++) {
        if ($wordmark.GetPixel($x, $y).A -gt 12) { $empty = $false; break }
    }
    if ($empty) {
        if ($runStart -lt 0) { $runStart = $x }
        if (($x - $runStart) -gt ($gapEnd - $gapStart)) { $gapStart = $runStart; $gapEnd = $x }
    }
    else { $runStart = -1 }
}
$textX = if ($gapEnd -gt 0) { $gapEnd } else { 0 }
$textRect = New-Object System.Drawing.Rectangle($textX, 0, ($wordmark.Width - $textX), $wordmark.Height)
$textPart = $wordmark.Clone($textRect, [System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
$textLight = New-Object System.Drawing.Bitmap($textPart.Width, $textPart.Height, [System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
for ($y = 0; $y -lt $textPart.Height; $y++) {
    for ($x = 0; $x -lt $textPart.Width; $x++) {
        $a = $textPart.GetPixel($x, $y).A
        $textLight.SetPixel($x, $y, [System.Drawing.Color]::FromArgb($a, 255, 255, 255))
    }
}
$textLight.Save((Join-Path $outDir "logo-text-light.png"), [System.Drawing.Imaging.ImageFormat]::Png)
Write-Output ("  logo-text-light.png  {0}x{1}" -f $textLight.Width, $textLight.Height)
$textLight.Dispose()
$textPart.Dispose()

# Same lockup in white, for placement on dark backgrounds.
$light = New-Object System.Drawing.Bitmap($wordmark.Width, $wordmark.Height, [System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
for ($y = 0; $y -lt $wordmark.Height; $y++) {
    for ($x = 0; $x -lt $wordmark.Width; $x++) {
        $a = $wordmark.GetPixel($x, $y).A
        $light.SetPixel($x, $y, [System.Drawing.Color]::FromArgb($a, 255, 255, 255))
    }
}
$light.Save((Join-Path $outDir "logo-wordmark-light.png"), [System.Drawing.Imaging.ImageFormat]::Png)
Write-Output ("  logo-wordmark-light.png  {0}x{1}" -f $light.Width, $light.Height)
$light.Dispose()
$wordmark.Dispose()
$src.Dispose()

# Square mark drives the favicons and the footer badge.
$mark = [System.Drawing.Bitmap]::FromFile((Resolve-Path $ProfileLogo).Path)
$mark.Save((Join-Path $outDir "logo-mark.png"), [System.Drawing.Imaging.ImageFormat]::Png)
Write-Output ("  logo-mark.png  {0}x{1}" -f $mark.Width, $mark.Height)
Save-Resized -Source $mark -Size 32 -Path (Join-Path $outDir "favicon-32.png")
Save-Resized -Source $mark -Size 180 -Path (Join-Path $outDir "apple-touch-icon.png")
$mark.Dispose()
