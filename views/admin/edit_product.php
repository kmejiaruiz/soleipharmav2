<section class="content-header">
    <div class="container-fluid">
        <h1>Editar Producto</h1>
    </div>
</section>
<section class="content">
    <div class="container-fluid">
        <form id="editProductForm" action="index.php?controller=admin&action=updateProduct&id=<?= $product['id'] ?>"
            method="post">
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="name" class="form-control" value="<?= $product['name'] ?>" required>
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <textarea name="description" class="form-control" required><?= $product['description'] ?></textarea>
            </div>
            <div class="form-group">
                <label>Precio</label>
                <input type="number" step="0.01" name="price" class="form-control" value="<?= $product['price'] ?>"
                    required>
            </div>
            <div class="form-group">
                <label>Stock</label>
                <input type="number" name="stock" class="form-control" value="<?= $product['stock'] ?>" required>
            </div>
            <div class="form-group">
                <label>Imagen (URL)</label>
                <input type="text" name="image" class="form-control" value="<?= $product['image'] ?>">
            </div>
            <div class="form-group form-check">
                <input type="checkbox" name="available" class="form-check-input" id="available"
                    <?= $product['available'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="available">Disponible para venta</label>
            </div>
            <div class="form-group" id="reasonGroup" style="display: <?= $product['available'] ? 'none' : 'block' ?>;">
                <label for="reason_unavailable">Motivo de no disponibilidad</label>
                <textarea name="reason_unavailable" id="reason_unavailable"
                    class="form-control"><?= $product['reason_unavailable'] ?></textarea>
            </div>
            <!-- Campos ocultos para credenciales -->
            <input type="hidden" name="confirm_username" id="confirmUsername">
            <input type="hidden" name="confirm_password" id="confirmPassword">
            <button type="submit" class="btn btn-primary mb-4">Actualizar Producto</button>
        </form>
    </div>
</section>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
document.getElementById('available').addEventListener('change', function() {
    var reasonGroup = document.getElementById('reasonGroup');
    reasonGroup.style.display = this.checked ? 'none' : 'block';
});

$("#editProductForm").on('submit', function(e) {
    e.preventDefault();
    Swal.fire({
        title: 'Confirmar Actualización de Producto',
        html: `<p>Ingrese sus credenciales para confirmar:</p>
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
            $("#confirmUsername").val(result.value.username);
            $("#confirmPassword").val(result.value.password);
            var formData = $("#editProductForm").serialize();
            $.ajax({
                url: $("#editProductForm").attr("action"),
                type: "POST",
                data: formData,
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Producto actualizado',
                            text: response.message
                        }).then(() => {
                            window.location.href =
                                "index.php?controller=admin&action=index";
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
                        text: response.error
                        
                    });
                }
            });
        }
    });
});
</script>