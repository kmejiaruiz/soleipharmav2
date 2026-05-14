<?php
// views/admin/inventory_report.php
$bodegaLabel  = $bodegaLabels[$bodega] ?? $bodega;
$totalSistema = array_sum(array_column($reportRows, 'existencia_sistema'));
$reportNum    = date('YmdHis');

// Detectar bodega sin stock: reporte generado pero stock total = 0 
// (aplica a debito y merma donde el stock puede estar todo en cero)
$bodegaSinStock = $generated && $bodega !== 'sucursal' && $totalSistema == 0;
// Para sucursal, detectar si no hay ningún producto con stock > 0
if ($generated && $bodega === 'leon' && $totalSistema == 0) {
    $bodegaSinStock = true;
}
?>

<!-- Botón imprimir (oculto al imprimir) -->
<section class="content-header no-print">
    <div class="row mb-2 align-items-center">
        <div class="col-sm-6"><h1><i class="fas fa-print"></i> Reporte de Bodega</h1></div>
        <div class="col-sm-6 text-right">
            <?php if ($generated && !empty($reportRows)): ?>
            <button onclick="window.print()" class="btn btn-primary btn-sm">
                <i class="fas fa-print"></i> Imprimir / PDF
            </button>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Formulario de filtros -->
<section class="content no-print">
    <div class="container-fluid">
        <div class="card card-outline card-primary mb-4">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-filter"></i> Parámetros del Reporte</h3></div>
            <div class="card-body">
                <form method="GET" action="<?= APP_BASE ?>/inventory/report" class="row align-items-end">
                    <div class="col-md-5">
                        <label><span class="text-danger">*</span> Proveedor</label>
                        <select name="supplier_id" class="form-control" required>
                            <option value="">— Seleccione un proveedor —</option>
                            <?php foreach ($suppliers as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= $supplierId == $s['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label><span class="text-danger">*</span> Bodega</label>
                        <select name="bodega" class="form-control" required>
                            <option value="">— Seleccione bodega —</option>
                            <option value="merma"  <?= $bodega === 'merma'  ? 'selected' : '' ?>>Merma / Descarte</option>
                            <option value="debito" <?= $bodega === 'debito' ? 'selected' : '' ?>>Bodega de Débito — Devoluciones al Proveedor</option>
                            <option value="leon"   <?= $bodega === 'leon'   ? 'selected' : '' ?>><?= htmlspecialchars(defined('BRANCH') && BRANCH !== '' ? BRANCH : 'Sucursal León') ?></option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary btn-block mt-4">
                            <i class="fas fa-search"></i> Generar Reporte
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>


<?php if (!empty($reportError)): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
        title: 'Reporte no puede ser ejecutado',
        html:  'Sin datos para reporte.',
        icon:  'warning',
        confirmButtonText: 'Entendido'
    }).then(function () {
        // Limpiar los campos del formulario
        var sel = document.querySelector('select[name="supplier_id"]');
        var bod = document.querySelector('select[name="bodega"]');
        if (sel) sel.value = '';
        if (bod) bod.value = '';
    });
});
</script>
<?php endif; ?>

