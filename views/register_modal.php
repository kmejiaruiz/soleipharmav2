<!-- views/register_modal.php -->
<h2 class="text-3xl font-bold text-white mb-4 text-center">Registrarse</h2>
<?php if(isset($error)): ?>
    <div class="bg-red-400 text-white p-2 rounded mb-4 text-center"><?= $error ?></div>
<?php endif; ?>
<form action="index.php?controller=auth&action=register" method="POST" class="space-y-4">
    <div>
        <label for="username" class="block text-white font-semibold">Usuario</label>
        <input type="text" name="username" id="username" required class="w-full px-4 py-2 rounded focus:outline-none focus:ring focus:border-purple-300">
    </div>
    <div>
        <label for="password" class="block text-white font-semibold">Contraseña</label>
        <input type="password" name="password" id="password" required class="w-full px-4 py-2 rounded focus:outline-none focus:ring focus:border-purple-300">
    </div>
    <div class="flex justify-end">
        <button type="submit" class="w-full py-2 bg-green-500 text-white font-bold rounded hover:bg-green-600 transition-colors">
            Registrarse
        </button>
    </div>
</form>
