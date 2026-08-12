$ErrorActionPreference = "Stop"
$root = "D:\Work-FIles\yn"

Copy-Item "$root\assets\images\beach.jpg" "$root\assets\images\goa-beach.jpg" -Force

$idMap = [ordered]@{
  "photo-1602216056096-3b40cc0c9944" = "kerala-backwaters.jpg"
  "photo-1593693411515-c202281174ec" = "tea-plantation.jpg"
  "photo-1506905925346-21bda4d32df4" = "hills-mist.jpg"
  "photo-1582510003544-4d00b7f74220" = "temple.jpg"
  "photo-1544735716-392fe2489ffa"   = "forest.jpg"
  "photo-1578662996442-48f60103fc96" = "waterfall.jpg"
  "photo-1512343879784-a96090943e36" = "goa-beach.jpg"
  "photo-1566837497312-7be7830ae9b3" = "lake.jpg"
  "photo-1527631746610-bca00a040d60" = "friends-travel.jpg"
  "photo-1513885535751-8b9238bd345a" = "gift.jpg"
  "photo-1582719508461-905c673771fd" = "resort.jpg"
  "photo-1590490360182-c33d57733427" = "resort-room.jpg"
  "photo-1571896349842-33c89424de2d" = "resort-pool.jpg"
  "photo-1449965408869-eaa3f722e40d" = "car-taxi.jpg"
  "photo-1512453979798-5ea266f8880c" = "dubai.jpg"
  "photo-1508009603885-50cf7c579449" = "thailand.jpg"
  "photo-1525625293386-3f8f99389edd" = "singapore.jpg"
  "photo-1514282401047-d79a71a590e8" = "maldives.jpg"
  "photo-1537996194471-e657df975ab4" = "bali.jpg"
  "photo-1528127269322-539801943592" = "vietnam.jpg"
  "photo-1524492412937-b28074a5d7da" = "rajasthan.jpg"
  "photo-1559827260-dc66d52bef19"   = "island.jpg"
  "photo-1493976040374-85c8e12f0c0e" = "japan.jpg"
  "photo-1507525428034-b723cf961d3e" = "beach.jpg"
  "photo-1578683010236-d716f9a3f461" = "suite.jpg"
}

function Replace-ImagesInFile([string]$path, [string]$prefix) {
  $content = [System.IO.File]::ReadAllText($path)
  $original = $content

  foreach ($id in $idMap.Keys) {
    $local = "$prefix/assets/images/$($idMap[$id])"
    $startToken = "https://images.unsplash.com/$id"
    $searchFrom = 0
    while (($idx = $content.IndexOf($startToken, $searchFrom)) -ge 0) {
      $end = $idx + $startToken.Length
      while ($end -lt $content.Length) {
        $ch = $content[$end]
        if ($ch -eq '"' -or $ch -eq "'" -or $ch -eq ')' -or [char]::IsWhiteSpace($ch)) { break }
        $end++
      }
      $content = $content.Remove($idx, $end - $idx).Insert($idx, $local)
      $searchFrom = $idx + $local.Length
    }
  }

  if ($content -ne $original) {
    [System.IO.File]::WriteAllText($path, $content)
    $left = ([regex]::Matches($content, "images\.unsplash\.com")).Count
    Write-Output "Updated $(Split-Path $path -Leaf) (remaining unsplash: $left)"
  } else {
    Write-Output "No change $(Split-Path $path -Leaf)"
  }
}

Replace-ImagesInFile "$root\index.html" "."
Get-ChildItem "$root\pages\*.html" | ForEach-Object {
  Replace-ImagesInFile $_.FullName ".."
}

Write-Output "--- Remaining Unsplash refs ---"
$hits = Select-String -Path "$root\index.html","$root\pages\*.html" -Pattern "images\.unsplash\.com"
if ($hits) {
  $hits | ForEach-Object { "$($_.Filename):$($_.LineNumber)" }
} else {
  Write-Output "None"
}
