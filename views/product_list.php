<main class="container mx-auto py-6">
    <!-- Carousel con Splide -->
    <?php if(isset($slides) && is_array($slides) && count($slides) > 0): ?>
    <section class="mb-8">
        <div id="splide-carousel" class="splide">
            <div class="splide__track">
                <ul class="splide__list">
                    <?php foreach($slides as $slide): ?>
                    <li class="splide__slide">
                        <img src="<?= htmlspecialchars($slide['image']) ?>"
                            alt="<?= htmlspecialchars($slide['title'] ?? '') ?>">
                        <div class="carousel-overlay">
                            <?php if(!empty($slide['title'])): ?>
                            <h2 class="text-xl font-bold"><?= htmlspecialchars($slide['title']) ?></h2>
                            <?php endif; ?>
                            <?php if(!empty($slide['description'])): ?>
                            <p class="text-sm"><?= htmlspecialchars($slide['description']) ?></p>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Listado de Productos -->
    <h1 class="text-3xl font-bold mb-6 text-center">Productos</h1>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
        <?php foreach($products as $product): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <img src="assets/img/<?= htmlspecialchars($product['image']) ?>"
                alt="<?= htmlspecialchars($product['name']) ?>" class="w-full h-48 object-cover">
            <div class="p-4">
                <h3 class="font-bold text-lg"><?= htmlspecialchars($product['name']) ?></h3>
                <p class="text-gray-700 mt-2">$<?= number_format($product['price'], 2) ?></p>
                <p class="text-gray-600 mt-1">Stock: <?= $product['stock'] ?></p>
                <?php 
              $disabled = false;
              $message = "";
              if ($product['stock'] == 0) {
                  $disabled = true;
                  $message = "StockOut";
              }
              if ($product['available'] == 0) {
                  $disabled = true;
                  $message = "No disponible";
              }
            ?>
                <?php if($disabled): ?>
                <p class="text-red-600 font-bold"><?= $message ?></p>
                <a href="#" class="mt-4 inline-block bg-gray-400 text-white px-4 py-2 rounded cursor-not-allowed"
                    onclick="return false;">Ver detalle</a>
                <?php else: ?>
                <a href="index.php?controller=product&action=detail&id=<?= $product['id'] ?>"
                    class="mt-4 inline-block bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Ver
                    detalle</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Paginación con Tailwind -->
    <nav class="mt-8 flex justify-center">
        <ul class="flex space-x-2">
            <?php for($i = 1; $i <= $totalPages; $i++): ?>
            <li>
                <a href="index.php?controller=product&action=index&page=<?= $i ?>" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-200 
               <?= ($i == $currentPage) ? 'bg-blue-500 text-white border-blue-500' : '' ?>">
                    <?= $i ?>
                </a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>

</main>


<script>
document.addEventListener('DOMContentLoaded', function() {
    new Splide('#splide-carousel', {
        type: 'loop',
        perPage: 1,
        autoplay: true,
        interval: 5000,
    }).mount();
});
</script>