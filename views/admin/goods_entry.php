<?php
$invoiceSaved = ($savedInvoiceSub !== null && $savedInvoiceTax !== null);
$createdDate  = isset($order['created_at']) ? date('d/m/Y', strtotime($order['created_at'])) : date('d/m/Y');
$todayDate    = date('d/m/Y');
?>
<section class="content-header">
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Entrada de Mercadería #<?= htmlspecialchars($order['id'] ?? '') ?></h1>
        </div>
        <div class="col-sm-6 text-right">
            <h3><span id="boletaEstadoBadge" class="badge badge-secondary p-2">ESTADO: GUARDADO</span></h3>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <!-- Tarjeta de Metadatos -->
        <div class="card card-outline card-primary mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle"></i> Datos del Documento</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Boleta</label>
                            <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($order['id'] ?? 'N/A') ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Fecha Boleta</label>
                            <input type="text" class="form-control form-control-sm" value="<?= $todayDate ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Fecha Orden</label>
                            <input type="text" class="form-control form-control-sm" value="<?= $createdDate ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Sucursal</label>
                            <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars(defined('BRANCH') ? BRANCH : 'Principal') ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group mb-0">
                            <label>Proveedor</label>
                            <input type="text" class="form-control form-control-sm"
                                value="<?= htmlspecialchars($order['supplier_name'] ?? 'Sin proveedor asignado') ?>" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form id="goodsEntryForm" action="/soleipharmav2/order/storeGoodsEntry?id=<?= $order['id'] ?>" method="post">
            
            <!-- Botonera de acciones superiores -->
            <div class="d-flex justify-content-end mb-3">
                <button type="button" class="btn btn-secondary mr-2" id="btnCalcSystem">
                    <i class="fas fa-calculator"></i> [F5] Calcular Sistema
                </button>
                <button type="button" class="btn btn-info mr-2" id="btnFacturas" <?= !$invoiceSaved ? 'disabled' : '' ?>>
                    <i class="fas fa-file-invoice-dollar"></i> [F9] Factura vs Sistema
                </button>
                <button type="button" class="btn btn-success" id="btnConfirmF6" <?= !$invoiceSaved ? 'disabled' : '' ?>>
                    <i class="fas fa-check-double"></i> [F6] Aplicar Entrada
                </button>
            </div>

            <!-- Tabla de items -->
            <div class="card">
                <div class="card-body p-0" style="overflow-x: auto;">
                    <table class="table table-bordered table-striped table-hover text-sm" id="itemsTable">
                        <thead class="thead-dark">
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th class="text-right">Esp.</th>
                                <th>Factura</th>
                                <th class="text-right">Factor</th>
                                <th class="text-right">Emp.</th>
                                <th class="text-right bg-primary">Cant Scan</th>
                                <th class="text-right">Emp. Fact</th>
                                <th class="text-right">Cant Fact</th>
                                <th>Mot. Faltante</th>
                                <th class="text-right">Bultos</th>
                                <th class="text-right">C. Bruto</th>
                                <th class="text-right">C. Bruto Emp %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderItems as $it): ?>
                            <tr data-cost="<?= $it['cost'] ?>">
                                <td><?= htmlspecialchars($it['sku'] ?? '') ?></td>
                                <td style="min-width: 250px;"><?= htmlspecialchars($it['name'] ?? '') ?></td>
                                <td class="text-right">0</td>
                                <td><?= htmlspecialchars($order['id'] ?? '') ?></td>
                                <td class="text-right">1.000</td>
                                <td class="text-right">1.000</td>
                                
                                <td style="min-width: 120px;">
                                    <input type="number" name="received_quantities[<?= $it['product_id'] ?>]" value="<?= $it['ordered_qty'] ?>" min="0" class="form-control form-control-sm text-right received-qty transition-bg">
                                </td>
                                
                                <td class="text-right"><?= number_format($it['ordered_qty'], 3) ?></td>
                                <td class="ordered text-right"><?= number_format($it['ordered_qty'], 3) ?></td>
                                
                                <td style="min-width: 180px;">
                                    <select name="justifications[<?= $it['product_id'] ?>]" class="form-control form-control-sm justification-select" style="display:none;">
                                        <option value="">Seleccione...</option>
                                        <option>Sin justif. proveedor</option>
                                        <option>Daño en transporte</option>
                                        <option>Pérdida en almacén</option>
                                        <option>Error de entrega</option>
                                    </select>
                                </td>
                                
                                <td class="text-right">1</td>
                                <td class="row-subtotal text-right font-weight-bold">0.0000</td>
                                <td class="text-right text-muted">0.00%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Resumen Totales -->
            <div class="row justify-content-end mb-4">
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                                <span class="font-weight-bold">Subtotal:</span>
                                <span>C$ <span id="rtSub">0.00</span></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                                <span class="font-weight-bold">Descuento:</span>
                                <span>C$ 0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                                <span class="font-weight-bold">Impuesto (IVA 15%):</span>
                                <span>C$ <span id="rtTax">0.00</span></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="font-weight-bold text-primary" style="font-size: 1.2rem;">TOTAL:</span>
                                <span class="font-weight-bold text-primary" style="font-size: 1.2rem;">C$ <span id="rtTotal">0.00</span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Campos ocultos de factura -->
            <div id="hiddenFacturaFields">
              <?php if ($invoiceSaved): ?>
                <input type="hidden" name="invoice_subtotal" value="<?= $savedInvoiceSub ?>">
                <input type="hidden" name="invoice_tax" value="<?= $savedInvoiceTax ?>">
              <?php endif; ?>
            </div>
            <div id="hiddenCreds"></div>
        </form>
    </div>
