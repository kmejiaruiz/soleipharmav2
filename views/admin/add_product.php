<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Agregar Producto</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="/soleipharmav2/admin/index">Dashboard</a></li>
                    <li class="breadcrumb-item active">Agregar Producto</li>
                </ol>
            </div>
        </div>
    </div>
</section>
<section class="content">
    <div class="container-fluid">
        <form id="addProductForm" action="/soleipharmav2/admin/saveProduct" method="post">
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <textarea name="description" class="form-control" required></textarea>
            </div>
            <div class="form-group">
                <label>Costo (Compra)</label>
                <input type="number" step="0.01" name="cost" id="cost" class="form-control" required value="0.00">
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>% Utilidad</label>
                        <input type="number" step="0.01" name="utility_percent" id="utility_percent" class="form-control" required value="30.00">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>% Impuesto</label>
                        <input type="number" step="0.01" name="tax_percent" id="tax_percent" class="form-control" required value="15.00">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Precio Venta (Calculado: Costo + Utilidad)</label>
                <input type="number" step="0.01" name="price" id="price" class="form-control" required readonly>
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

<!-- Incluir jQuery (Micromodal JS ya se incluye globalmente en admin_footer) -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
document.getElementById('available').addEventListener('change', function() {
    var reasonGroup = document.getElementById('reasonGroup');
    reasonGroup.style.display = this.checked ? 'none' : 'block';
});

// Interceptar submit para abrir el modal
$("#addProductForm").on('submit', function(e) {
    e.preventDefault();
    window.ActionModal.show({
        title: 'Confirmar Acción',
        description: 'Ingrese sus credenciales para confirmar:',
        fields: [
            { id: 'modal-input-username', type: 'text', placeholder: 'Usuario' },
            { id: 'modal-input-password', type: 'password', placeholder: 'Contraseña' }
        ],
        onConfirm: function(data) {
            const username = data['modal-input-username'] ? data['modal-input-username'].trim() : '';
            const password = data['modal-input-password'] ? data['modal-input-password'].trim() : '';
            
            if (!username || !password) {
                window.ActionModal.showError('Debe ingresar usuario y contraseña');
                return;
            }
            
            window.ActionModal.hide();
            
            // Asignar al form
            $("#confirmUsername").val(username);
            $("#confirmPassword").val(password);
            
            // Enviar AJAX
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
                            window.location.href = "/soleipharmav2/admin/index";
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
<script>
    // Auto-calculo de precio
    function calculatePrice() {
        const cost = parseFloat($('#cost').val()) || 0;
        const utility = parseFloat($('#utility_percent').val()) || 0;
        // Precio base = Costo * (1 + Utilidad)
        const price = cost * (1 + (utility / 100));
        $('#price').val(price.toFixed(2));
    }
    
    $('#cost, #utility_percent').on('input', calculatePrice);
    
    // Calcular inicial
    calculatePrice();
</script>