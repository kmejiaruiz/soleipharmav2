<?php
// Suponemos que LOW_STOCK_THRESHOLD está definido
$lowStockThreshold = LOW_STOCK_THRESHOLD;
$formDisabled = ($product['stock'] >= $lowStockThreshold);

if($formDisabled): ?>
<div class="d-flex align-items-center d-flex justify-content-center mt-3">
    <div class="alert alert-danger">
        <div class="d-flex justify-content-center">
            <span><i class="bi bi-exclamation-diamond-fill"></i> </span>
            <span class="ml-2">El stock actual ya no se considera bajo. No se puede aumentar.</span>
        </div>
    </div>
</div>
<?php 
endif; 
?>



<section class="content-header">
    <div class="container-fluid">
        <h1>Aumentar Stock</h1>
    </div>
</section>
<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header" style="background-color: #ffc107;">
                <h3 class="card-title">Producto: <?= $product['name'] ?></h3>
            </div>
            <form id="increaseStockForm" action="index.php?controller=admin&action=updateStock&id=<?= $product['id'] ?>"
                method="post">
                <div class="card-body">
                    <div class="form-group">
                        <label>Stock Actual:</label>
                        <input type="number" class="form-control" id="currentStock" value="<?= $product['stock'] ?>"
                            disabled>
                    </div>
                    <div class="form-group">
                        <label>Cantidad a Aumentar</label>
                        <input type="number" name="additional_stock" id="additionalStock" class="form-control"
                            placeholder="Ingrese la cantidad a aumentar" required min="1"
                            <?= $formDisabled ? 'disabled' : '' ?>>
                    </div>
                    <!-- Campos ocultos para las credenciales -->
                    <input type="hidden" name="confirm_username" id="confirmUsername">
                    <input type="hidden" name="confirm_password" id="confirmPassword">
                </div>
                <div class="card-footer">
                    <button type="submit" id="updateStockBtn" class="btn btn-warning"
                        <?= $formDisabled ? 'disabled' : '' ?>>Actualizar Stock</button> <a
                        href="index.php?controller=admin&action=inventory"
                        class="btn btn-default float-right">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- Incluir SweetAlert2 y jQuery -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
document.getElementById('increaseStockForm').addEventListener('submit', function(e) {
    e.preventDefault(); // Evitar el envío inmediato del formulario
    var currentStock = document.getElementById('currentStock').value;
    var additionalStock = document.getElementById('additionalStock').value;

    Swal.fire({
        title: 'Confirmar Actualización de Stock',
        html: `<p>Stock actual: <strong>${currentStock}</strong></p>
               <p>Aumentar en: <strong>${additionalStock}</strong> unidades</p>
               <p>Ingrese sus credenciales para confirmar:</p>
               <input type="text" id="swal-input-username" class="swal2-input" placeholder="Usuario">
               <input type="password" id="swal-input-password" class="swal2-input" placeholder="Contraseña">`,
        focusConfirm: false,
        preConfirm: () => {
            const username = Swal.getPopup().querySelector('#swal-input-username').value;
            const password = Swal.getPopup().querySelector('#swal-input-password').value;
            if (!username || !password) {
                Swal.showValidationMessage('Debe ingresar usuario y contraseña');
            }
            return {
                username: username,
                password: password
            };
        },
        showCancelButton: true,
        confirmButtonText: 'Confirmar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Asigna los valores ingresados a los campos ocultos
            document.getElementById('confirmUsername').value = result.value.username;
            document.getElementById('confirmPassword').value = result.value.password;
            // Realiza la solicitud AJAX
            var formData = $("#increaseStockForm").serialize();
            $.ajax({
                url: $("#increaseStockForm").attr("action"),
                type: "POST",
                data: formData,
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        var receipt = response.receipt;
                        Swal.fire({
                            title: 'Entrada Correcta.',
                            html: `<p><strong>${receipt.product_name}</strong></p>
                                   <p>Stock anterior: ${receipt.previous_stock}</p>
                                   <p>Aumentado en: ${receipt.additional_stock}</p>
                                   <p>Nuevo stock: ${receipt.new_stock}</p>
                                   <p>Usuario aplica: ${receipt.admin_aplica}</p>
                                   <p>Fecha: ${receipt.date}</p>
                                   <p>${receipt.company} - ${receipt.branch}</p>`,
                            icon: 'success',
                            showCancelButton: false,
                            confirmButtonText: 'Imprimir'
                        }).then((printResult) => {
                            if (printResult.isConfirmed) {
                                // Crear contenido imprimible
                                var printContent = `
                                    <html>
                                    <head>
                                        <title>Recibo de Stock</title>
                                        <style>
                                          body { font-family: Arial, sans-serif; }
                                          h1 { text-align: center; }
                                          p { font-size: 14px; }
                                        </style>
                                    </head>
                                    <body>
                                        <h1>Recibo de Actualización de Stock</h1>
                                        <p><strong>${receipt.product_name}</strong></p>
                                        <p>Stock anterior: ${receipt.previous_stock}</p>
                                        <p>Aumentado en: ${receipt.additional_stock}</p>
                                        <p>Nuevo stock: ${receipt.new_stock}</p>
                                        <p>Aplicado por: ${receipt.admin_aplica}</p>
                                        <p>Fecha: ${receipt.date}</p>
                                        <p>${receipt.company} - ${receipt.branch}</p>
                                    </body>
                                    </html>
                                `;
                                var printWindow = window.open('', '',
                                    'height=600,width=400');
                                printWindow.document.write(printContent);
                                printWindow.document.close();
                                printWindow.focus();
                                printWindow.print();
                                printWindow.close();

                                location.reload();
                            }
                            // Si el stock ya no se considera bajo, deshabilitar el formulario y mostrar alerta
                            if (receipt.disableIncrease) {
                                $("#additionalStock").prop("disabled", true);
                                $("#updateStockBtn").prop("disabled", true);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Acción no permitida',
                                    text: 'El stock ya no se considera bajo. No se puede aumentar más.'
                                });
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Ocurrió un error en el servidor.'
                    });
                }
            });
        }
    });
});
</script>