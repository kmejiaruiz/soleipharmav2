<?php
// Se espera que el controlador pase la variable $nextNoteNumber (número de nota autogenerado)
?>
<section class="content-header">
    <div class="container-fluid">
        <h1>Crear Nota de Crédito/Débito</h1>
    </div>
</section>
<section class="content">
    <div class="container-fluid">
        <form id="notesForm" action="index.php?controller=notes&action=save" method="post">
            <div class="form-group">
                <label>Número de Nota</label>
                <input type="text" name="note_number" class="form-control"
                    value="<?= htmlspecialchars($nextNoteNumber) ?>" readonly>
            </div>
            <div class="form-group">
                <label>Tipo de Nota</label>
                <select name="type" class="form-control" required>
                    <option value="credit">Crédito</option>
                    <option value="debit">Débito</option>
                </select>
            </div>
            <div class="form-group">
                <label>Nombre del Cliente</label>
                <input type="text" name="client_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Documento del Cliente (RUC/DNI)</label>
                <input type="text" name="client_document" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Dirección del Cliente</label>
                <input type="text" name="client_address" class="form-control">
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <textarea name="description" class="form-control"></textarea>
            </div>
            <div class="form-group">
                <label>Monto</label>
                <input type="number" step="0.01" name="amount" class="form-control" required>
            </div>
            <!-- Campos ocultos para credenciales de administrador -->
            <input type="hidden" name="confirm_username" id="confirmUsername">
            <input type="hidden" name="confirm_password" id="confirmPassword">
            <button type="submit" class="btn btn-primary">Crear Nota</button>
            <a href="index.php?controller=notes&action=index" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</section>

<!-- Incluir SweetAlert2 y jQuery -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
    $("#notesForm").on('submit', function (e) {
        e.preventDefault(); // Evita el envío tradicional del formulario
        Swal.fire({
            title: 'Confirmar Creación de Nota',
            html: `<p>Ingrese sus credenciales de administrador para confirmar:</p>
                 <input type="text" id="swal-input-username" class="swal2-input" placeholder="Usuario">
                 <input type="password" id="swal-input-password" class="swal2-input" placeholder="Contraseña">`,
            focusConfirm: false,
            preConfirm: () => {
                const username = Swal.getPopup().querySelector('#swal-input-username').value;
                const password = Swal.getPopup().querySelector('#swal-input-password').value;
                if (!username || !password) {
                    Swal.showValidationMessage('Debe ingresar usuario y contraseña');
                }
                return { username: username, password: password };
            },
            showCancelButton: true,
            confirmButtonText: 'Confirmar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Asigna las credenciales ingresadas a los campos ocultos
                $("#confirmUsername").val(result.value.username);
                $("#confirmPassword").val(result.value.password);
                // Serializa y envía el formulario vía AJAX
                var formData = $("#notesForm").serialize();
                $.ajax({
                    url: $("#notesForm").attr("action"),
                    type: "POST",
                    data: formData,
                    dataType: "json",
                    success: function (response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Nota creada',
                                text: response.message
                            }).then(() => {
                                window.location.href = "index.php?controller=notes&action=index";
                            });
                        } else {
                            // Si las credenciales son incorrectas u ocurre otro error, se muestra el error sin reiniciar el formulario
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    },
                    error: function () {
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