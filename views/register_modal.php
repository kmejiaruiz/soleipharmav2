<!-- views/register_modal.php -->
<?php if (session_status() === PHP_SESSION_NONE)
    session_start(); ?>

<h2 class="text-3xl font-bold text-white mb-4 text-center">Registrarse</h2>

<div id="registerAlert" class="hidden p-2 rounded mb-4 text-center"></div>

<form id="registerForm" method="POST" class="space-y-4">
    <div>
        <label for="first_name1" class="block text-white font-semibold">Primer Nombre</label>
        <input type="text" name="first_name1" id="first_name1" required
            class="w-full px-4 py-2 rounded focus:outline-none focus:ring focus:border-purple-300"
            placeholder="Primer nombre">
    </div>
    <div>
        <label for="first_name2" class="block text-white font-semibold">Segundo Nombre</label>
        <input type="text" name="first_name2" id="first_name2" required
            class="w-full px-4 py-2 rounded focus:outline-none focus:ring focus:border-purple-300"
            placeholder="Segundo nombre">
    </div>
    <div>
        <label for="last_name1" class="block text-white font-semibold">Primer Apellido</label>
        <input type="text" name="last_name1" id="last_name1" required
            class="w-full px-4 py-2 rounded focus:outline-none focus:ring focus:border-purple-300"
            placeholder="Primer apellido">
    </div>
    <div>
        <label for="last_name2" class="block text-white font-semibold">Segundo Apellido</label>
        <input type="text" name="last_name2" id="last_name2" required
            class="w-full px-4 py-2 rounded focus:outline-none focus:ring focus:border-purple-300"
            placeholder="Segundo apellido">
    </div>
    <div>
        <label for="password" class="block text-white font-semibold">Contraseña</label>
        <input type="password" name="password" id="password" required
            class="w-full px-4 py-2 rounded focus:outline-none focus:ring focus:border-purple-300"
            placeholder="Elige una contraseña segura">
    </div>
    <div class="flex justify-end">
        <button type="submit" id="registerBtn"
            class="w-full py-2 bg-green-500 text-white font-bold rounded hover:bg-green-600 transition-colors">
            Registrarse
        </button>
    </div>
</form>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
    $(function () {
        $("#registerForm").on("submit", function (e) {
            e.preventDefault();
            $("#registerBtn").prop("disabled", true).text("Espere por favor...");
            $("#registerAlert").hide();

            $.ajax({
                url: "<?= APP_BASE ?>/auth/registerAjax",
                type: "POST",
                data: $(this).serialize(),
                dataType: "json"
            })
                .done(function (res) {
                    const alert = $("#registerAlert");
                    if (res.success) {
                        alert
                            .removeClass("hidden bg-red-400")
                            .addClass("bg-blue-500 text-white")
                            .html("Registro exitoso. Tu usuario es: <strong>" +
                                $('<div>').text(res.username).html() + "</strong>. No comparta su usuario con nadie.")
                            .show();
                        $("#registerForm")[0].reset();
                    } else {
                        alert
                            .removeClass("hidden bg-blue-500")
                            .addClass("bg-red-400 text-white")
                            .text(res.message)
                            .show();
                    }
                })
                .fail(function (jqXHR, textStatus, errorThrown) {
                    console.error("AJAX ERROR:", textStatus, errorThrown, jqXHR.responseText);
                    $("#registerAlert")
                        .removeClass("hidden bg-blue-500")
                        .addClass("bg-red-400 text-white")
                        .text("Error de comunicación con el servidor.")
                        .show();
                })
                .always(function () {
                    $("#registerBtn").prop("disabled", false).text("Registrarse");
                });
        });
    });
</script>