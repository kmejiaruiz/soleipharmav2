<?php
// variables en scope: $order, $orderItems, $sysSub, $sysTax, $sysTotal
?>
<section class="content-header">
  <h1>Entrada de Mercadería #<?= htmlspecialchars($order['id']) ?></h1>
</section>
<section class="content">
  <form id="goodsEntryForm" action="index.php?controller=order&action=storeGoodsEntry&id=<?= $order['id'] ?>"
    method="post">

    <table class="table table-bordered">
      <thead>
        <tr>
          <th>SKU</th>
          <th>Producto</th>
          <th>Ordenado</th>
          <th>Recibido</th>
          <th>Justificación</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orderItems as $it): ?>
          <tr data-cost="<?= $it['cost'] ?>">
            <td><?= htmlspecialchars($it['sku']) ?></td>
            <td><?= htmlspecialchars($it['name']) ?></td>
            <td class="ordered"><?= $it['ordered_qty'] ?></td>
            <td>
              <input type="number" name="received_quantities[<?= $it['product_id'] ?>]" value="<?= $it['ordered_qty'] ?>"
                min="0" class="form-control received-qty">
            </td>
            <td>
              <select name="justifications[<?= $it['product_id'] ?>]" class="form-control justification-select"
                style="display:none">
                <option value="">Seleccione...</option>
                <option>Sin justificación por parte del proveedor</option>
                <option>Daño en transporte</option>
                <option>Pérdida en almacenamiento</option>
                <option>Error en entrega</option>
              </select>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="row mt-4">
      <div class="col-md-4">
        <label>Subtotal Factura *</label>
        <input type="number" step="0.01" min="0" class="form-control" id="invoiceSubtotalInput">
      </div>
      <div class="col-md-4">
        <label>IVA Factura *</label>
        <input type="number" step="0.01" min="0" class="form-control" id="invoiceTaxInput">
      </div>
      <div class="col-md-4 text-right bg-light p-3 rounded">
        <label>Total Sistema</label>
        <div id="systemTotals">
          <p>Subtotal: C$<span id="sysSub">0.00</span></p>
          <p>IVA (15%): C$<span id="sysTax">0.00</span></p>
          <p><strong>Total: C$<span id="sysTotal">0.00</span></strong></p>
        </div>
      </div>
    </div>

    <!-- Campos ocultos que se irán inyectando -->
    <div id="hiddenFacturaFields"></div>
    <div id="hiddenCreds"></div>

    <button type="button" id="btnFacturas" class="btn btn-secondary mt-4">
      Factura Física
    </button>
    <button type="button" id="btnRegisterEntry" class="btn btn-primary mt-4">
      Registrar Entrada
    </button>
  </form>
</section>

<!-- Modal Bootstrap para factura física -->
<div id="facturaModal" class="modal fade" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Datos de Factura Física</h5>
        <button type="button" class="close" id="closeModal">×</button>
      </div>
      <div class="modal-body">
        <table class="table table-borderless">
          <tr>
            <th>Subtotal Sistema</th>
            <td><input type="text" class="form-control" id="systemSubtotal" readonly></td>
            <th>IVA Sistema (15%)</th>
            <td><input type="text" class="form-control" id="systemTaxRead" readonly></td>
            <th>Total Sistema</th>
            <td><input type="text" class="form-control" id="systemTotalRead" readonly></td>
          </tr>
          <tr>
            <th>Subtotal Factura *</th>
            <td><input type="number" step="0.01" min="0" class="form-control" id="invoiceSubtotalInputModal"></td>
            <th>IVA Factura *</th>
            <td><input type="number" step="0.01" min="0" class="form-control" id="invoiceTaxInputModal"></td>
            <th>Total Factura</th>
            <td><input type="text" class="form-control" id="invoiceTotalInputModal" readonly></td>
          </tr>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="saveFactura">Guardar Factura</button>
        <button type="button" class="btn btn-secondary" id="cancelFactura">Cancelar</button>
      </div>
    </div>
  </div>
</div>

