<!-- views/cart_modal.php
<h2 class="text-3xl font-bold text-white mb-4 text-center">Tu Carrito</h2>
<?php if(!empty($cartItems)): ?>
<table class="w-full bg-white rounded-lg shadow-md mb-4">
    <thead>
        <tr class="bg-gray-200">
            <th class="py-2 px-3 text-left">Producto</th>
            <th class="py-2 px-3 text-left">Cantidad</th>
            <th class="py-2 px-3 text-left">Precio</th>
            <th class="py-2 px-3 text-left">Total</th>
            <th class="py-2 px-3 text-left">Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php 
            $grandTotal = 0;
            foreach($cartItems as $item):
                $total = $item['price'] * $item['quantity'];
                $grandTotal += $total;
        ?>
        <tr class="border-t">
            <td class="py-2 px-3"><?= $item['name'] ?></td>
            <td class="py-2 px-3"><?= $item['quantity'] ?></td>
            <td class="py-2 px-3">$<?= $item['price'] ?></td>
            <td class="py-2 px-3">$<?= number_format($total, 2) ?></td>
            <td class="py-2 px-3">
                <a href="index.php?controller=cart&action=remove&id=<?= $item['id'] ?>" class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 text-sm">
                    Eliminar
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<div class="text-right">
    <p class="text-xl font-semibold text-white">Total a pagar: $<?= number_format($grandTotal, 2) ?></p>
</div>
<?php else: ?>
<p class="text-center text-white">Tu carrito está vacío.</p>
<?php endif; ?> -->
