# fix_controllers.ps1 - Corrige los dos tipos de error en controllers

Get-ChildItem -Path 'controllers' -Recurse -Include '*.php' |
  Where-Object { $_.Name -ne 'SetupController.php' } | ForEach-Object {

    $content = [System.IO.File]::ReadAllText($_.FullName, [System.Text.Encoding]::UTF8)
    $orig    = $content

    # ── Tipo 1: string que EMPIEZA por la ruta (orphan quote) ──────────────
    # Incorrecto: => ' . APP_BASE . '/path'
    # Correcto:   => APP_BASE . '/path'
    $content = $content.Replace("=> ' . APP_BASE . '/", "=> APP_BASE . '/")
    $content = $content.Replace('=> " . APP_BASE . "/',  '=> APP_BASE . "/')
    $content = $content.Replace("= ' . APP_BASE . '/",  "= APP_BASE . '/")
    $content = $content.Replace('= " . APP_BASE . "/',   '= APP_BASE . "/')

    # También en json_encode arrays con 'key' => ' . APP_BASE . '/...
    $content = $content.Replace("' => ' . APP_BASE . '/", "' => APP_BASE . '/")
    $content = $content.Replace('" => " . APP_BASE . "/', '" => APP_BASE . "/')

    # ── Tipo 2: rutas que NO se reemplazaron (aún tienen /soleipharmav2/) ──
    # En single-quoted: header('Location: /soleipharmav2/path')
    $content = $content.Replace("'Location: /soleipharmav2/", "' . APP_BASE . '/")
    # En double-quoted dentro de header
    $content = $content.Replace('"Location: /soleipharmav2/', '" . APP_BASE . "/')

    # ── Tipo 3: redirect en json_encode que quedó sin reemplazar ───────────
    $content = $content.Replace("'redirect' => '/soleipharmav2/", "'redirect' => APP_BASE . '/")
    $content = $content.Replace('"redirect" => "/soleipharmav2/', '"redirect" => APP_BASE . "/')
    $content = $content.Replace("'receipt_url' => '/soleipharmav2/", "'receipt_url' => APP_BASE . '/")
    $content = $content.Replace('"receipt_url" => "/soleipharmav2/', '"receipt_url" => APP_BASE . "/')
    $content = $content.Replace("'pdf_url' => '/soleipharmav2/", "'pdf_url' => APP_BASE . '/")
    $content = $content.Replace('"pdf_url" => "/soleipharmav2/', '"pdf_url" => APP_BASE . "/')

    if ($content -ne $orig) {
        [System.IO.File]::WriteAllText($_.FullName, $content, [System.Text.Encoding]::UTF8)
        Write-Host "Fixed: $($_.Name)"
    }
}
Write-Host "Controllers corregidos."
