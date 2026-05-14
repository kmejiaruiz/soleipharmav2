<!-- views/admin/orders_create.php -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Realizar Pedido de Productos</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/admin/index">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/order/index">Pedidos</a></li>
                    <li class="breadcrumb-item active">Nuevo Pedido</li>
                </ol>
            </div>
        </div>
    </div>
</section>
<section class="content">
    <div class="container-fluid">

        <!-- Paso 1: Selector de Proveedor -->
        <div class="card card-outline card-primary mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-truck"></i> Paso 1 — Seleccionar Proveedor</h3>
            </div>
            <div class="card-body">
                <div class="row align-items-end">
                    <div class="col-md-6">
                        <div class="form-group mb-0">
                            <label>Proveedor <span class="text-danger">*</span></label>
                            <select id="supplierSelect" class="form-control">
                                <option value="">— Seleccione un proveedor —</option>
                                <?php foreach ($suppliers as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button id="btnLoadProducts" class="btn btn-primary btn-block">
                            <i class="fas fa-download"></i> Cargar Catálogo
                        </button>
                    </div>
                    <div class="col-md-3">
                        <a href="<?= APP_BASE ?>/supplier/create" class="btn btn-outline-secondary btn-block" target="_blank">
                            <i class="fas fa-plus"></i> Nuevo Proveedor
                        </a>
                    </div>
                </div>
                <div id="supplierInfo" class="mt-3" style="display:none">
                    <span class="badge badge-success p-2">
                        <i class="fas fa-check-circle"></i> Proveedor seleccionado: <strong id="selectedSupplierName"></strong>
                    </span>
                </div>
            </div>
        </div>

        <!-- Paso 2: Tabla de Productos del proveedor -->
        <div id="step2" style="display:none">
            <form id="orderCreateForm" action="<?= APP_BASE ?>/order/store" method="POST">
                <input type="hidden" name="supplier_id" id="hiddenSupplierId">

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title"><i class="fas fa-boxes"></i> Paso 2 — Productos del Catálogo</h3>
                        <div>
                            <input type="text" id="productFilter" class="form-control form-control-sm" placeholder="Filtrar productos..." style="min-width:200px">
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered table-hover table-sm" id="orderTable">
                            <thead class="thead-dark">
                                <tr>
                                    <th>SKU</th>
                                    <th>Producto</th>
                                    <th class="text-right">Precio Proveedor</th>
                                    <th class="text-right" style="min-width:130px">Cantidad</th>
                                    <th class="text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody id="productsBody">
                                <tr><td colspan="5" class="text-center text-muted">Carga el catálogo para ver los productos.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <small class="text-muted">Solo se incluyen productos con cantidad &gt; 0.</small>
                            </div>
                            <div class="col-md-4 text-right">
                                <h6 class="mb-1">Subtotal: C$<span id="orderSubtotal">0.00</span></h6>
                                <h6 class="mb-1">IVA (15%): C$<span id="orderTax">0.00</span></h6>
                                <h4 class="text-primary mb-2">Total: C$<span id="orderTotal">0.00</span></h4>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check-circle"></i> Confirmar Pedido
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

    </div>
</section>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {

    // Cargar catálogo del proveedor
    $('#btnLoadProducts').click(function() {
        const supplierId = $('#supplierSelect').val();
        const supplierName = $('#supplierSelect option:selected').text();
        if (!supplierId) {
            return Swal.fire('Atención', 'Selecciona un proveedor primero.', 'warning');
        }

        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Cargando...');

        $.getJSON('<?= APP_BASE ?>/supplier/products?id=' + supplierId, function(products) {
            $('#productsBody').empty();
            if (!products.length) {
                $('#productsBody').html('<tr><td colspan="5" class="text-center text-muted">Este proveedor no tiene productos en su catálogo. <a href="<?= APP_BASE ?>/supplier/catalog?id=' + supplierId + '" target="_blank">Agregar productos</a></td></tr>');
                $('#step2').slideDown();
                return;
            }

            products.forEach(function(p) {
                const price = parseFloat(p.supplier_price) > 0 ? p.supplier_price : p.cost;
                const row = `<tr data-product-id="${p.id}" data-price="${price}">
                    <td>${p.sku || '—'}</td>
                    <td>${p.name}</td>
                    <td class="text-right">C$${parseFloat(price).toFixed(2)}
                        <input type="hidden" name="unit_costs[${p.id}]" value="${price}">
                    </td>
                    <td class="text-right">
                        <input type="number" name="quantities[${p.id}]" value="0" min="0"
                            class="form-control form-control-sm text-right quantity-input"
                            data-product-id="${p.id}" data-price="${price}">
                    </td>
                    <td class="text-right">C$<span class="line-subtotal" data-product-id="${p.id}">0.00</span></td>
                </tr>`;
                $('#productsBody').append(row);
            });

            $('#hiddenSupplierId').val(supplierId);
            $('#selectedSupplierName').text(supplierName);
            $('#supplierInfo').show();
            $('#step2').slideDown();
            recalcTotal();
        }).fail(function() {
            Swal.fire('Error', 'No se pudo cargar el catálogo.', 'error');
        }).always(function() {
            $('#btnLoadProducts').prop('disabled', false).html('<i class="fas fa-download"></i> Cargar Catálogo');
        });
    });

    // Filtro de productos
    $('#productFilter').on('input', function() {
        const q = $(this).val().toLowerCase();
        $('#productsBody tr').each(function() {
            $(this).toggle($(this).text().toLowerCase().includes(q));
        });
    });

    // Cálculos en tiempo real
    function recalcTotal() {
        let subtotal = 0;
        $('.line-subtotal').each(function() { subtotal += parseFloat($(this).text()) || 0; });
        const tax = subtotal * 0.15;
        $('#orderSubtotal').text(subtotal.toFixed(2));
        $('#orderTax').text(tax.toFixed(2));
        $('#orderTotal').text((subtotal + tax).toFixed(2));
    }

    $(document).on('input', '.quantity-input', function() {
        const id    = $(this).data('product-id');
        const price = parseFloat($(this).data('price')) || 0;
        const qty   = parseFloat($(this).val()) || 0;
        const sub   = (price * qty).toFixed(2);
        $(`.line-subtotal[data-product-id="${id}"]`).text(sub);
        recalcTotal();
    });

    // Submit con AJAX
    $('#orderCreateForm').on('submit', function(e) {
        e.preventDefault();
        $.post($(this).attr('action'), $(this).serialize(), function(r) {
            if (r.success) {
                Swal.fire({ icon: 'success', title: 'Pedido Creado', text: r.message })
                    .then(() => window.location.href = '<?= APP_BASE ?>/order/index');
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: r.message });
            }
        }, 'json').fail(function() {
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo procesar el pedido.' });
        });
    });
});
</script>