<?php
// views/admin/orders_edit.php
// Variables: $order, $orderItems, $orderTotal
$isPending = ($order['status'] === 'pending');
$statusLabel = ucfirst($order['status'] ?? '');
?>
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1> Pedido #<?= htmlspecialchars($order['id'] ?? '') ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/admin/index">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/admin/index">Dashboard</a></li>
                    <li class="breadcrumb-item active"> Pedido #<?= htmlspecialchars($order['id'] ?? '') ?></li>
                </ol>
            </div>
        </div>
    </div>
</section>
<section class="content">
    <div class="container-fluid">
        <!-- Tarjeta de proveedor -->
        <div class="card card-outline card-<?= $isPending ? 'warning' : 'secondary' ?> mb-3">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-truck"></i> Proveedor del Pedido</h3>
            </div>
            <div class="card-body">
                <?php if ($isPending): ?>
                <div class="row align-items-end">
                    <div class="col-md-6">
                        <select id="supplierSelectEdit" class="form-control">
                            <option value="">— Sin proveedor —</option>
                            <?php foreach ($suppliers as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= ($order['supplier_id'] ?? null) == $s['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button id="btnSaveSupplier" class="btn btn-warning btn-sm">
                            <i class="fas fa-save"></i> Guardar Proveedor
                        </button>
                    </div>
                    <div class="col-md-3 text-muted">
                        <small>Opcional: selecciona el proveedor al que pertenece este pedido.</small>
                    </div>
                </div>
                <?php else: ?>
                <span class="font-weight-bold">
                    <i class="fas fa-truck mr-1"></i>
                    <?= htmlspecialchars($order['supplier_name'] ?? 'Sin proveedor asignado') ?>
                </span>
                <?php endif; ?>
            </div>
        </div>

                <div class="card">
            <div class="card-header">
                <?php
                    $statusMap = ['pending' => ['label' => 'Pendiente', 'badge' => 'warning'], 'applied' => ['label' => 'Aplicado', 'badge' => 'primary'], 'received' => ['label' => 'Recibido', 'badge' => 'success']];
                    $statInfo = $statusMap[$order['status'] ?? 'pending'] ?? ['label' => ucfirst($order['status'] ?? ''), 'badge' => 'secondary'];
                ?>
                <span class="badge badge-<?= $statInfo['badge'] ?> p-1">
                    <?= $statInfo['label'] ?>
                </span>
                <span class="ml-2">Creado por: <?= htmlspecialchars($order['admin_name'] ?? '') ?></span>
                <span class="ml-2">Fecha: <?= htmlspecialchars($order['order_date'] ?? '') ?></span>
            </div>
            <div class="card-body table-responsive p-0">
                <?php if ($isPending): ?>
                    <form id="editOrderForm"
                        action="<?= APP_BASE ?>/order/update?id=<?= $order['id'] ?>" method="post">
                <?php endif; ?>

                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Producto</th>
                            <th>Costo Unit.</th>
                            <th>Cantidad</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orderItems as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['sku'] ?? $item['product_id']) ?></td>
                                <td><?= htmlspecialchars($item['name'] ?? '') ?></td>
                                <td>C$<?= number_format($item['cost'] ?? 0, 2) ?></td>
                                <td>
                                    <?php if ($isPending): ?>
                                        <input type="number" name="quantities[<?= $item['product_id'] ?>]"
                                            value="<?= $item['quantity'] ?>" min="0" class="form-control qty-input"
                                            data-cost="<?= $item['cost'] ?? 0 ?>">
                                    <?php else: ?>
                                        <?= $item['quantity'] ?>
                                    <?php endif; ?>
                                </td>
                                <td class="line-sub">C$<?= number_format(($item['cost'] ?? 0) * $item['quantity'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-right" style="border-top: 2px solid #dee2e6;"><strong>Subtotal:</strong></td>
                            <td style="border-top: 2px solid #dee2e6;"><strong>C$<span id="orderSubtotal"><?= number_format($orderTotal, 2) ?></span></strong></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-right" style="border-top: none;"><strong>IVA (15%):</strong></td>
                            <td style="border-top: none;"><strong>C$<span id="orderTax"><?= number_format($orderTotal * 0.15, 2) ?></span></strong></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-right" style="border-top: none; padding-top: 15px;">
                                <h4 style="margin: 0; color: #6f42c1;">Total Pedido:</h4>
                            </td>
                            <td style="border-top: none; padding-top: 15px;">
                                <h4 style="margin: 0; color: #6f42c1;">C$<span id="orderTotalFinal"><?= number_format($orderTotal * 1.15, 2) ?></span></h4>
                            </td>
                        </tr>
                    </tfoot>
                </table>

                <?php if ($isPending): ?>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Actualizar Pedido</button>
                        <a href="<?= APP_BASE ?>/order/index" class="btn btn-secondary ml-2">Volver</a>
                    </div>
                    </form>
                <?php else: ?>
                    <div class="card-footer">
                        <a href="<?= APP_BASE ?>/order/index" class="btn btn-secondary">Volver a Pedidos</a>
                        <?php if ($order['status'] === 'received'): ?>
                            <a href="<?= APP_BASE ?>/order/goodsEntryReport?id=<?= $order['id'] ?>"
                                class="btn btn-info ml-2">Ver Boleta de Recepción</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php if ($isPending): ?>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
    $(function () {
        // Recalcular subtotales y total con IVA
        function recalc() {
            let subtotal = 0;
            $('tbody tr').each(function () {
                const qty = parseInt($(this).find('.qty-input').val()) || 0;
                const cost = parseFloat($(this).find('.qty-input').data('cost')) || 0;
                const sub = qty * cost;
                $(this).find('.line-sub').text('C$' + sub.toFixed(2));
                subtotal += sub;
            });
            
            const tax = subtotal * 0.15;
            const totalFinal = subtotal + tax;

            $('#orderSubtotal').text(subtotal.toFixed(2));
            $('#orderTax').text(tax.toFixed(2));
            $('#orderTotalFinal').text(totalFinal.toFixed(2));
        }
        $('.qty-input').on('input', recalc);

        $("#editOrderForm").on("submit", function (e) {
            e.preventDefault();
            $.ajax({
                url: $(this).attr("action"),
                type: "POST",
                data: $(this).serialize(),
                dataType: "json",
                success: function (response) {
                    if (response.success) {
                        Swal.fire({ icon: 'success', title: 'Éxito', text: response.message })
                            .then(() => { window.location.href = "<?= APP_BASE ?>/order/index"; });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: response.message });
                    }
                },
                error: function () {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Ocurrió un error en el servidor.' });
                }
            });
        });

        // Guardar proveedor
        $('#btnSaveSupplier').click(function() {
            const supplierId = $('#supplierSelectEdit').val();
            $.post('<?= APP_BASE ?>/order/updateSupplier', {
                order_id: <?= $order['id'] ?>,
                supplier_id: supplierId
            }, function(r) {
                if (r.success) {
                    Swal.fire({ icon: 'success', title: 'Proveedor guardado', timer: 1200, showConfirmButton: false });
                } else {
                    Swal.fire('Error', r.message, 'error');
                }
            }, 'json');
        });
    });
</script>
<?php endif; ?>