# fix_views.ps1 — Corrige el reemplazo incorrecto en vistas HTML
# Convierte el patrón roto: href=" . APP_BASE . "/path"
# en el patrón correcto:   href="<?= APP_BASE ?>/path"

Get-ChildItem -Path 'views' -Recurse -Include '*.php' | ForEach-Object {
    $content = [System.IO.File]::ReadAllText($_.FullName, [System.Text.Encoding]::UTF8)

    # Patrón incorrecto generado en atributos HTML:  " . APP_BASE . "/
    # Lo reemplazamos por el PHP echo correcto:      "<?= APP_BASE ?>/
    $updated = $content.Replace('" . APP_BASE . "/', '"<?= APP_BASE ?>/')

    # También por si alguno quedó con comilla simple en HTML:  ' . APP_BASE . '/
    $updated = $updated.Replace("' . APP_BASE . '/", "'<?= APP_BASE ?>/")

    if ($updated -ne $content) {
        [System.IO.File]::WriteAllText($_.FullName, $updated, [System.Text.Encoding]::UTF8)
        Write-Host "Fixed: $($_.Name)"
    }
}

Write-Host "Vistas corregidas."
