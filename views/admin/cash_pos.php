<?php // views/admin/cash_pos.php ?>
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col-sm-6">
                <h1><i class="fas fa-cash-register text-success"></i> Punto de Venta (POS)</h1>
            </div>
            <div class="col-sm-6 text-right">
                <a href="/soleipharmav2/cash/dashboard" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver al Panel
                </a>
            </div>
        </div>
    </div>
</section>

<section class="content">
<div class="container-fluid">
<div class="row">

    <!-- LEFT: Product Search -->
    <div class="col-lg-7">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-search"></i> Buscar Producto</h3>
            </div>
            <div class="card-body">
                <!-- Search input -->
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-primary text-white">
                            <i class="fas fa-barcode"></i>
                        </span>
                    </div>
                    <input type="text" id="posSearch" class="form-control form-control-lg"
                           placeholder="Nombre o código del producto..." autocomplete="off">
                    <div class="input-group-append">
                        <button class="btn btn-primary" onclick="document.getElementById('posSearch').focus()">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>

                <!-- Search results -->
                <div id="searchResults" style="display:none;">
                    <table class="table table-sm table-hover table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th>Producto</th>
                                <th>SKU</th>
                                <th class="text-right">Precio</th>
                                <th class="text-center">Stock</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="searchResultsBody"></tbody>
                    </table>
                </div>
                <div id="searchNoResults" class="text-center text-muted py-3" style="display:none;">
                    <i class="fas fa-search fa-2x mb-2"></i><br>No se encontraron productos.
                </div>
                <div id="searchLoading" class="text-center py-3" style="display:none;">
                    <i class="fas fa-spinner fa-spin"></i> Buscando...
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT: Cart -->
    <div class="col-lg-5">
        <div class="card card-outline card-success" id="cartCard">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-shopping-cart"></i> Carrito de Venta</h3>
                <div class="card-tools">
                    <button class="btn btn-xs btn-danger" onclick="clearCart()" title="Limpiar carrito">
                        <i class="fas fa-trash"></i> Limpiar
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div id="emptyCart" class="text-center text-muted py-5">
                    <i class="fas fa-shopping-cart fa-3x mb-3 opacity-25"></i>
                    <p>Agregue productos buscando a la izquierda</p>
                </div>
                <div id="cartContent" style="display:none;">
                    <table class="table table-sm mb-0" id="cartTable">
                        <thead class="thead-light">
                            <tr>
                                <th>Producto</th>
                                <th class="text-center" style="width:70px">Cant.</th>
                                <th class="text-right" style="width:80px">Subtotal</th>
                                <th style="width:30px"></th>
                            </tr>
                        </thead>
                        <tbody id="cartBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer" id="cartFooter" style="display:none;">
                <!-- Totals -->
                <div class="d-flex justify-content-between mb-2">
                    <span class="font-weight-bold">TOTAL:</span>
                    <span class="font-weight-bold text-success" id="cartTotal" style="font-size:1.3em;">C$ 0.00</span>
                </div>

                <!-- Payment info -->
                <div class="form-row mb-2">
                    <div class="col-7">
                        <label class="small mb-0">Cliente (opcional)</label>
                        <input type="text" id="clientName" class="form-control form-control-sm"
                               placeholder="Consumidor Final" value="Consumidor Final">
                    </div>
                    <div class="col-5">
                        <label class="small mb-0">Método de Pago</label>
                        <select id="payMethod" class="form-control form-control-sm">
                            <option value="efectivo">Efectivo</option>
                            <option value="tarjeta">Tarjeta</option>
                            <option value="transferencia">Transferencia</option>
                        </select>
                    </div>
                </div>

                <!-- Discount -->
                <div class="form-row mb-2" id="discountRow">
                    <div class="col-7">
                        <label class="small mb-0">Descuento</label>
                        <div class="input-group input-group-sm">
                            <input type="number" id="discountValue" class="form-control"
                                   placeholder="0" min="0" step="0.01" value="0">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" id="discountToggle"
                                        type="button" title="Cambiar tipo de descuento">C$</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-5">
                        <label class="small mb-0">Total c/descuento</label>
                        <input type="text" id="totalAfterDiscount" class="form-control form-control-sm font-weight-bold text-danger"
                               readonly value="C$ 0.00">
                    </div>
                </div>

                <div class="form-row mb-3" id="cashPayRow">
                    <div class="col-6">
                        <label class="small mb-0">Monto Recibido</label>
                        <input type="number" id="amountPaid" class="form-control form-control-sm"
                               placeholder="0.00" min="0" step="0.01">
                    </div>
                    <div class="col-6">
                        <label class="small mb-0">Cambio</label>
                        <input type="text" id="changeDisplay" class="form-control form-control-sm text-success font-weight-bold"
                               readonly value="C$ 0.00">
                    </div>
                </div>

                <button class="btn btn-success btn-block btn-lg" id="btnProcessSale">
                    <i class="fas fa-check-circle"></i> Procesar Venta
                </button>
            </div>
        </div>
    </div>

