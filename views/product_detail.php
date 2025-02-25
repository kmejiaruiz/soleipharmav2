<?php if($product): ?>
<div class="flex flex-col md:flex-row bg-white rounded-lg shadow-md overflow-hidden">
    <div class="md:w-1/2">
        <img src="assets/img/<?= $product['image'] ?>" alt="<?= $product['name'] ?>" class="w-full h-full object-cover">
    </div>
    <div class="md:w-1/2 p-6">
        <h2 class="text-3xl font-bold text-gray-800"><?= $product['name'] ?></h2>
        <p class="mt-4 text-gray-700"><?= $product['description'] ?></p>
        <p class="mt-4 text-xl font-semibold text-gray-800">$<?= $product['price'] ?></p>
        <p class="mt-2 text-gray-600">Stock disponible: <?= $product['stock'] ?></p>

        <?php 
            $disabled = false;
            $msg = "";
            if ($product['stock'] == 0) {
                $disabled = true;
                $msg = "StockOut";
            }
            if ($product['available'] == 0) {
                $disabled = true;
                $msg = "Actualmente no está disponible el artículo.";
            }
        ?>
        <?php if($disabled): ?>
        <div class="bg-red-200 text-red-800 p-2 rounded mt-4"><?= $msg ?></div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
        <div class="bg-red-400 text-white p-2 rounded mb-4 text-center"><?= $error ?></div>
        <?php endif; ?>
        <form action="index.php?controller=cart&action=add" method="POST" class="mt-6">
            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
            <div>
                <label for="quantity" class="block text-gray-700">Cantidad:</label>
                <input type="number" name="quantity" id="quantity" value="1" min="1"
                    class="mt-1 w-20 px-3 py-2 border rounded focus:outline-none focus:ring focus:border-indigo-300"
                    <?= $disabled ? 'disabled' : '' ?>>
            </div>
            <button type="submit" class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700"
                <?= $disabled ? 'disabled' : '' ?>>
                Añadir al carrito
            </button>
        </form>
    </div>
</div>
<?php else: ?>
<p class="text-center text-red-500">Producto no encontrado.</p>
<?php endif; ?>