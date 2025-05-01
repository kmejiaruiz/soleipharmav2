<?php if(session_status() === PHP_SESSION_NONE) session_start(); ?>
<!— Incluir jQuery y SweetAlert2 —>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<h1>Solicitar Descarte</h1>

<form id="formDiscard" method="POST" action="index.php?controller=discard&action=request">
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
      $.ajax({
        url: $form.attr('action'),
        type: 'POST',
        data: $form.serialize(),
        dataType: 'json',
        success: function(data) {
          Swal.fire({
            icon: data.success ? 'success' : 'error',
            title: data.message,
            timer: 3000,
            showConfirmButton: false
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
        }
      });
    });
  });
</script>


<script>
$('#formDiscard').submit(function(e){
  e.preventDefault();
  $.post('index.php?controller=discard&action=request', $(this).serialize(), data=>{
    Swal.fire(data.success?'success':'error', data.message);
  }, 'json');
});
</script>