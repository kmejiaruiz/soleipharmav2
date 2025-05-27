<!-- views/admin/orders_create.php -->
<section class="content-header">
    <div class="container-fluid">
        <h1>Realizar Pedido de Productos</h1>
    </div>
</section>
<section class="content">
    <div class="container-fluid">
        <form id="orderCreateForm" action="index.php?controller=order&action=store" method="POST">
            <div class="card">
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover text-nowrap" id="orderTable">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Producto</th>
                                <th>Costo Unitario</th>
                                <th>Cantidad</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['sku']) ?></td>
                                    <td><?= htmlspecialchars($p['name']) ?></td>
                                    <td>
                                        $<span class="unit-cost"><?= number_format($p['cost'], 2) ?></span>
                                        <input type="hidden" name="unit_costs[<?= $p['id'] ?>]" value="<?= $p['cost'] ?>">
                                    </td>
                                    <td>
                                        <input type="number" name="quantities[<?= $p['id'] ?>]" value="0" min="0"
                                            class="form-control quantity-input" data-product-id="<?= $p['id'] ?>">
                                    </td>
                                    <td>$<span class="line-subtotal" data-product-id="<?= $p['id'] ?>">0.00</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer text-right">
                    <h4>Total Orden: $<span id="orderTotal">0.00</span></h4>
                    <button type="submit" class="btn btn-success">Confirmar Pedido</button>
                </div>
            </div>
        </form>
    </div>
</section>

<!-- SweetAlert2 y jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function () {
        // Cálculos en tiempo real
        function recalcLine(id) {
            var qty = parseFloat($('input[name="quantities[' + id + ']"]').val()) || 0;
            var cost = parseFloat($('input[name="unit_costs[' + id + ']"]').val()) || 0;
            var sub = (qty * cost).toFixed(2);
            $('.line-subtotal[data-product-id="' + id + '"]').text(sub);
        }
        function recalcTotal() {
            var total = 0;
            $('.line-subtotal').each(function () { total += parseFloat($(this).text()) || 0; });
            $('#orderTotal').text(total.toFixed(2));
        }
        $('.quantity-input').on('input', function () {
            var id = $(this).data('product-id');
            recalcLine(id);
            recalcTotal();
        });

        // Envío vía AJAX con SweetAlert feedback
        $('#orderCreateForm').on('submit', function (e) {
            e.preventDefault();
            var data = $(this).serialize();
            $.post($(this).attr('action'), data, function (response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Pedido Creado',
                        text: response.message,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = 'index.php?controller=order&action=index';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                }
            }, 'json').fail(function () {
                Swal.fire({ icon: 'error', title: 'Error interno', text: 'No se pudo procesar el pedido.' });
            });
        });
    });
</script>