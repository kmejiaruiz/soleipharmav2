<!-- views/admin/credit_debit_notes.php -->
<section class="content-header">
  <div class="container-fluid">
    <h1>Notas de Crédito / Débito</h1>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <!-- Formulario para emitir notas -->
    <form method="post" action="index.php?controller=notes&action=emit">
      <div class="form-group">
        <label for="noteType">Tipo de Nota</label>
        <select id="noteType" name="noteType" class="form-control" required>
          <option value="credit">Crédito</option>
          <option value="debit">Débito</option>
        </select>
      </div>
      <div class="form-group">
        <label for="description">Descripción</label>
        <textarea id="description" name="description" class="form-control" required></textarea>
      </div>
      <!-- Agrega más campos según se requiera -->
      <button type="submit" class="btn btn-primary">Emitir Nota</button>
    </form>
  </div>
</section>