<!-- JS necesario -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  $(function () {

    const IVA_RATE = 0.15;

    function recalcSystem() {
      let sub = 0;
      $('tbody tr').each(function () {
        const cost = parseFloat($(this).data('cost')) || 0;
        const qty = parseInt($(this).find('.received-qty').val()) || 0;
        sub += cost * qty;
      });
      const tax = +(sub * IVA_RATE).toFixed(2);
      const tot = +(sub + tax).toFixed(2);
      $('#sysSub, #systemSubtotal, #systemSubtotal').text(sub.toFixed(2));
      $('#sysTax, #systemTaxRead').text(tax.toFixed(2));
      $('#sysTotal, #systemTotalRead').text(tot.toFixed(2));
    }

    // Mostrar select si recibe < ordenado
    $('.received-qty').on('input', function () {
      const tr = $(this).closest('tr');
      const ord = parseInt(tr.find('.ordered').text());
      if (parseInt(this.value) < ord) {
        tr.find('.justification-select').show();
      } else {
        tr.find('.justification-select').hide().val('');
      }
      recalcSystem();
    });

    // Botón Factura
    $('#btnFacturas').click(function (e) {
      e.preventDefault();
      recalcSystem();
      // rellenar modal inputs con sistema
      $('#invoiceSubtotalInputModal, #invoiceTaxInputModal, #invoiceTotalInputModal').val('');
      $('#facturaModal').modal('show');
    });
    $('#closeModal, #cancelFactura').click(function () {
      $('#facturaModal').modal('hide');
    });

    // Calcular factura en modal
    $('#invoiceSubtotalInputModal, #invoiceTaxInputModal').on('input', function () {
      const sub = parseFloat($('#invoiceSubtotalInputModal').val()) || 0;
      const taxManual = parseFloat($('#invoiceTaxInputModal').val()) || 0;
      $('#invoiceTotalInputModal').val((sub + taxManual).toFixed(2));
    });

    // Guardar datos factura
    $('#saveFactura').click(function () {
      const sub = $('#invoiceSubtotalInputModal').val();
      const tax = $('#invoiceTaxInputModal').val();
      if (!sub || !tax) {
        return Swal.fire('Atención', 'Debe ingresar Subtotal e IVA de la factura.', 'warning');
      }
      // inject hidden
      if (!$('#hiddenFacturaFields').children().length) {
        $('#hiddenFacturaFields').append(`
        <input type="hidden" name="invoice_subtotal" value="${sub}">
        <input type="hidden" name="invoice_tax"      value="${tax}">
      `);
      } else {
        $('input[name=invoice_subtotal]').val(sub);
        $('input[name=invoice_tax]').val(tax);
      }
      $('#facturaModal').modal('hide');
    });

    // Registrar Entrada con credenciales
    $('#btnRegisterEntry').click(function (e) {
      e.preventDefault();
      // validar factura
      if (!$('input[name=invoice_subtotal]').length) {
        return Swal.fire('Atención', 'Primero ingresa datos de la factura física.', 'warning');
      }
      // pedir credenciales
      Swal.fire({
        title: 'Credenciales Admin',
        html:
          '<input id="swal-user" class="swal2-input" placeholder="Usuario">' +
          '<input id="swal-pass" type="password" class="swal2-input" placeholder="Contraseña">',
        preConfirm: () => {
          const u = $('#swal-user').val();
          const p = $('#swal-pass').val();
          if (!u || !p) Swal.showValidationMessage('Ambos campos obligatorios');
          return { username: u, password: p };
        }
      }).then(res => {
        if (!res.isConfirmed) return;
        // inject creds
        if (!$('#hiddenCreds').children().length) {
          $('#hiddenCreds').append(`
          <input type="hidden" name="confirm_username" value="${res.value.username}">
          <input type="hidden" name="confirm_password" value="${res.value.password}">
        `);
        } else {
          $('input[name=confirm_username]').val(res.value.username);
          $('input[name=confirm_password]').val(res.value.password);
        }
        // enviar AJAX
        const form = $('#goodsEntryForm')[0];
        fetch(form.action, { method: 'POST', body: new FormData(form) })
          .then(r => r.json()).then(json => {
            if (json.success) {
              Swal.fire('¡Listo!', json.message, 'success')
                .then(() => window.location = 'index.php?controller=order&action=index');
            } else {
              Swal.fire('Error', json.message, 'error');
            }
          })
          .catch(() => Swal.fire('Error', 'Comunicación fallida', 'error'));
      });
    });

    // cálculo inicial
    recalcSystem();
  });
</script>