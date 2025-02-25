<h1>Iniciar Sesión</h1>

<?php if(isset($error)): ?>
  <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<form action="index.php?controller=auth&action=login" method="POST">
  <div class="form-group">
    <label for="username">Usuario</label>
    <input type="text" name="username" id="username" class="form-control" required>
  </div>
  <div class="form-group">
    <label for="password">Contraseña</label>
    <input type="password" name="password" id="password" class="form-control" required>
  </div>
  <button type="submit" class="btn btn-primary">Entrar</button>
</form>
