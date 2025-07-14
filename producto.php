<?php
session_start();
require_once 'config/database.php';

$producto_id = $_GET['id'] ?? 0;
if (!$producto_id) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Obtener producto
$stmt = $db->prepare("
    SELECT p.*, c.nombre as categoria_nombre 
    FROM productos p 
    LEFT JOIN categorias c ON p.id_categoria = c.id 
    WHERE p.id = ?
");
$stmt->execute([$producto_id]);
$producto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$producto) {
    header("Location: index.php");
    exit();
}

// Obtener productos relacionados
$stmt = $db->prepare("
    SELECT * FROM productos 
    WHERE id_categoria = ? AND id != ? 
    ORDER BY RAND() 
    LIMIT 4
");
$stmt->execute([$producto['id_categoria'], $producto_id]);
$productos_relacionados = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($producto['nombre']); ?> - Tienda Juancho & Sofi</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/producto.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar">
            <div class="nav-brand">
                <a href="index.php">
                    <img src="Logo/Logo juancho.png" alt="Logo" class="logo">
                </a>
            </div>
            
            <ul class="nav-menu">
                <li><a href="index.php">Inicio</a></li>
                <li><a href="index.php#productos">Productos</a></li>
                <li><a href="#servicio-tecnico">Servicio Técnico</a></li>
                <li><a href="#colecciones">Colecciones</a></li>
                <li><a href="#ofertas">Ofertas</a></li>
            </ul>
            
            <div class="nav-icons">
                <a href="#" class="nav-icon search-toggle"><i class="fas fa-search"></i></a>
                <a href="carrito.php" class="nav-icon cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-badge">0</span>
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="perfil.php" class="nav-icon"><i class="fas fa-user"></i></a>
                    <a href="logout.php" class="nav-icon"><i class="fas fa-sign-out-alt"></i></a>
                <?php else: ?>
                    <a href="login.php" class="nav-icon"><i class="fas fa-user"></i></a>
                <?php endif; ?>
            </div>
        </nav>
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="Buscar productos...">
            <button id="searchButton"><i class="fas fa-search"></i></button>
        </div>
    </header>

    <main class="main-content" style="margin-top: 100px;">
        <div class="container">
            <!-- Breadcrumb -->
            <nav class="breadcrumb">
                <a href="index.php">Inicio</a>
                <span>/</span>
                <a href="categoria.php?id=<?php echo $producto['id_categoria']; ?>"><?php echo htmlspecialchars($producto['categoria_nombre']); ?></a>
                <span>/</span>
                <span><?php echo htmlspecialchars($producto['nombre']); ?></span>
            </nav>

            <!-- Producto Principal -->
            <div class="product-detail">
                <div class="product-gallery">
                    <div class="main-image">
                        <img id="main-product-image" src="images/productos/<?php echo $producto['imagen']; ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                        <?php if ($producto['descuento'] > 0): ?>
                            <div class="discount-badge">-<?php echo $producto['descuento']; ?>%</div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Thumbnails (simuladas) -->
                    <div class="image-thumbnails">
                        <img src="images/productos/<?php echo $producto['imagen']; ?>" alt="Vista 1" class="thumbnail active" onclick="changeMainImage(this.src)">
                        <img src="images/productos/<?php echo $producto['imagen']; ?>" alt="Vista 2" class="thumbnail" onclick="changeMainImage(this.src)">
                        <img src="images/productos/<?php echo $producto['imagen']; ?>" alt="Vista 3" class="thumbnail" onclick="changeMainImage(this.src)">
                    </div>
                </div>

                <div class="product-info">
                    <div class="product-brand"><?php echo htmlspecialchars($producto['marcas']); ?></div>
                    <h1 class="product-title"><?php echo htmlspecialchars($producto['nombre']); ?></h1>
                    
                    <div class="product-rating">
                        <div class="stars">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                        <span class="rating-text">(4.5) - 23 reseñas</span>
                    </div>

                    <div class="product-price">
                        <?php if ($producto['descuento'] > 0): ?>
                            <?php 
                            $precio_original = $producto['precio_unitario'];
                            $precio_descuento = $precio_original - ($precio_original * $producto['descuento'] / 100);
                            ?>
                            <span class="price-original">$<?php echo number_format($precio_original); ?></span>
                            <span class="price-discount">$<?php echo number_format($precio_descuento); ?></span>
                            <span class="savings">Ahorras $<?php echo number_format($precio_original - $precio_descuento); ?></span>
                        <?php else: ?>
                            <span class="price-current">$<?php echo number_format($producto['precio_unitario']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="product-description">
                        <p><?php echo htmlspecialchars($producto['descripcion']); ?></p>
                    </div>

                    <?php if (!empty($producto['tallas'])): ?>
                    <div class="product-options">
                        <label>Tallas Disponibles:</label>
                        <div class="size-options">
                            <?php 
                            $tallas = explode(',', $producto['tallas']);
                            foreach ($tallas as $talla): 
                            ?>
                                <button class="size-option" onclick="selectSize(this)"><?php echo trim($talla); ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($producto['colores'])): ?>
                    <div class="product-options">
                        <label>Colores Disponibles:</label>
                        <div class="color-options">
                            <?php 
                            $colores = explode(',', $producto['colores']);
                            foreach ($colores as $color): 
                            ?>
                                <button class="color-option" onclick="selectColor(this)"><?php echo trim($color); ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="product-stock">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo $producto['cantidad']; ?> unidades disponibles</span>
                    </div>

                    <div class="product-actions">
                        <div class="quantity-selector">
                            <button class="qty-btn" onclick="changeQuantity(-1)">-</button>
                            <input type="number" id="quantity" value="1" min="1" max="<?php echo $producto['cantidad']; ?>">
                            <button class="qty-btn" onclick="changeQuantity(1)">+</button>
                        </div>
                        
                        <button class="btn btn-primary btn-add-cart" onclick="addToCartFromDetail(<?php echo $producto['id']; ?>)">
                            <i class="fas fa-shopping-cart"></i>
                            Agregar al Carrito
                        </button>
                        
                        <button class="btn btn-secondary btn-wishlist" onclick="addToWishlist(<?php echo $producto['id']; ?>)">
                            <i class="fas fa-heart"></i>
                            Favoritos
                        </button>
                    </div>

                    <div class="product-features">
                        <div class="feature">
                            <i class="fas fa-truck"></i>
                            <div>
                                <strong>Envío Gratis</strong>
                                <small>En compras mayores a $100,000</small>
                            </div>
                        </div>
                        <div class="feature">
                            <i class="fas fa-shield-alt"></i>
                            <div>
                                <strong>Garantía</strong>
                                <small>12 meses de garantía</small>
                            </div>
                        </div>
                        <div class="feature">
                            <i class="fas fa-undo"></i>
                            <div>
                                <strong>Devoluciones</strong>
                                <small>30 días para devoluciones</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Productos Relacionados -->
            <?php if (!empty($productos_relacionados)): ?>
            <section class="related-products">
                <h2>Productos Relacionados</h2>
                <div class="products-grid">
                    <?php foreach ($productos_relacionados as $relacionado): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <a href="producto.php?id=<?php echo $relacionado['id']; ?>">
                                <img src="images/productos/<?php echo $relacionado['imagen']; ?>" alt="<?php echo htmlspecialchars($relacionado['nombre']); ?>">
                            </a>
                            <?php if ($relacionado['descuento'] > 0): ?>
                                <div class="discount-badge">-<?php echo $relacionado['descuento']; ?>%</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-info">
                            <div class="product-brand"><?php echo htmlspecialchars($relacionado['marcas']); ?></div>
                            <h3 class="product-name">
                                <a href="producto.php?id=<?php echo $relacionado['id']; ?>">
                                    <?php echo htmlspecialchars($relacionado['nombre']); ?>
                                </a>
                            </h3>
                            
                            <div class="product-price">
                                <?php if ($relacionado['descuento'] > 0): ?>
                                    <?php 
                                    $precio_orig = $relacionado['precio_unitario'];
                                    $precio_desc = $precio_orig - ($precio_orig * $relacionado['descuento'] / 100);
                                    ?>
                                    <span class="price-original">$<?php echo number_format($precio_orig); ?></span>
                                    <span class="price-discount">$<?php echo number_format($precio_desc); ?></span>
                                <?php else: ?>
                                    <span class="price-current">$<?php echo number_format($relacionado['precio_unitario']); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-actions">
                                <button class="btn btn-primary" onclick="addToCart(<?php echo $relacionado['id']; ?>)">
                                    <i class="fas fa-shopping-cart"></i> Agregar
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
        </div>
    </main>

    <script src="js/main.js"></script>
    <script src="js/producto.js"></script>
</body>
</html>
