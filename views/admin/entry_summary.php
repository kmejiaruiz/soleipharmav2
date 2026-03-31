<?php
// views/admin/entry_summary.php
// Variables: $entry, $items, $qtyDiffs, $hasCostDebitNote, $hasQtyDebitNote,
//            $invoiceTotal, $systemTotal, $receptionDate, $userName, $orderId
?>
<section class="content-header">
    <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Resumen de Entrada — Pedido #<?= htmlspecialchars($orderId ?? '') ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="/soleipharmav2/admin/index">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="/soleipharmav2/admin/index">Dashboard</a></li>
                    <li class="breadcrumb-item active">Resumen de Entrada — Pedido #<?= htmlspecialchars($orderId ?? '') ?></li>
                </ol>
            </div>
        </div>
</section>
<section class="content">
    <div class="row">
        <!-- Info general -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><strong>Información General</strong></div>
                <div class="card-body">
                    <p><strong>Entrada #:</strong> <?= $entry['id'] ?></p>
                    <p><strong>Fecha:</strong> <?= htmlspecialchars($receptionDate) ?></p>
                            <span class="font-weight-bold">Estado:</span>
                        <?php
                            $sm = ['pending' => ['Pendiente','warning'], 'applied' => ['Aplicado','primary'], 'received' => ['Recibido','success']];
                            $si = $sm[strtolower($entry['status'] ?? 'received')] ?? ['Procesado','secondary'];
                        ?>
                        <span class="badge badge-<?= $si[1] ?> p-1"><?= $si[0] ?></span>
                    <?php $diff = $invoiceTotal - $systemTotal; ?>
                    <?php if (abs($diff) > 0.01): ?>
                        <p><strong>Diferencia de Totales:</strong>
                            <span class="text-<?= $diff > 0 ? 'danger' : 'success' ?>">
                                C$<?= number_format($diff, 2) ?>
                            </span>
                        </p>
                    <?php else: ?>
                        <p><strong>Diferencia de Totales:</strong> <span class="text-success">Sin diferencia</span></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Notas de débito -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><strong>Notas de Débito</strong></div>
                <div class="card-body">
                    <?php if ($hasCostDebitNote || $hasQtyDebitNote): ?>
                        <div class="alert alert-warning">
                            <strong>Atención: Se ha generado una Nota de Débito unificada</strong><br>
                            Existen diferencias ya sea por costos (factura > sistema) o por cantidades recibidas.
                            <br>
                            <a href="/soleipharmav2/order/debitNote?id=<?= $entry['id'] ?>"
                               target="_blank" class="btn btn-warning btn-sm mt-2">
                               <i class="fas fa-file-pdf"></i> Ver Nota de Débito Unificada
                            </a>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Sin notas de débito. Todo coincide.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Botón para Detalles de Facturación -->
    <div class="mb-4">
        <button type="button" class="btn btn-dark" style="background-color: #343a40; border-color: #343a40;" onclick="MicroModal.show('invoice-details-modal')">
            <i class="fas fa-file-invoice-dollar"></i> Ver Detalles de Facturación
        </button>
    </div>

    <!-- Modal de Detalles de Facturación -->
    <div class="modal micromodal-slide" id="invoice-details-modal" aria-hidden="true">
      <div class="modal__overlay" tabindex="-1" data-micromodal-close>
        <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="invoice-details-title" style="max-width: 750px;">
          <header class="modal__header">
            <h2 class="modal__title" id="invoice-details-title">Comparativa de Facturación</h2>
            <button class="modal__close" aria-label="Cerrar modal" data-micromodal-close></button>
          </header>
          <main class="modal__content" id="invoice-details-content">
            <div class="row">
                <!-- Columna Sistema -->
                <div class="col-md-6">
                    <h6 class="text-center mb-3" style="color: #6c757d;"><strong>Totales del Sistema</strong></h6>
                    <div class="form-group">
                        <label>Subtotal Sistema</label>
                        <input type="text" class="micromodal-input text-right" value="C$ <?= number_format($entry['system_subtotal'], 2) ?>" readonly style="background:#f8f9fa; cursor:not-allowed;">
                    </div>
                    <div class="form-group">
                        <label>IVA Sistema (15%)</label>
                        <input type="text" class="micromodal-input text-right" value="C$ <?= number_format($entry['system_tax'], 2) ?>" readonly style="background:#f8f9fa; cursor:not-allowed;">
                    </div>
                    <div class="form-group">
                        <label>Total Sistema</label>
                        <input type="text" class="micromodal-input text-right" value="C$ <?= number_format($systemTotal, 2) ?>" readonly style="background:#e9ecef; font-weight:bold; cursor:not-allowed;">
                    </div>
                </div>

                <!-- Columna Factura Física -->
                <div class="col-md-6">
                    <h6 class="text-center mb-3" style="color: #6c757d;"><strong>Factura Física</strong></h6>
                    <div class="form-group">
                        <label>Subtotal Factura</label>
                        <input type="text" class="micromodal-input text-right" value="C$ <?= number_format($entry['invoice_subtotal'], 2) ?>" readonly style="background:#f8f9fa; cursor:not-allowed;">
                    </div>
                    <div class="form-group">
                        <label>IVA Factura</label>
                        <input type="text" class="micromodal-input text-right" value="C$ <?= number_format($entry['invoice_tax'], 2) ?>" readonly style="background:#f8f9fa; cursor:not-allowed;">
                    </div>
                    <div class="form-group">
                        <label>Total Factura</label>
                        <input type="text" class="micromodal-input text-right" value="C$ <?= number_format($invoiceTotal, 2) ?>" readonly style="background:#e9ecef; font-weight:bold; cursor:not-allowed;">
                    </div>
                </div>
            </div>
          </main>
          <footer class="modal__footer" style="text-align: right;">
            <button class="modal__btn modal__btn-primary" data-micromodal-close aria-label="Cerrar">Cerrar Compendio</button>
          </footer>
        </div>
      </div>
    </div>

    <!-- Tabla de items recibidos -->
    <div class="card">
        <div class="card-header"><strong>Detalle de Productos</strong></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Producto</th>
                        <th>Costo Unit.</th>
                        <th>Ordenado</th>
                        <th>Recibido</th>
                        <th>Diferencia</th>
                        <th>Justificación</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $it): ?>
                        <?php $diff = $it['received_qty'] - $it['ordered_qty']; ?>
                        <tr class="<?= $diff > 0 ? 'table-danger' : ($diff < 0 ? 'table-warning' : '') ?>">
                            <td><?= htmlspecialchars($it['sku'] ?? '') ?></td>
                            <td><?= htmlspecialchars($it['name'] ?? '') ?></td>
                            <td>C$<?= number_format($it['cost_unit'], 2) ?></td>
                            <td><?= $it['ordered_qty'] ?></td>
                            <td><?= $it['received_qty'] ?></td>
                            <td>
                                <?php if ($diff > 0): ?>
                                    <span class="text-danger">+<?= $diff ?></span>
                                <?php elseif ($diff < 0): ?>
                                    <span class="text-warning"><?= $diff ?></span>
                                <?php else: ?>
                                    <span class="text-success">0</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($it['justification'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Botones -->
    <div class="mb-4 d-flex align-items-center">
        <a href="/soleipharmav2/order/goodsEntryReport?id=<?= $orderId ?>"
           target="_blank" class="btn btn-primary mr-2">
            <i class="fas fa-file-pdf"></i> Ver Boleta de Recepción (PDF)
        </a>
        <button onclick="window.print()" class="btn btn-secondary mr-2">
            <i class="fas fa-print"></i> Imprimir Esta Página
        </button>
        <a href="/soleipharmav2/order/index" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Pedidos
        </a>
    </div>

    <style>
        @media print {
            @page { size: A4 portrait; margin: 15mm 15mm 20mm; }
            .content-header, .main-header, .main-sidebar, .main-footer,
            .mb-4.d-flex, #invoice-details-modal, button, a.btn { display: none !important; }
            body, .content-wrapper, .wrapper { font-size: 13pt !important; background: #fff !important; }
            .card { border: 1px solid #ccc !important; page-break-inside: avoid; }
            .card-header { font-size: 13pt !important; }
            .card-body, .card-body td, .card-body th, .card-body p, .card-body li { font-size: 12pt !important; }
            h1, h2, h3, h4 { font-size: 15pt !important; }
            table { width: 100% !important; }
        }
    </style>
</section>