</div>
</div>
</section>

<script>
// ── Cart state ────────────────────────────────────────────────────────────────
let cart = {}; // { productId: { id, name, price, qty, stock } }
let searchTimer = null;

// ── Search ────────────────────────────────────────────────────────────────────
document.getElementById('posSearch').addEventListener('input', function() {
    clearTimeout(searchTimer);
    const q = this.value.trim();
    if (q.length < 2) {
        document.getElementById('searchResults').style.display = 'none';
        document.getElementById('searchNoResults').style.display = 'none';
        return;
    }
    document.getElementById('searchLoading').style.display = 'block';
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('searchNoResults').style.display = 'none';

    searchTimer = setTimeout(() => {
        fetch(`/soleipharmav2/cash/posSearch?q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(products => {
                document.getElementById('searchLoading').style.display = 'none';
                if (products.length === 0) {
                    document.getElementById('searchNoResults').style.display = 'block';
                    return;
                }
                const tbody = document.getElementById('searchResultsBody');
                tbody.innerHTML = '';
                products.forEach(p => {
                    const tr = document.createElement('tr');
                    tr.style.cursor = 'pointer';
                    tr.innerHTML = `
                        <td><strong>${escHtml(p.name)}</strong></td>
                        <td><small class="text-muted">${escHtml(p.sku || '—')}</small></td>
                        <td class="text-right font-weight-bold text-success">C$ ${parseFloat(p.sale_price).toFixed(2)}</td>
                        <td class="text-center"><span class="badge ${p.stock < 5 ? 'badge-warning' : 'badge-success'}">${p.stock}</span></td>
                        <td>
                            <button class="btn btn-xs btn-primary" onclick="addToCart(${p.id}, \`${escHtml(p.name).replace(/`/g,"'")}\`, ${p.sale_price}, ${p.stock})">
                                <i class="fas fa-plus"></i>
                            </button>
                        </td>`;
                    tbody.appendChild(tr);
                });
                document.getElementById('searchResults').style.display = 'block';
            })
            .catch(() => { document.getElementById('searchLoading').style.display = 'none'; });
    }, 280);
});

// Also search on Enter key (for barcode scanners)
document.getElementById('posSearch').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        clearTimeout(searchTimer);
        const q = this.value.trim();
        if (q.length >= 2) {
            fetch(`/soleipharmav2/cash/posSearch?q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(products => {
                    if (products.length === 1) {
                        addToCart(products[0].id, products[0].name, products[0].sale_price, products[0].stock);
                        this.value = '';
                    }
                });
        }
    }
});

// ── Cart functions ────────────────────────────────────────────────────────────
function addToCart(id, name, price, stock) {
    if (cart[id]) {
        if (cart[id].qty >= stock) {
            Swal.fire({ icon:'warning', title:'Stock insuficiente', text: `Solo hay ${stock} unidades disponibles.`, timer:2000, showConfirmButton:false });
            return;
        }
        cart[id].qty++;
    } else {
        cart[id] = { id, name, price: parseFloat(price), qty: 1, stock: parseInt(stock) };
    }
    renderCart();
}

function removeFromCart(id) {
    delete cart[id];
    renderCart();
}

function updateQty(id, delta) {
    if (!cart[id]) return;
    const newQty = cart[id].qty + delta;
    if (newQty <= 0) { removeFromCart(id); return; }
    if (newQty > cart[id].stock) {
        Swal.fire({ icon:'warning', title:'Stock máximo', text:`Solo hay ${cart[id].stock} disponibles.`, timer:1500, showConfirmButton:false });
        return;
    }
    cart[id].qty = newQty;
    renderCart();
}

function setQty(id, value) {
    const qty = parseInt(value) || 0;
    if (qty <= 0) { removeFromCart(id); return; }
    if (qty > cart[id].stock) {
        Swal.fire({ icon:'warning', title:'Stock insuficiente', text:`Solo hay ${cart[id].stock} disponibles.`, timer:1500, showConfirmButton:false });
        cart[id].qty = cart[id].stock;
    } else {
        cart[id].qty = qty;
    }
    renderCart();
}

function clearCart() {
    cart = {};
    renderCart();
}

function renderCart() {
    const ids = Object.keys(cart);
    const empty = document.getElementById('emptyCart');
    const content = document.getElementById('cartContent');
    const footer = document.getElementById('cartFooter');

    if (ids.length === 0) {
        empty.style.display = 'block';
        content.style.display = 'none';
        footer.style.display = 'none';
        return;
    }
    empty.style.display = 'none';
    content.style.display = 'block';
    footer.style.display = 'block';

    let total = 0;
    const tbody = document.getElementById('cartBody');
    tbody.innerHTML = '';
    ids.forEach(id => {
        const item = cart[id];
        const sub = item.price * item.qty;
        total += sub;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <div style="font-size:12px;font-weight:600">${escHtml(item.name)}</div>
                <small class="text-muted">C$ ${item.price.toFixed(2)} c/u</small>
            </td>
            <td class="text-center">
                <div class="input-group input-group-sm" style="width:90px;margin:auto;">
                    <div class="input-group-prepend">
                        <button class="btn btn-outline-secondary btn-xs px-1" onclick="updateQty(${id}, -1)">−</button>
                    </div>
                    <input type="number" class="form-control text-center px-1" style="min-width:35px"
                           value="${item.qty}" min="1" max="${item.stock}"
                           onchange="setQty(${id}, this.value)">
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary btn-xs px-1" onclick="updateQty(${id}, 1)">+</button>
                    </div>
                </div>
            </td>
            <td class="text-right font-weight-bold text-success" style="vertical-align:middle">C$ ${sub.toFixed(2)}</td>
            <td style="vertical-align:middle">
                <button class="btn btn-xs btn-danger" onclick="removeFromCart(${id})">
                    <i class="fas fa-times"></i>
                </button>
            </td>`;
        tbody.appendChild(tr);
    });

    document.getElementById('cartTotal').textContent = `C$ ${total.toFixed(2)}`;
    recalcDiscount();
}

// ── Discount ───────────────────────────────────────────────────
let discountType = 'fixed'; // 'fixed' | 'percent'

document.getElementById('discountToggle').addEventListener('click', function() {
    discountType = discountType === 'fixed' ? 'percent' : 'fixed';
    this.textContent = discountType === 'fixed' ? 'C$' : '%';
    this.classList.toggle('btn-outline-secondary', discountType === 'fixed');
    this.classList.toggle('btn-outline-warning',   discountType === 'percent');
    document.getElementById('discountValue').max = discountType === 'percent' ? 100 : '';
    recalcDiscount();
});

document.getElementById('discountValue').addEventListener('input', recalcDiscount);

function recalcDiscount() {
    const subtotal = parseFloat(document.getElementById('cartTotal').textContent.replace('C$ ', '')) || 0;
    const rawVal   = parseFloat(document.getElementById('discountValue').value) || 0;
    let   discAmt  = 0;
    if (discountType === 'percent') {
        discAmt = Math.min(subtotal, subtotal * Math.min(rawVal, 100) / 100);
    } else {
        discAmt = Math.min(subtotal, Math.max(0, rawVal));
    }
    const finalTotal = subtotal - discAmt;
    const el = document.getElementById('totalAfterDiscount');
    if (discAmt > 0) {
        el.value = `C$ ${finalTotal.toFixed(2)}`;
        el.style.display = 'block';
    } else {
        el.value = `C$ ${subtotal.toFixed(2)}`;
    }
    calcChange();
}

function getEffectiveTotal() {
    const subtotal = parseFloat(document.getElementById('cartTotal').textContent.replace('C$ ', '')) || 0;
    const rawVal   = parseFloat(document.getElementById('discountValue').value) || 0;
    let   discAmt  = 0;
    if (discountType === 'percent') {
        discAmt = Math.min(subtotal, subtotal * Math.min(rawVal, 100) / 100);
    } else {
        discAmt = Math.min(subtotal, Math.max(0, rawVal));
    }
    return { subtotal, discAmt, total: subtotal - discAmt };
}

// ── Payment ───────────────────────────────────────────────────────────────────
document.getElementById('payMethod').addEventListener('change', function() {
    document.getElementById('cashPayRow').style.display = this.value === 'efectivo' ? 'flex' : 'none';
});
document.getElementById('amountPaid').addEventListener('input', calcChange);

function calcChange() {
    const { total } = getEffectiveTotal();
    const paid      = parseFloat(document.getElementById('amountPaid').value) || 0;
    const change    = Math.max(0, paid - total);
    document.getElementById('changeDisplay').value = `C$ ${change.toFixed(2)}`;
    document.getElementById('changeDisplay').style.color = change > 0 ? '#28a745' : '#6c757d';
}

// ── Process Sale ──────────────────────────────────────────────────────────────
document.getElementById('btnProcessSale').addEventListener('click', async function() {
    const ids = Object.keys(cart);
    if (ids.length === 0) return;

    const { subtotal, discAmt, total } = getEffectiveTotal();
    const payMethod  = document.getElementById('payMethod').value;
    const paid       = parseFloat(document.getElementById('amountPaid').value) || 0;
    // Si efectivo y no ingresaron monto, asumir monto exacto (cambio = 0)
    const effectivePaid = (payMethod === 'efectivo' && paid <= 0) ? total : paid;

    if (payMethod === 'efectivo' && effectivePaid < total - 0.001) {
        Swal.fire({ icon:'warning', title:'Monto insuficiente', text:'El monto recibido es menor al total a cobrar.' });
        return;
    }

    let htmlConfirm = `Subtotal: C$ ${subtotal.toFixed(2)}`;
    if (discAmt > 0) htmlConfirm += `<br>Descuento: <span class="text-danger">−C$ ${discAmt.toFixed(2)}</span>`;
    htmlConfirm += `<br><strong>Total: C$ ${total.toFixed(2)}</strong>`;
    if (payMethod === 'efectivo') htmlConfirm += `<br>Recibido: C$ ${effectivePaid.toFixed(2)} | Cambio: C$ ${Math.max(0, effectivePaid - total).toFixed(2)}`;

    const confirm = await Swal.fire({
        title: '¿Confirmar venta?',
        html: htmlConfirm,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, procesar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#28a745'
    });
    if (!confirm.isConfirmed) return;

    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

    const fd = new FormData();
    fd.append('client_name',    document.getElementById('clientName').value || 'Consumidor Final');
    fd.append('pay_method',     payMethod);
    fd.append('amount_paid',    effectivePaid);
    fd.append('discount_type',  discountType);
    fd.append('discount_value', parseFloat(document.getElementById('discountValue').value) || 0);
    ids.forEach(id => {
        fd.append('items[]', JSON.stringify({ id, qty: cart[id].qty, price: cart[id].price }));
    });

    const res  = await fetch('/soleipharmav2/cash/posSale', { method: 'POST', body: fd });
    const data = await res.json();

    this.disabled = false;
    this.innerHTML = '<i class="fas fa-check-circle"></i> Procesar Venta';

    if (data.success) {
        let htmlOk = `Total: <strong>C$ ${parseFloat(data.total).toFixed(2)}</strong>`;
        if (parseFloat(data.discount) > 0)
            htmlOk = `Subtotal: C$ ${parseFloat(data.subtotal).toFixed(2)}<br>Descuento: <span style="color:#c00">−C$ ${parseFloat(data.discount).toFixed(2)}</span><br><strong>Total: C$ ${parseFloat(data.total).toFixed(2)}</strong>`;
        if (payMethod === 'efectivo')
            htmlOk += `<br>Cambio: <strong>C$ ${parseFloat(data.change).toFixed(2)}</strong>`;

        Swal.fire({
            icon: 'success',
            title: 'Venta Procesada',
            html: htmlOk,
            confirmButtonText: 'Imprimir Recibo',
            showCancelButton: true,
            cancelButtonText: 'Nueva Venta'
        }).then(result => {
            if (result.isConfirmed) window.open(data.receipt_url, '_blank');
            newSale();
        });
    } else {
        Swal.fire({ icon: 'error', title: 'Error', text: data.message });
    }
});

function newSale() {
    clearCart();
    document.getElementById('posSearch').value = '';
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('searchNoResults').style.display = 'none';
    document.getElementById('amountPaid').value = '';
    document.getElementById('discountValue').value = 0;
    recalcDiscount();
    document.getElementById('posSearch').focus();
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Focus search on load
window.addEventListener('load', () => document.getElementById('posSearch').focus());
</script>
