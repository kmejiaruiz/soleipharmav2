</div>
<!-- /.content-wrapper -->
<footer class="main-footer" style="background-color: #4B0082; color: #fff;">
  <div class="float-right d-none d-sm-block">
    <b>Version</b> 1.109
  </div>
  <strong>&copy; <?= date('Y') ?> <?= COMPANY_NAME ?></strong> <?= BRANCH ?>
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
  (function () {
    const wrapper = document.getElementById('loading-wrapper');

    if (sessionStorage.getItem('appLoaded')) {
      wrapper.style.opacity = '0';
      wrapper.style.visibility = 'hidden';
      return;
    }
    sessionStorage.setItem('appLoaded', 'true');

    window.addEventListener('load', () => {
      setTimeout(() => {
        wrapper.style.opacity = '0';
        wrapper.style.transform = 'translateY(-20px)';
        setTimeout(() => wrapper.style.visibility = 'hidden', 500);
      }, 500);
    });
  })();
</script>


</body>

</html>