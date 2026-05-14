# sync_to_leon.ps1
# Sincroniza el código de Managua a León SIN tocar config/app.json ni la BD

$src  = "c:\xampp\htdocs\soleipharmav2"
$dest = "c:\xampp\htdocs\soleipharmav2leon"

# Carpetas a sincronizar (excluye config para proteger app.json de León)
$folders = @('controllers','views','helpers','models','vendor','database','assets')

foreach ($folder in $folders) {
    $s = Join-Path $src  $folder
    $d = Join-Path $dest $folder
    if (Test-Path $s) {
        xcopy $s $d /E /Y /Q /I
        Write-Host "Synced: $folder"
    }
}

# Copiar index.php y otros archivos raíz (NO config)
Copy-Item -Force (Join-Path $src 'index.php')   (Join-Path $dest 'index.php')
Write-Host "Synced: index.php"

# Copiar config EXCEPTO app.json
$configDest = Join-Path $dest 'config'
Get-ChildItem (Join-Path $src 'config') | Where-Object { $_.Name -ne 'app.json' } | ForEach-Object {
    Copy-Item -Force $_.FullName (Join-Path $configDest $_.Name)
    Write-Host "Synced config: $($_.Name)"
}

Write-Host "`nSync completado. app.json de Leon conservado."
