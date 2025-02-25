<!-- views/admin/invoice.php -->
<section class="content-header">
  <div class="container-fluid">
    <h1>Generar Boleta / Factura</h1>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <div class="card card-primary">
      <div class="card-header" style="background-color: #4B0082;">
        <h3 class="card-title">Datos de la Boleta</h3>
      </div>
      <!-- form start -->
      <form method="post" action="index.php?controller=invoice&action=generate">
        <div class="card-body">
          <div class="form-group">
            <label for="serie">Serie</label>
            <input type="text" class="form-control" id="serie" name="serie" placeholder="Ej: B001" required>
          </div>
          <div class="form-group">
            <label for="numero">Número</label>
            <input type="text" class="form-control" id="numero" name="numero" placeholder="Ej: 00012345" required>
          </div>
          <div class="form-group">
            <label for="fecha">Fecha</label>
            <input type="date" class="form-control" id="fecha" name="fecha" value="<?= date('Y-m-d'); ?>" required>
          </div>
          <div class="form-group">
            <label for="ruc">RUC / DNI</label>
            <input type="text" class="form-control" id="ruc" name="ruc" placeholder="Número de identificación fiscal" required>
          </div>
          <div class="form-group">
            <label for="cliente">Razón Social / Cliente</label>
            <input type="text" class="form-control" id="cliente" name="cliente" placeholder="Nombre o razón social" required>
          </div>
          <div class="form-group">
            <label for="direccion">Dirección</label>
            <input type="text" class="form-control" id="direccion" name="direccion" placeholder="Dirección completa" required>
          </div>
          <!-- Detalle de productos -->
          <div class="form-group">
            <label>Detalle de Productos</label>
            <table class="table table-bordered">
              <thead>
                <tr>
                  <th>Producto</th>
                  <th>Cantidad</th>
                  <th>Precio Unitario</th>
                  <th>Subtotal</th>
                </tr>
              </thead>
              <tbody id="detalleProductos">
                <tr>
                  <td><input type="text" class="form-control" name="productos[0][nombre]" placeholder="Nombre del producto" required></td>
                  <td><input type="number" class="form-control" name="productos[0][cantidad]" placeholder="Cantidad" min="1" required></td>
                  <td><input type="number" step="0.01" class="form-control" name="productos[0][precio]" placeholder="Precio Unitario" required></td>
                  <td><input type="number" step="0.01" class="form-control" name="productos[0][subtotal]" placeholder="Subtotal" readonly></td>
                </tr>
              </tbody>
            </table>
            <button type="button" id="btnAgregarProducto" class="btn btn-secondary mt-2">Agregar Producto</button>
          </div>
          <!-- Totales -->
          <div class="form-group">
            <label for="subtotalTotal">Subtotal Total</label>
            <input type="number" step="0.01" class="form-control" id="subtotalTotal" name="subtotalTotal" readonly>
          </div>
          <div class="form-group">
            <label for="igv">IGV (18%)</label>
            <input type="number" step="0.01" class="form-control" id="igv" name="igv" readonly>
          </div>
          <div class="form-group">
            <label for="total">Total</label>
            <input type="number" step="0.01" class="form-control" id="total" name="total" readonly>
          </div>
        </div>
        <!-- /.card-body -->
        <div class="card-footer">
          <button type="submit" class="btn btn-primary">Generar Boleta</button>
        </div>
      </form>
    </div>
  </div>
</section>

<!-- Script para agregar filas al detalle y calcular totales -->
<script>
document.addEventListener("DOMContentLoaded", function(){
    let index = 1;
    document.getElementById('btnAgregarProducto').addEventListener('click', function(){
        const tableBody = document.getElementById('detalleProductos');
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td><input type="text" class="form-control" name="productos[${index}][nombre]" placeholder="Nombre del producto" required></td>
            <td><input type="number" class="form-control producto-cantidad" name="productos[${index}][cantidad]" placeholder="Cantidad" min="1" required></td>
            <td><input type="number" step="0.01" class="form-control producto-precio" name="productos[${index}][precio]" placeholder="Precio Unitario" required></td>
            <td><input type="number" step="0.01" class="form-control producto-subtotal" name="productos[${index}][subtotal]" placeholder="Subtotal" readonly></td>
        `;
        tableBody.appendChild(newRow);
        index++;
        calcularTotales();
    });

    function calcularTotales(){
        const cantidades = document.querySelectorAll('.producto-cantidad');
        const precios = document.querySelectorAll('.producto-precio');
        const subtotales = document.querySelectorAll('.producto-subtotal');
        let subtotalTotal = 0;
        for(let i = 0; i < cantidades.length; i++){
            const cant = parseFloat(cantidades[i].value) || 0;
            const precio = parseFloat(precios[i].value) || 0;
            const subtotal = cant * precio;
            subtotales[i].value = subtotal.toFixed(2);
            subtotalTotal += subtotal;
        }
        document.getElementById('subtotalTotal').value = subtotalTotal.toFixed(2);
        const igv = subtotalTotal * 0.18;
        document.getElementById('igv').value = igv.toFixed(2);
        const total = subtotalTotal + igv;
        document.getElementById('total').value = total.toFixed(2);
    }

    document.addEventListener('input', function(e){
        if(e.target.classList.contains('producto-cantidad') || e.target.classList.contains('producto-precio')){
            calcularTotales();
        }
    });
});
</script>
