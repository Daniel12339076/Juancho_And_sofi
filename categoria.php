<?php
session_start();
require_once 'config/database.php';

$categoria_id = $_GET['id'] ?? 0;
if (!$categoria_id) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Obtener información de la categoría
$stmt_categoria = $db->prepare("SELECT * FROM categorias WHERE id = ?");
$stmt_categoria->execute([$categoria_id]);
$categoria = $stmt_categoria->fetch(PDO::FETCH_ASSOC);

if (!$categoria) {
    header("Location: index.php"); // Redirigir si la categoría no existe
    exit();
}

// Obtener productos de la categoría
$stmt_productos = $db->prepare("SELECT * FROM productos WHERE id_categoria = ? ORDER BY fecha_creacion DESC");
$stmt_productos->execute([$categoria_id]);
$productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($categoria['nombre']); ?> - Tienda Juancho & Sofi</title>
    <link rel="stylesheet" href="css/style.css">
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
                <span><?php echo htmlspecialchars($categoria['nombre']); ?></span>
            </nav>

            <section class="category-products-section">
                <h2>Productos de <?php echo htmlspecialchars($categoria['nombre']); ?></h2>
                <?php if (!empty($productos)): ?>
                <div class="products-grid">
                    <?php foreach ($productos as $producto): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <a href="producto.php?id=<?php echo $producto['id']; ?>">
                                    <img src="images/productos/<?php echo htmlspecialchars($producto['imagen']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>" onerror="this.src='/placeholder.svg?height=200&width=200'">
                                </a>
                                <?php if ($producto['descuento'] > 0): ?>
                                    <div class="discount-badge">-<?php echo htmlspecialchars($producto['descuento']); ?>%</div>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <div class="product-brand"><?php echo htmlspecialchars($producto['marcas']); ?></div>
                                <h3 class="product-name">
                                    <a href="producto.php?id=<?php echo $producto['id']; ?>">
                                        <?php echo htmlspecialchars($producto['nombre']); ?>
                                    </a>
                                </h3>
                                <div class="product-price">
                                    <?php if ($producto['descuento'] > 0): ?>
                                        <?php 
                                        $precio_original = $producto['precio_unitario'];
                                        $precio_descuento = $precio_original - ($precio_original * $producto['descuento'] / 100);
                                        ?>
                                        <span class="price-original">$<?php echo number_format($precio_original, 0, ',', '.'); ?></span>
                                        <span class="price-discount">$<?php echo number_format($precio_descuento, 0, ',', '.'); ?></span>
                                    <?php else: ?>
                                        <span class="price-current">$<?php echo number_format($producto['precio_unitario'], 0, ',', '.'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="product-actions">
                                    <button class="btn btn-primary" onclick="addToCart(<?php echo $producto['id']; ?>)">
                                        <i class="fas fa-shopping-cart"></i> Agregar
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                    <p class="empty-state-text">No hay productos en esta categoría.</p>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col about-us">
                    <h3>Sobre Nosotros</h3>
                    <p>Tienda Juancho & Sofi es tu destino para la mejor tecnología. Ofrecemos productos de calidad y un servicio excepcional.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h3>Enlaces Rápidos</h3>
                    <ul>
                        <li><a href="index.php">Inicio</a></li>
                        <li><a href="#productos">Productos</a></li>
                        <li><a href="#servicio-tecnico">Servicios</a></li>
                        <li><a href="#">Preguntas Frecuentes</a></li>
                        <li><a href="#">Política de Privacidad</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Contacto</h3>
                    <p><i class="fas fa-map-marker-alt"></i> Calle Ficticia 123, Ciudad, País</p>
                    <p><i class="fas fa-phone"></i> +57 300 123 4567</p>
                    <p><i class="fas fa-envelope"></i> info@tiendajuanchosofi.com</p>
                </div>
                <div class="footer-col">
                    <h3>Métodos de Pago</h3>
                    <div class="payment-icons">
                        <i class="fab fa-cc-visa"></i>
                        <i class="fab fa-cc-mastercard"></i>
                        <i class="fab fa-cc-paypal"></i>
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Tienda Juancho & Sofi. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>
