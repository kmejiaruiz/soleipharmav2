<!-- views/admin/admin_panel.php -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">Dashboard</h1>

            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <!-- Aquí se pueden agregar tarjetas de información, gráficos, etc. -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box" style="background-color: #6f42c1; color: #fff;">
                    <div class="inner">
                        <!-- <h3>150</h3> -->
                        <p>Ventas del Día</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <a href="index.php?controller=admin&action=salesReport" class="small-box-footer">
                        Más info <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            <div class="row">
                <!-- Ventas del Día -->
                <div class="col-lg-4 col-12">
                    <div class="small-box" style="background-color: #6f42c1; color: #fff;">
                        <div class="inner">
                            <!-- <h3><?= number_format($dailySales, 2) ?></h3> -->
                            <p>Ventas del Día</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <a href="index.php?controller=admin&action=salesReport" class="small-box-footer">
                            Más info <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>

                <!-- Top 10 Productos Más Vendidos -->
                <div class="col-lg-4 col-12">
                    <div class="small-box" style="background-color: #007bff; color: #fff;">
                        <div class="inner">
                            <?php if(!empty($topProducts)): 
          $topProduct = $topProducts[0];
        ?>
                            <!-- <h3><?= $topProduct['name'] ?> <sup
                                    style="font-size: 20px"><?= $topProduct['total_quantity'] ?> uds</sup></h3> -->
                            <p>Top 10 Productos Más Vendidos</p>
                            <?php else: ?>
                            <h3>0</h3>
                            <p>No hay datos</p>
                            <?php endif; ?>
                        </div>
                        <div class="icon">
                            <i class="fas fa-thumbs-up"></i>
                        </div>
                        <a href="index.php?controller=admin&action=topProducts" class="small-box-footer">
                            Más info <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>

                <!-- Productos con Bajo Stock -->
                <div class="col-lg-4 col-12">
                    <div class="small-box" style="background-color: #dc3545; color: #fff;">
                        <div class="inner">
                            <!-- <h3><?= count($lowStockProducts) ?></h3> -->
                            <p>Productos con Bajo Stock</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <a href="index.php?controller=admin&action=lowStock" class="small-box-footer">
                            Más info <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Agrega más tarjetas según se requiera -->
        </div>
        <div class="card col-md-12 col-12">
            <div class="card-header">
                <h3 class="card-title">Productos</h3>
                <div class="card-tools">
                    <a href="index.php?controller=admin&action=addProduct" class="btn btn-success btn-sm">Agregar
                        Producto</a>
                </div>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Disponible</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
        global $pdo;
        $stmt = $pdo->query("SELECT * FROM products");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($products as $product):
        ?>
                        <tr>
                            <td><?= $product['id'] ?></td>
                            <td><?= $product['name'] ?></td>
                            <td><?= $product['price'] ?></td>
                            <td><?= $product['stock'] ?></td>
                            <td><?= $product['available'] ? 'Sí' : 'No' ?></td>
                            <td>
                                <a href="index.php?controller=admin&action=editProduct&id=<?= $product['id'] ?>"
                                    class="btn btn-primary btn-sm">Editar</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>