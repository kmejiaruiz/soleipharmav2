<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<!-- <script>
document.addEventListener("DOMContentLoaded", function() {
  // Comprueba si la página ya se cargó anteriormente
  if (localStorage.getItem("pageLoaded") === "true") {
    // Si ya se cargó, remueve el loader inmediatamente sin mostrar ningún efecto
    var loader = document.getElementById("loading-wrapper");
    if (loader) {
      loader.parentNode.removeChild(loader);
    }
  } else {
    // Si es la primera vez, espera a que la ventana se cargue completamente
    $(window).on('load', function() {
      $('#loading-wrapper').slideUp(0.99, function(){
          $(this).remove();
          // Marca en localStorage que la página ya se cargó
          localStorage.setItem("pageLoaded", "true");
      });
    });
  }
});
</script> -->
