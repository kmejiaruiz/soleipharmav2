<section class="content-header">
    <div class="container-fluid">
        <h1>Agregar Producto</h1>
    </div>
</section>
<section class="content">
    <div class="container-fluid">
        <form id="addProductForm" action="index.php?controller=admin&action=saveProduct" method="post">
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <textarea name="description" class="form-control" required></textarea>
            </div>
            <div class="form-group">
                <label>Precio</label>
                <input type="number" step="0.01" name="price" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Stock</label>
                <input type="number" name="stock" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Imagen (URL)</label>
                <input type="text" name="image" class="form-control">
            </div>
            <div class="form-group form-check">
                <input type="checkbox" name="available" class="form-check-input" id="available" checked>
                <label class="form-check-label" for="available">Disponible para venta</label>
            </div>
            <div class="form-group" id="reasonGroup" style="display: none;">
                <label for="reason_unavailable">Motivo de no disponibilidad</label>
                <textarea name="reason_unavailable" id="reason_unavailable" class="form-control"></textarea>
            </div>
            <!-- Campos ocultos para credenciales -->
            <input type="hidden" name="confirm_username" id="confirmUsername">
            <input type="hidden" name="confirm_password" id="confirmPassword">
            <button type="submit" class="btn btn-primary">Agregar Producto</button>
        </form>
    </div>
</section>
<!-- Incluir SweetAlert2 y jQuery -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
document.getElementById('available').addEventListener('change', function() {
    var reasonGroup = document.getElementById('reasonGroup');
    reasonGroup.style.display = this.checked ? 'none' : 'block';
});

$("#addProductForm").on('submit', function(e) {
    e.preventDefault();
    Swal.fire({
        title: 'Confirmar Agregar Producto',
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
            var formData = $("#addProductForm").serialize();
            $.ajax({
                url: $("#addProductForm").attr("action"),
                type: "POST",
                data: formData,
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Producto agregado',
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
                        text: 'Ocurrió un error en el servidor.'
                    });
                }
            });
        }
    });
});
</script>