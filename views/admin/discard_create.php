<?php if(session_status() === PHP_SESSION_NONE) session_start(); ?>
<!— Incluir jQuery —>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<div class="row mb-2">
            <div class="col-sm-6">
                <h1>Solicitar Descarte</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/admin/index">Dashboard</a></li>
                    <li class="breadcrumb-item active">Solicitar Descarte</li>
                </ol>
            </div>
        </div>

<form id="formDiscard" method="POST" action="<?= APP_BASE ?>/discard/request">
  <div class="form-group">
    <label for="product_id">Producto:</label>
    <select name="product_id" id="product_id" class="form-control" required>
      <?php foreach($products as $p): ?>
        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group">
    <label for="quantity">Cantidad a descartar:</label>
    <input type="number" name="quantity" id="quantity" class="form-control" min="1" required>
  </div>
  <div class="form-group">
    <label for="reason">Razón del descarte:</label>
    <textarea name="reason" id="reason" class="form-control" rows="3" required></textarea>
  </div>
  <button type="submit" class="btn btn-warning">Enviar Solicitud</button>
</form>
<!-- jQuery necesario para AJAX -->
<script>
  $(document).ready(function() {
    $('#formDiscard').on('submit', function(e) {
      e.preventDefault();
      var $form = $(this);
      var $btn = $form.find('button[type="submit"]');
      var originalBtnText = $btn.text();

      // Deshabilitar botón y mostrar estado de carga para prevenir clics múltiples
      $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enviando...');

      $.ajax({
        url: $form.attr('action'),
        type: 'POST',
        data: $form.serialize(),
        dataType: 'json',
        success: function(data) {
          Swal.fire({
            icon: data.success ? 'success' : 'error',
            title: data.message || 'Solicitud enviada'
          });
          if (data.success) {
            $form[0].reset();
          }
        },
        error: function(xhr, status, error) {
          console.error('AJAX Error:', xhr.responseText);
          Swal.fire({
            icon: 'error',
            title: 'Error interno al enviar solicitud',
            text: xhr.responseText || status
          });
        },
        complete: function() {
            // Restaurar botón sea cual sea el resultado
            $btn.prop('disabled', false).text(originalBtnText);
        }
      });
    });
  });
</script>