<?php if ($generated): ?>
<!-- ═══════════════════════════════════════ REPORTE IMPRIMIBLE ════════════════ -->
<div class="inv-report-wrap" id="printableReport">

    <!-- Encabezado estilo Nota de Débito -->
    <table class="inv-header-table">
        <tr>
            <td class="inv-company-block">
                <div class="inv-company">FARMACIA SOLEI</div>
                <div class="inv-company-sub">Sistema de Gestión de Inventario</div>
            </td>
            <td class="inv-title-block">
                <div class="inv-report-type">CONTEO CÍCLICO DE INVENTARIO</div>
                <div class="inv-report-no">Reporte N°: <strong>RC-<?= $reportNum ?></strong></div>
            </td>
        </tr>
    </table>

    <!-- Parámetros -->
    <table class="inv-params-table">
        <tr>
            <td class="label">Proveedor:</td>
            <td colspan="3"><strong><?= htmlspecialchars($supplierName) ?></strong></td>
        </tr>
        <tr>
            <td class="label">Bodega:</td>
            <td colspan="3"><?= htmlspecialchars($bodegaLabel) ?></td>
        </tr>
        <tr>
            <td class="label">Fecha de Emisión:</td>
            <td><?= date('d/m/Y H:i') ?></td>
            <td class="label">Elaborado por:</td>
            <td><?= htmlspecialchars($_SESSION['user']['username'] ?? '') ?></td>
        </tr>
    </table>

    <!-- Tabla de productos -->
    <?php if (empty($reportRows)): ?>
    <div style="text-align:center;padding:30px;color:#666;">
        No se encontraron productos en el catálogo de este proveedor.
    </div>
    <?php else: ?>

    <table class="inv-items-table">
        <thead>
            <tr>
                <th style="width:80px">SKU</th>
                <th>Descripción del Producto</th>
                <th class="text-right" style="width:100px">Exist. Sistema</th>
                <th class="text-right" style="width:180px">Conteo Físico</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reportRows as $i => $row): ?>
            <tr class="<?= $i % 2 === 0 ? 'r-even' : 'r-odd' ?>">
                <td><code><?= htmlspecialchars($row['sku'] ?? '—') ?></code></td>
                <td><?= htmlspecialchars($row['producto']) ?></td>
                <td class="text-right"><?= number_format($row['existencia_sistema']) ?></td>
                <td class="blank-col">&nbsp;</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="inv-total-row">
                <td colspan="2" class="text-right"><strong>TOTAL:</strong></td>
                <td class="text-right"><strong><?= number_format($totalSistema) ?></strong></td>
                <td class="blank-col">&nbsp;</td>
            </tr>
            <tr>
                <td colspan="4" style="text-align:right;font-size:9px;padding-top:4px;">
                    <?= count($reportRows) ?> productos registrados
                </td>
            </tr>
        </tfoot>
    </table>

    <!-- Observaciones -->
    <table class="inv-obs-table">
        <tr>
            <td class="label">Observaciones:</td>
            <td>&nbsp;</td>
        </tr>
        <tr><td colspan="2" style="height:24px;">&nbsp;</td></tr>
    </table>

    <!-- Firmas -->
    <table class="inv-sig-table">
        <tr>
            <td>
                <div class="sig-line"></div>
                <div class="sig-label">Elaborado por</div>
            </td>
            <td>
                <div class="sig-line"></div>
                <div class="sig-label">Verificado por</div>
            </td>
            <td>
                <div class="sig-line"></div>
                <div class="sig-label">Autorizado por</div>
            </td>
        </tr>
    </table>

    <?php endif; ?>

    <div class="inv-footer">
        Farmacia Solei &mdash; SoleiPharma &mdash; <?= date('d/m/Y H:i:s') ?>
    </div>
</div>
<?php endif; ?>

<!-- ── Micromodal: Bodega sin stock ─────────────────────────────────── -->
<div class="modal micromodal-slide" id="modal-bodega-vacia" aria-hidden="true">
    <div class="modal__overlay" tabindex="-1">
        <div class="modal__container" role="dialog" aria-modal="true"
             aria-labelledby="modal-bodega-vacia-title"
             style="max-width:420px;text-align:center;">
            <div style="padding:8px 0 16px;">
                <svg xmlns="http://www.w3.org/2000/svg" style="width:56px;height:56px;color:#ffc107;" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
            </div>
            <h2 class="modal__title" id="modal-bodega-vacia-title"
                style="font-size:1.1rem;margin-bottom:10px;">
                Sin datos en la bodega
            </h2>
            <div class="modal__content">
                <p style="margin:0;">
                    La <strong><?= htmlspecialchars($bodegaLabel) ?></strong>
                    no tiene unidades registradas para el proveedor seleccionado.
                    <br><br>
                    No es posible generar el reporte de conteo.
                </p>
            </div>
            <div style="margin-top:18px;">
                <button class="btn btn-warning btn-sm" onclick="MicroModal.close('modal-bodega-vacia')">
                    Entendido
                </button>
            </div>
        </div>
    </div>
</div>

<?php if ($bodegaSinStock): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof MicroModal !== 'undefined') {
        MicroModal.show('modal-bodega-vacia', { disableFocus: true });
    }
});
</script>
<?php endif; ?>

