$folders = @('controllers','views','config')
$files = Get-ChildItem -Path $folders -Recurse -Include '*.php' |
         Where-Object { $_.Name -ne 'SetupController.php' -and $_.FullName -notmatch 'vendor' }

foreach ($file in $files) {
    $content = [System.IO.File]::ReadAllText($file.FullName, [System.Text.Encoding]::UTF8)

    # Reemplazar rutas hardcodeadas en comilla simple
    $updated = $content.Replace("'/soleipharmav2/", "' . APP_BASE . '/")

    # Reemplazar rutas hardcodeadas en comilla doble
    $updated = $updated.Replace('"/soleipharmav2/', '" . APP_BASE . "/')

    if ($updated -ne $content) {
        [System.IO.File]::WriteAllText($file.FullName, $updated, [System.Text.Encoding]::UTF8)
        Write-Host "Fixed: $($file.Name)"
    }
}

Write-Host "Done."