</section>

<!-- Modal Facturas -->
<div id="facturaModal" class="modal fade" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Factura Física vs Sistema</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="row">
          <!-- Columna Sistema (readonly) -->
          <div class="col-md-6">
            <h6 class="text-center mb-3"><strong>Totales del Sistema</strong></h6>
            <div class="form-group">
              <label>Subtotal Sistema</label>
              <input type="text" class="form-control" id="modalSysSub" readonly
                style="background:#e9ecef;">
            </div>
            <div class="form-group">
              <label>IVA Sistema (15%)</label>
              <input type="text" class="form-control" id="modalSysTax" readonly
                style="background:#e9ecef;">
            </div>
            <div class="form-group">
              <label>Total Sistema</label>
              <input type="text" class="form-control" id="modalSysTotal" readonly
                style="background:#e9ecef; font-weight:bold;">
            </div>
          </div>

          <!-- Columna Factura Física (editable) -->
          <div class="col-md-6">
            <h6 class="text-center mb-3"><strong>Factura Física</strong></h6>
            <div class="form-group">
              <label>Subtotal Factura *</label>
              <input type="number" step="0.01" min="0" class="form-control" id="modalInvSub"
                value="<?= $invoiceSaved ? number_format($savedInvoiceSub, 2, '.', '') : '' ?>">
            </div>
            <div class="form-group">
              <label>IVA Factura *</label>
              <input type="number" step="0.01" min="0" class="form-control" id="modalInvTax"
                value="<?= $invoiceSaved ? number_format($savedInvoiceTax, 2, '.', '') : '' ?>">
            </div>
            <div class="form-group">
              <label>Total Factura</label>
              <input type="text" class="form-control" id="modalInvTotal" readonly
                style="background:#e9ecef; font-weight:bold;"
                value="<?= $invoiceSaved ? number_format($savedInvoiceSub + $savedInvoiceTax, 2, '.', '') : '' ?>">
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="btnSaveInvoice">Guardar Factura</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<style>
.transition-bg { transition: background-color 0.3s; }
.bg-edited { background-color: #fff3cd !important; border-color: #ffeeba !important; }
</style>

<script>
$(document).ready(function() {
    const IVA_RATE = 0.15;
    const ORDER_ID = <?= json_encode($order['id']) ?>;
    let invoiceSaved = <?= json_encode($invoiceSaved) ?>;
    let systemCalculated = <?= json_encode($invoiceSaved) ?>;
    
    const $badge = $('#boletaEstadoBadge');
    
    function setEstado(estado, clase) {
        $badge.text('ESTADO: ' + estado).removeClass().addClass('badge p-2 ' + clase);
    }

    // Keyboard global listener para F5, F6 y F9
    $(document).on('keydown', function(e) {
        if (e.key === 'F5' || e.key === 'F6' || e.key === 'F9') {
            e.preventDefault();
        }
        
        switch(e.key) {
            case 'F5': 
                $('#btnCalcSystem').click(); 
                break;
            case 'F6': 
                if(!$('#btnConfirmF6').is(':disabled')) {
                    $('#btnConfirmF6').click();
                } else if(!invoiceSaved) {
                    Swal.fire('Atención', 'Calcule (F5) y guarde Detalles de Factura Física (F9) primero.', 'warning');
                }
                break;
            case 'F9':
                if(!$('#btnFacturas').is(':disabled')) {
                    $('#btnFacturas').click();
                } else if (!systemCalculated) {
                    Swal.fire('Bloqueado', 'Calcule totales del sistema (F5) primero.', 'warning');
                }
                break;
        }
    });

    // 1. Calcular Sistema (F5)
    $('#btnCalcSystem').click(function() {
        let subtotal = 0;
        $('#itemsTable tbody tr').each(function() {
            let cost = parseFloat($(this).data('cost')) || 0;
            let qty = parseFloat($(this).find('.received-qty').val()) || 0;
            let lineTot = cost * qty;
            
            $(this).find('.row-subtotal').text(lineTot.toFixed(4));
            subtotal += lineTot;
            
            let ordString = $(this).find('.ordered').text().replace(/,/g, '');
            let ord = parseFloat(ordString) || 0;
            if (qty < ord) {
                $(this).find('.justification-select').show();
            } else {
                $(this).find('.justification-select').hide().val('');
            }
            
            // quitar marca de editado y actualizar colores
            $(this).find('.received-qty').removeClass('bg-edited');
            $(this).removeClass('table-warning table-info');
            if (qty < ord) {
                $(this).addClass('table-warning');
            } else if (qty > ord) {
                $(this).addClass('table-info');
            }
        });
        
        let tax = subtotal * IVA_RATE;
        let tot = subtotal + tax;
        
        $('#rtSub').text(subtotal.toFixed(4));
        $('#rtTax').text(tax.toFixed(4));
        $('#rtTotal').text(tot.toFixed(4));
        
        systemCalculated = true;
        
        // Populate modal data
        $('#modalSysSub').val(subtotal.toFixed(4));
        $('#modalSysTax').val(tax.toFixed(4));
        $('#modalSysTotal').val(tot.toFixed(4));
        
        
        $('#btnFacturas').prop('disabled', false);
        $('#btnConfirmF6').prop('disabled', false); // Activar Aplicar Entrada también al calcular
        setEstado('CALCULADO', 'badge-info');
        Swal.fire({ icon: 'success', title: 'Sistema Calculado', text: 'Los totales del sistema reflejan la tabla.', timer: 1500, showConfirmButton: false });
    });

    // Ejecutar calculo silente en init, sin popup ni estilo
    if (systemCalculated) {
        let subtotal=0; $('#itemsTable tbody tr').each(function(){ let c=parseFloat($(this).data('cost'))||0, q=parseFloat($(this).find('.received-qty').val())||0; let l=c*q; $(this).find('.row-subtotal').text(l.toFixed(4)); subtotal+=l; });
        let tax=subtotal*IVA_RATE;
        $('#rtSub').text(subtotal.toFixed(4)); $('#rtTax').text(tax.toFixed(4)); $('#rtTotal').text((subtotal+tax).toFixed(4));
        $('#modalSysSub').val(subtotal.toFixed(4)); $('#modalSysTax').val(tax.toFixed(4)); $('#modalSysTotal').val((subtotal+tax).toFixed(4));
        setEstado('CALCULADO', 'badge-info');
    }
    // Always compute stats on page load
    // (defined after this block, works because JS hoisting of function declarations)
    setTimeout(updateStats, 100);

    // 2. Evento Editar Input — calcula en tiempo real C.Bruto y totales
    $('.received-qty').on('input', function() {
        $(this).addClass('bg-edited');
        systemCalculated = false;
        $('#btnFacturas').prop('disabled', true);
        $('#btnConfirmF6').prop('disabled', true);
        setEstado('MODIFICADO (Require Cálculo)', 'badge-warning');
        
        const tr = $(this).closest('tr');
        const cost = parseFloat(tr.data('cost')) || 0;
        const qty  = parseFloat($(this).val()) || 0;
        
        // Actualizar C.Bruto de la fila
        const lineTot = cost * qty;
        tr.find('.row-subtotal').text(lineTot.toFixed(4));
        
        // Actualizar colores de fila
        const ordString = tr.find('.ordered').text().replace(/,/g, '');
        const ord = parseFloat(ordString) || 0;
        tr.removeClass('table-warning table-info');
        if (qty < ord) {
            tr.addClass('table-warning');
        } else if (qty > ord) {
            tr.addClass('table-info');
        }
        
        // Recalcular totales del resumen en tiempo real
        let subtotal = 0;
        $('#itemsTable tbody tr').each(function() {
            subtotal += parseFloat($(this).find('.row-subtotal').text()) || 0;
        });
        const tax = subtotal * IVA_RATE;
        const tot = subtotal + tax;
        $('#rtSub').text(subtotal.toFixed(4));
        $('#rtTax').text(tax.toFixed(4));
        $('#rtTotal').text(tot.toFixed(4));
        
        // Actualizar barra de estadísticas
        updateStats();
    });

    function updateStats() {
        let ok = 0, missing = 0, extra = 0;
        $('#itemsTable tbody tr').each(function() {
            const ordString = $(this).find('.ordered').text().replace(/,/g, '');
            const ord = parseFloat(ordString) || 0;
            const qty = parseFloat($(this).find('.received-qty').val()) || 0;
            if (qty < ord) missing++;
            else if (qty > ord) extra++;
            else ok++;
        });
        $('#statOk').text(ok);
        $('#statMissing').text(missing);
        $('#statExtra').text(extra);
    }

    // Auto-select text on focus to avoid manual deletion
    $('.received-qty, #modalInvSub, #modalInvTax').on('focus', function() {
        $(this).select();
    });

    // Arrow key navigation between rows + Enter triggers Calculate
    $('.received-qty').on('keydown', function(e) {
        const inputs = $('.received-qty');
        const idx = inputs.index(this);
        if (e.key === 'Enter' || e.key === 'ArrowDown') {
            e.preventDefault();
            if (e.key === 'Enter') { $('#btnCalcSystem').click(); return; }
            if (idx < inputs.length - 1) inputs.eq(idx + 1).focus();
        }
        if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (idx > 0) inputs.eq(idx - 1).focus();
        }
    });

    // 3. Abrir Modal Facturas (F9)
    $('#btnFacturas').click(function() {
        if(!systemCalculated) {
             Swal.fire('Atención', 'Debe calcular primero los totales del sistema.', 'warning');
             return;
        }
        $('#facturaModal').modal('show');
        setTimeout(() => $('#modalInvSub').focus(), 500);
    });
    
    // Suma en vivo dentro del Modal F9 y Enter key
    $('#modalInvSub, #modalInvTax').on('input', function() {
        let s = parseFloat($('#modalInvSub').val()) || 0;
        let t = parseFloat($('#modalInvTax').val()) || 0;
        $('#modalInvTotal').val((s + t).toFixed(4));
    }).on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $('#btnSaveInvoice').click();
        }
    });

    // Guardar Factura Fisica desde el Modal F9
    $('#btnSaveInvoice').click(function() {
        const sub = $('#modalInvSub').val();
        const tax = $('#modalInvTax').val();
        if (!sub || !tax || parseFloat(sub) <= 0 || parseFloat(tax) < 0) {
            return Swal.fire('Atención', 'Debe ingresar montos lógicos de la Factura.', 'warning');
        }
        
        $.post('/soleipharmav2/order/saveInvoice?id=' + ORDER_ID, {
            invoice_subtotal: sub,
            invoice_tax: tax
        }, function(resp) {
            if (resp.success) {
                invoiceSaved = true;
                if (!$('input[name=invoice_subtotal]').length) {
                     $('#hiddenFacturaFields').append(
                        '<input type="hidden" name="invoice_subtotal" value="' + sub + '">' +
                        '<input type="hidden" name="invoice_tax" value="' + tax + '">'
                    );
                } else {
                    $('input[name=invoice_subtotal]').val(sub);
                    $('input[name=invoice_tax]').val(tax);
                }
                
                $('#btnConfirmF6').prop('disabled', false);
                $('#facturaModal').modal('hide');
                setEstado('LISTO PARA APLICAR', 'badge-primary');
                Swal.fire('Guardado', 'Datos de factura conciliados.', 'success');
            } else {
                Swal.fire('Error', resp.message, 'error');
            }
        }, 'json').fail(function() { Swal.fire('Error', 'Fallo al conectar.', 'error'); });
    });

    // 4. Aplicar (F6) - Solicita credenciales
    $('#btnConfirmF6').click(function() {
        if(!invoiceSaved) {
            return Swal.fire('Alto', 'Falta cargar Montos de Factura Física (F9).', 'warning');
        }
        
        // Validación frontend de justificaciones
        let faltanJustificaciones = false;
        $('#itemsTable tbody tr').each(function() {
            let ordString = $(this).find('.ordered').text().replace(/,/g, '');
            let ord = parseFloat(ordString) || 0;
            let qty = parseFloat($(this).find('.received-qty').val()) || 0;
            if (qty < ord) {
                let just = $(this).find('.justification-select').val();
                if (!just || just.trim() === '') {
                    faltanJustificaciones = true;
                }
            }
        });
        
        if (faltanJustificaciones) {
            return Swal.fire('Atención', 'Debe seleccionar una justificación para cada producto con faltante.', 'warning');
        }
        
        if (window.ActionModal) {
            window.ActionModal.show({
                title: 'Aplicar Entrada',
                description: 'La boleta quedará aplicada permanentemente. Ingrese sus credenciales de administrador.',
                fields: [
                  { id: 'modal-input-username', type: 'text', placeholder: 'Usuario Admin' },
                  { id: 'modal-input-password', type: 'password', placeholder: 'Contraseña Admin' }
                ],
                onConfirm: function(data) {
                    const u = data['modal-input-username'] || '';
                    const p = data['modal-input-password'] || '';
                    if (!u || !p) {
                        return window.ActionModal.showError('Complete sus credenciales obligatorias');
                    }
                    window.ActionModal.hide();
                    
                    if (!$('#hiddenCreds').children().length) {
                        $('#hiddenCreds').append(
                            '<input type="hidden" name="confirm_username" value="' + u + '">' +
                            '<input type="hidden" name="confirm_password" value="' + p + '">'
                        );
                    } else {
                        $('input[name=confirm_username]').val(u);
                        $('input[name=confirm_password]').val(p);
                    }
                    
                    setEstado('PROCESANDO...', 'badge-default bg-dark text-white');
                    
                    Swal.fire({
                        title: 'Aplicando boleta y generando PDF...',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });
                    
                    const form = $('#goodsEntryForm')[0];
                    fetch(form.action, { method: 'POST', body: new FormData(form) })
                    .then(r => r.json()).then(async (json) => {
                        window.ActionModal.hide();
                        
                        if (json.success) {
                            setEstado('APLICADO', 'badge-success');
                            
                            let extraHtml = '';
                            if (!json.has_debit_note) {
                                extraHtml += '<br><br><i class="fas fa-info-circle text-info"></i> <b>Costos Cuadrados:</b> No se generó Nota de Débito por Costos.';
                            }
                            if (!json.has_qty_debit_note) {
                                extraHtml += '<br><br><i class="fas fa-info-circle text-info"></i> <b>Cantidades Completas:</b> No se generó diferencia en las cantidades.';
                            }
                            
                            await Swal.fire({
                                icon: 'success', 
                                title: 'Entrada Aplicada', 
                                html: json.message + extraHtml, 
                                showConfirmButton: true,
                                confirmButtonText: 'Finalizar',
                                allowOutsideClick: false
                            });
                            
                            if (json.has_debit_note || json.has_qty_debit_note) {
                                window.open('/soleipharmav2/order/debitNote?id=' + json.entry_id, '_blank');
                            }
                            window.location = '/soleipharmav2/order/entrySummary?id=' + (json.order_id || ORDER_ID);
                            
                        } else {
                            setEstado('CALCULADO', 'badge-info');
                            Swal.fire('Aviso', json.message, 'error');
                        }
                    }).catch(e => {
                        setEstado('ERROR', 'badge-danger');
                        Swal.fire('Error de Red', 'Fallo de conectividad.', 'error');
                    });
                }
            });
        }
    });

});
</script>