<style>
/* ─── Estilos pantalla ─── */
.inv-report-wrap {
    background: #fff;
    max-width: 800px;
    margin: 0 auto 30px;
    padding: 22px 28px;
    border: 1px solid #c8c8c8;
    box-shadow: 0 2px 10px rgba(0,0,0,.12);
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 11px;
    color: #111;
}
.inv-header-table {
    width: 100%;
    border-bottom: 3px double #003366;
    margin-bottom: 10px;
    border-collapse: collapse;
}
.inv-company-block { width: 60%; vertical-align: bottom; padding-bottom: 6px; }
.inv-title-block   { width: 40%; vertical-align: bottom; text-align: right; padding-bottom: 6px; border: none; }
.inv-company       { font-size: 18px; font-weight: 800; color: #003366; letter-spacing: 1px; }
.inv-company-sub   { font-size: 9px; color: #666; }
.inv-report-type   { font-size: 13px; font-weight: 700; color: #003366; }
.inv-report-no     { font-size: 10px; color: #444; margin-top: 2px; }

.inv-params-table {
    width: 100%;
    border: 1px solid #b0b8cc;
    border-collapse: collapse;
    background: #f4f7ff;
    margin-bottom: 12px;
}
.inv-params-table td { padding: 4px 8px; border: 1px solid #c4cce0; font-size: 11px; }
.inv-params-table .label { font-weight: 600; background: #dde5f5; width: 120px; white-space: nowrap; }

.inv-items-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 10px;
}
.inv-items-table th {
    background: #003366;
    color: #fff;
    padding: 5px 7px;
    font-size: 10px;
    border: 1px solid #001e44;
    text-transform: uppercase;
    letter-spacing: .4px;
}
.inv-items-table td {
    padding: 4px 7px;
    border: 1px solid #c8c8c8;
    font-size: 11px;
    vertical-align: middle;
}
.r-even { background: #fff; }
.r-odd  { background: #eff4ff; }
.blank-col { background: #fafafa; }
.inv-total-row { background: #dce6f8; font-weight: bold; }
.inv-total-row td { border: 1px solid #9aaed0; }

.text-right { text-align: right !important; }
code { font-size: 10px; background: #f0f0f0; padding: 1px 3px; border-radius: 2px; }

.inv-obs-table {
    width: 100%;
    border-collapse: collapse;
    border: 1px solid #ccc;
    margin-bottom: 18px;
}
.inv-obs-table .label { font-weight: 600; background: #dde5f5; width: 110px; padding: 4px 8px; border-right: 1px solid #ccc; }
.inv-obs-table td { padding: 4px 8px; }

.inv-sig-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 14px;
}
.inv-sig-table td { text-align: center; padding: 4px 16px; }
.sig-line  { border-top: 1px solid #333; margin: 0 auto 4px; width: 140px; }
.sig-label { font-size: 9px; color: #555; }

.inv-footer {
    border-top: 1px solid #ccc;
    padding-top: 5px;
    font-size: 9px;
    color: #999;
    text-align: center;
}

/* ─── Estilos impresión ─── */
@media print {
    .no-print, aside, header.main-header, footer, nav { display: none !important; }
    body, .wrapper, .content-wrapper { background: #fff !important; margin: 0 !important; padding: 0 !important; font-size: 12pt !important; }
    .inv-report-wrap { box-shadow: none; border: none; max-width: 100%; padding: 10px 14px; margin: 0; font-size: 12pt; }
    .inv-company    { font-size: 18pt !important; }
    .inv-report-type { font-size: 14pt !important; }
    .inv-report-no, .inv-company-sub { font-size: 10pt !important; }
    .inv-params-table td { font-size: 11pt !important; padding: 4px 8px; }
    .inv-items-table th { font-size: 10pt !important; padding: 5px 7px; -webkit-print-color-adjust: exact; print-color-adjust: exact; background: #003366 !important; color: #fff !important; }
    .inv-items-table td { font-size: 11pt !important; padding: 5px 7px; }
    .inv-total-row td { font-size: 11pt !important; }
    .sig-label, .inv-footer { font-size: 9pt !important; }
    .r-odd  { -webkit-print-color-adjust: exact; print-color-adjust: exact; background: #eff4ff !important; }
    .inv-total-row { -webkit-print-color-adjust: exact; print-color-adjust: exact; background: #dce6f8 !important; }
}
</style>
