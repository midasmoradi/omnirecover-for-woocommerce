# Build installable WordPress plugin zip (respects .distignore).
$ErrorActionPreference = 'Stop'
$pluginRoot = Split-Path -Parent $PSScriptRoot
$version = '0.1.1'
$releasesDir = Join-Path $pluginRoot 'releases'
$stagingParent = Join-Path $env:TEMP 'omnirecover-build'
$staging = Join-Path $stagingParent 'omnirecover-for-woocommerce'
$zipPath = Join-Path $releasesDir "omnirecover-for-woocommerce-$version.zip"

$excludeDirs = @('.git', 'vendor', 'node_modules', 'src', '.github', 'releases', 'scripts')
$excludeFiles = @(
	'.gitignore', '.distignore', 'package.json', 'composer.json', 'composer.lock',
	'phpcs.xml.dist', 'README.md', 'build-release.ps1'
)

if (Test-Path -LiteralPath $stagingParent) {
	Remove-Item -LiteralPath $stagingParent -Recurse -Force
}
New-Item -ItemType Directory -Path $releasesDir -Force | Out-Null
New-Item -ItemType Directory -Path $staging -Force | Out-Null

Get-ChildItem -LiteralPath $pluginRoot -Force | ForEach-Object {
	$name = $_.Name
	if ($excludeDirs -contains $name) { return }
	if ($excludeFiles -contains $name) { return }
	if ($_.PSIsContainer) {
		Copy-Item -LiteralPath $_.FullName -Destination (Join-Path $staging $name) -Recurse -Force
	} else {
		Copy-Item -LiteralPath $_.FullName -Destination (Join-Path $staging $name) -Force
	}
}

if (Test-Path -LiteralPath $zipPath) {
	Remove-Item -LiteralPath $zipPath -Force
}

if (-not (Test-Path -LiteralPath $staging)) {
	throw "Staging folder missing: $staging"
}

# tar avoids PowerShell issues with paths containing brackets (e.g. d:\[GitHub]\...).
$tar = Get-Command tar -ErrorAction SilentlyContinue
if ($tar) {
	Push-Location -LiteralPath $stagingParent
	try {
		& tar -a -c -f $zipPath 'omnirecover-for-woocommerce'
	} finally {
		Pop-Location
	}
} else {
	Compress-Archive -LiteralPath $staging -DestinationPath $zipPath -CompressionLevel Optimal
}

$info = Get-Item -LiteralPath $zipPath
Write-Host "Created: $($info.FullName)"
Write-Host "Size: $([math]::Round($info.Length / 1KB, 1)) KB"
