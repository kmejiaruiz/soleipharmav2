<?php // views/admin/supplier_catalog.php ?>
<section class="content-header">
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1><i class="fas fa-list"></i> Catálogo — <?= htmlspecialchars($supplier['name']) ?></h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="/soleipharmav2/admin/index">Inicio</a></li>
                <li class="breadcrumb-item"><a href="/soleipharmav2/supplier/index">Proveedores</a></li>
                <li class="breadcrumb-item active">Catálogo</li>
            </ol>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="row">

            <!-- Panel izquierdo: productos del catálogo -->
            <div class="col-md-8">
                <div class="card card-outline card-info">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title"><i class="fas fa-boxes"></i> Productos en Catálogo</h3>
                        <span class="badge badge-info p-2" id="catalogCount"><?= count($catalog) ?> productos</span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered table-sm table-hover mb-0" id="catalogTable">
                            <thead class="thead-dark">
                                <tr>
                                    <th>SKU</th>
                                    <th>Producto</th>
                                    <th class="text-right">Costo Sistema</th>
                                    <th class="text-right" style="min-width:140px">Precio Proveedor</th>
                                    <th class="text-center">Quitar</th>
                                </tr>
                            </thead>
                            <tbody id="catalogBody">
                                <?php foreach ($catalog as $row): ?>
                                <tr id="cat-row-<?= $row['id'] ?>">
                                    <td><?= htmlspecialchars($row['sku'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                    <td class="text-right">C$<?= number_format($row['cost'], 2) ?></td>
                                    <td class="text-right">
                                        <input type="number" step="0.0001" min="0"
                                            class="form-control form-control-sm text-right price-input"
                                            data-product-id="<?= $row['id'] ?>"
                                            value="<?= number_format($row['supplier_price'], 4, '.', '') ?>"
                                            <?= $isSuperAdmin ? '' : 'readonly' ?>>  
                                    </td>
                                    <td class="text-center">
                                        <?php if ($isSuperAdmin): ?>
                                        <button class="btn btn-danger btn-sm btn-remove"
                                            data-product-id="<?= $row['id'] ?>">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($catalog)): ?>
                                <tr id="emptyRow">
                                    <td colspan="5" class="text-center text-muted py-3">
                                        <i class="fas fa-inbox"></i> Sin productos. Agrega desde el panel derecho.
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Panel derecho: agregar productos (solo superadmin) -->
            <?php if ($isSuperAdmin): ?>
            <div class="col-md-4">
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-plus-circle"></i> Agregar Producto</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Buscar Producto</label>
                            <input type="text" id="productSearch" class="form-control mb-2" placeholder="Nombre o SKU...">
                            <select id="productSelect" class="form-control" size="8" style="height:auto">
                                <?php foreach ($allProducts as $p): ?>
                                <option value="<?= $p['id'] ?>" data-cost="<?= $p['cost'] ?>"
                                    data-sku="<?= htmlspecialchars($p['sku'] ?? '') ?>"
                                    data-name="<?= htmlspecialchars($p['name']) ?>">
                                    [<?= htmlspecialchars($p['sku'] ?? 'S/C') ?>] <?= htmlspecialchars($p['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Precio del Proveedor (C$)</label>
                            <input type="number" step="0.0001" min="0" id="newPrice" class="form-control" placeholder="0.0000">
                        </div>
                        <button class="btn btn-success btn-block" id="btnAddProduct">
                            <i class="fas fa-plus"></i> Agregar al Catálogo
                        </button>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-body text-center">
                        <a href="/soleipharmav2/supplier/edit?id=<?= $supplier['id'] ?>" class="btn btn-warning btn-sm mr-1">
                            <i class="fas fa-edit"></i> Editar Proveedor
                        </a>
                        <a href="/soleipharmav2/supplier/index" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; // isSuperAdmin right panel ?>
        </div>
    </div>
</section>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
$(document).ready(function() {
    const SUPPLIER_ID = <?= $supplier['id'] ?>;

    // Filtro de búsqueda del select
    $('#productSearch').on('input', function() {
        const q = $(this).val().toLowerCase();
        $('#productSelect option').each(function() {
            const txt = $(this).text().toLowerCase();
            $(this).toggle(txt.includes(q));
        });
    });

    // Autocompletar precio cuando seleccionan un producto
    $('#productSelect').on('change', function() {
        const cost = $(this).find(':selected').data('cost') || 0;
        $('#newPrice').val(parseFloat(cost).toFixed(4));
    });

    // Agregar producto al catálogo
    $('#btnAddProduct').click(function() {
        const productId = $('#productSelect').val();
        const price     = $('#newPrice').val();
        const opt       = $('#productSelect').find(':selected');

        if (!productId) { return Swal.fire('Atención', 'Selecciona un producto.', 'warning'); }

        $.post('/soleipharmav2/supplier/addProduct', {
            supplier_id: SUPPLIER_ID,
            product_id: productId,
            supplier_price: price
        }, function(r) {
            if (r.success) {
                const sku  = opt.data('sku');
                const name = opt.data('name');
                const cost = opt.data('cost');

                // Quitar fila vacía si existe
                $('#emptyRow').remove();

                // Agregar fila si no existe, si existe actualizar precio
                if ($('#cat-row-' + productId).length) {
                    $('#cat-row-' + productId + ' .price-input').val(parseFloat(price).toFixed(4));
                } else {
                    const row = `<tr id="cat-row-${productId}">
                        <td>${sku}</td>
                        <td>${name}</td>
                        <td class="text-right">C$${parseFloat(cost).toFixed(2)}</td>
                        <td class="text-right">
                            <input type="number" step="0.0001" min="0"
                                class="form-control form-control-sm text-right price-input"
                                data-product-id="${productId}"
                                value="${parseFloat(price).toFixed(4)}">
                        </td>
                        <td class="text-center">
                            <button class="btn btn-danger btn-sm btn-remove" data-product-id="${productId}">
                                <i class="fas fa-times"></i>
                            </button>
                        </td>
                    </tr>`;
                    $('#catalogBody').append(row);
                    $('#catalogCount').text($('#catalogBody tr').length + ' productos');
                }
                Swal.fire({ icon: 'success', title: 'Agregado', timer: 1000, showConfirmButton: false });
            } else {
                Swal.fire('Error', r.message, 'error');
            }
        }, 'json');
    });

    // Quitar producto del catálogo
    $(document).on('click', '.btn-remove', function() {
        const productId = $(this).data('product-id');
        const row = $(this).closest('tr');

        Swal.fire({
            title: '¿Quitar del catálogo?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, quitar',
            cancelButtonText: 'Cancelar'
        }).then(result => {
            if (!result.isConfirmed) return;
            $.post('/soleipharmav2/supplier/removeProduct', {
                supplier_id: SUPPLIER_ID,
                product_id: productId
            }, function(r) {
                if (r.success) {
                    row.remove();
                    $('#catalogCount').text($('#catalogBody tr').length + ' productos');
                    if ($('#catalogBody tr').length === 0) {
                        $('#catalogBody').append('<tr id="emptyRow"><td colspan="5" class="text-center text-muted py-3"><i class="fas fa-inbox"></i> Sin productos.</td></tr>');
                    }
                }
            }, 'json');
        });
    });

    // Guardar precio del proveedor al perder foco
    $(document).on('change', '.price-input', function() {
        const productId = $(this).data('product-id');
        const price     = $(this).val();
        $.post('/soleipharmav2/supplier/addProduct', {
            supplier_id: SUPPLIER_ID,
            product_id: productId,
            supplier_price: price
        }, function(r) {
            if (!r.success) Swal.fire('Error', 'No se pudo actualizar el precio.', 'error');
        }, 'json');
    });
});
</script>
