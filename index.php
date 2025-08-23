<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Obtener categorías
$stmt_categorias = $db->prepare("SELECT * FROM categorias ORDER BY nombre ASC");
$stmt_categorias->execute();
$categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

// Obtener productos destacados (ej. con descuento o los más recientes)
$stmt_destacados = $db->prepare("SELECT * FROM productos WHERE descuento > 0 ORDER BY descuento DESC LIMIT 8");
$stmt_destacados->execute();
$productos_destacados = $stmt_destacados->fetchAll(PDO::FETCH_ASSOC);

// Obtener productos por categoría para la sección "Explorar por Categoría"
$productos_por_categoria = [];
foreach ($categorias as $categoria) {
    $stmt_productos_cat = $db->prepare("SELECT * FROM productos WHERE id_categoria = ? ORDER BY fecha_creacion DESC LIMIT 4");
    $stmt_productos_cat->execute([$categoria['id']]);
    $productos_por_categoria[$categoria['nombre']] = $stmt_productos_cat->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda Juancho & Sofi - Tecnología y Accesorios</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar">
            <div class="nav-brand">
                <a href="index.php">
                    <img src="Logo/Logo juancho.png" alt="Logo" class="logo" >
                </a>
            </div>
            
            <ul class="nav-menu">
                <li><a href="index.php">Inicio</a></li>
                <li><a href="#productos">Productos</a></li>
                <li><a href="#servicio-tecnico">Servicio Técnico</a></li>
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Tecnología de Vanguardia a tu Alcance</h1>
            <p>Descubre los últimos gadgets, componentes y accesorios para potenciar tu vida digital.</p>
            <a href="#productos" class="btn btn-primary">Explorar Productos</a>
        </div>
    </section>

    <main class="main-content">
        <div class="container">
            <!-- Categorías Destacadas -->
            <section class="featured-categories">
                <h2>Explora Nuestras Categorías</h2>
                <div class="categories-grid">
                    <?php foreach ($categorias as $categoria): ?>
                    <a href="categoria.php?id=<?php echo $categoria['id']; ?>" class="category-card">
                        <img src="images/categorias/<?php echo htmlspecialchars($categoria['imagen']); ?>" alt="<?php echo htmlspecialchars($categoria['nombre']); ?>" onerror="this.src='/placeholder.svg?height=100&width=100'">
                        <h3><?php echo htmlspecialchars($categoria['nombre']); ?></h3>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Productos Destacados / Ofertas -->
            <section id="productos" class="featured-products">
                <h2>Productos Destacados</h2>
                <div class="products-grid">
                    <?php if (!empty($productos_destacados)): ?>
                        <?php foreach ($productos_destacados as $producto): ?>
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
                                    <a href="producto.php?id=<?php echo $producto['id']; ?>" class="btn btn-primary"><i class="fas fa-shopping-cart"></i> Agregar</a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="empty-state-text">No hay productos destacados en este momento.</p>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Sección de Servicios (Ejemplo) -->
            <section id="servicio-tecnico" class="services-section">
                <h2>Nuestros Servicios</h2>
                <div class="services-grid">
                    <div class="service-card">
                        <i class="fas fa-tools"></i>
                        <h3>Servicio Técnico</h3>
                        <p>Reparación y mantenimiento de equipos electrónicos.</p>
                    </div>
                    <div class="service-card">
                        <i class="fas fa-headset"></i>
                        <h3>Soporte 24/7</h3>
                        <p>Asistencia técnica y resolución de dudas en todo momento.</p>
                    </div>
                    <div class="service-card">
                        <i class="fas fa-truck-fast"></i>
                        <h3>Envíos Rápidos</h3>
                        <p>Entregas eficientes a nivel nacional.</p>
                    </div>
                </div>
            </section>

            <!-- Productos por Categoría (Ejemplo de cómo se mostrarían) -->
            <?php foreach ($productos_por_categoria as $cat_nombre => $prods): ?>
                <?php if (!empty($prods)): ?>
                <section class="category-products-section">
                    <h2><?php echo htmlspecialchars($cat_nombre); ?></h2>
                    <div class="products-grid">
                        <?php foreach ($prods as $producto): ?>
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
                                        <a href="producto.php?id=<?php echo $producto['id']; ?>" class="btn btn-primary"><i class="fas fa-shopping-cart"></i> Agregar</a>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-4">
                        <a href="categoria.php?id=<?php echo $categoria['id']; ?>" class="btn btn-outline">Ver más de <?php echo htmlspecialchars($cat_nombre); ?></a>
                    </div>
                </section>
                <?php endif; ?>
            <?php endforeach; ?>

            <!-- Call to Action / Newsletter -->
            <section class="cta-section">
                <h2>¡No te pierdas nuestras ofertas!</h2>
                <p>Suscríbete a nuestro boletín y recibe las últimas noticias y promociones.</p>
                <form class="newsletter-form">
                    <input type="email" placeholder="Tu correo electrónico" required>
                    <button type="submit" class="btn btn-primary">Suscribirme</button>
                </form>
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
    <script>
        // Función para agregar al carrito (simulada, se conectará con la API)
        function addToCart(productId) {
            // Aquí iría la lógica para añadir el producto al carrito
            // Por ahora, solo una alerta
            showAlert(`Producto ${productId} agregado al carrito`, 'success');
            updateCartCounter(); // Actualizar el contador del carrito
        }
    </script>
</body>
</html>
