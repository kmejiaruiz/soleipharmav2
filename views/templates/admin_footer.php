</div>
  <!-- /.content-wrapper -->
  <footer class="main-footer" style="background-color: #4B0082; color: #fff;">
    <div class="float-right d-none d-sm-block">
      <b>Version</b> 1.0
    </div>
    <strong>&copy; <?= date('Y') ?> Mi Tienda Online.</strong> Todos los derechos reservados.
  </footer>
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.1/dist/js/adminlte.min.js"></script>
<!-- Scripts personalizados -->
<script src="assets/js/main.js"></script>
<script>
 $(window).on('load', function() {
      $('#loading-wrapper').slideUp(250, function(){
          $(this).remove();
      });
  });
</script>
</body>
</html>
