# fix_final.ps1 - Corrige el patrón residual: header(' . APP_BASE . '/path')

Get-ChildItem -Path 'controllers' -Recurse -Include '*.php' |
  Where-Object { $_.Name -ne 'SetupController.php' } | ForEach-Object {

    $content = [System.IO.File]::ReadAllText($_.FullName, [System.Text.Encoding]::UTF8)
    $orig    = $content

    # Patrón residual: header(' . APP_BASE . '/path')
    # Debe ser:        header('Location: ' . APP_BASE . '/path')
    $content = $content.Replace("header(' . APP_BASE . '/", "header('Location: ' . APP_BASE . '/")
    $content = $content.Replace('header(" . APP_BASE . "/', 'header("Location: " . APP_BASE . "/')

    # Cualquier otro residuo de ' . APP_BASE . ' que no fue capturado antes
    # (patrón: comilla simple, espacio, punto, espacio, APP_BASE)
    # Ej: = ' . APP_BASE . '/path  → = APP_BASE . '/path
    $content = $content.Replace("' . APP_BASE . '/", "' . APP_BASE . '/")   # ya correcto, skip

    if ($content -ne $orig) {
        [System.IO.File]::WriteAllText($_.FullName, $content, [System.Text.Encoding]::UTF8)
        Write-Host "Fixed: $($_.Name)"
    }
}

Write-Host "Listo."
