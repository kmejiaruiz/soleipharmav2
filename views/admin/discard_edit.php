<div class="row mb-2">
    <div class="col-sm-6">
        <h1>Corregir Solicitud de Descarte #<?= htmlspecialchars($request['id']) ?></h1>
    </div>
    <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/admin/index">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/discard/myHistory">Mis Descartes</a></li>
            <li class="breadcrumb-item active">Editar Descarte</li>
        </ol>
    </div>
</div>

<div class="container">
    <div class="card shadow-sm col-md-8 mx-auto">
        <div class="card-header bg-white border-bottom-0 pt-4 pb-2">
            <h3 class="card-title text-dark fw-bold mb-0">
                <i class="fas fa-exclamation-circle text-warning"></i> Solicitud En Revisión
            </h3>
        </div>
        <div class="card-body">
            <p class="text-muted">
                El administrador ha solicitado que corrijas esta solicitud antes de poder ser aprobada. 
                <br><strong>Motivo / Instrucción:</strong> <span class="text-danger"><?= htmlspecialchars($request['decision_reason']) ?></span>
            </p>
            <hr>
            <form id="edit-discard-form" action="<?= APP_BASE ?>/discard/editRequest" method="POST">
                <input type="hidden" name="id" value="<?= htmlspecialchars($request['id']) ?>">
                
                <div class="mb-3">
                    <label class="form-label">Producto a descartar</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($request['product_name']) ?>" disabled>
                </div>

                <div class="mb-3">
                    <label for="quantity" class="form-label">Cantidad a Descartes (Máx: <?= htmlspecialchars($request['current_stock']) ?> en stock)</label>
                    <input type="number" name="quantity" id="quantity" class="form-control" 
                           value="<?= htmlspecialchars($request['quantity']) ?>" 
                           min="1" max="<?= htmlspecialchars($request['current_stock']) ?>" required>
                    <small class="text-muted">Ajusta la cantidad solicitada para que no exceda el inventario real.</small>
                </div>

                <div class="mb-3">
                    <label for="reason" class="form-label">Razón del Descarte</label>
                    <textarea name="reason" id="reason" class="form-control" rows="3" required><?= htmlspecialchars($request['reason']) ?></textarea>
                    <small class="text-muted">Puedes detallar más la razón si se te ha solicitado.</small>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="<?= APP_BASE ?>/discard/myHistory" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" id="btn-submit-edit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Reenviar Solicitud
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
<script>
    $('#edit-discard-form').on('submit', function(e) {
        e.preventDefault();
        
        const $btn = $('#btn-submit-edit');
        const originalContent = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enviando...');

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                try {
                    let res = JSON.parse(response);
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Corregido!',
                            text: res.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = '<?= APP_BASE ?>/discard/myHistory';
                        });
                    } else {
                        Swal.fire('Error', res.message, 'error');
                        $btn.prop('disabled', false).html(originalContent);
                    }
                } catch(err) {
                    Swal.fire('Error', 'Hubo un error en el servidor.', 'error');
                    $btn.prop('disabled', false).html(originalContent);
                }
            },
            error: function() {
                Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
                $btn.prop('disabled', false).html(originalContent);
            }
        });
    });
</script>
