<?php
session_start();
require_once 'config/database.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    // Opcional: redirigir al login o mostrar un mensaje
    // header("Location: login.php");
    // exit();
}

$database = new Database();
$db = $database->getConnection();

// Lógica para obtener productos del carrito (se hará principalmente con JS)
// Esta parte PHP es más para la estructura inicial o si se necesita persistencia en el servidor
function agregar_producto_al_carrito($id_producto, $cantidad) {
  if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = array();
  }
  $_SESSION['carrito'][$id_producto] = $cantidad;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Carrito - Tienda Juancho & Sofi</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/carrito.css">
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
            <div class="checkout-progress">
                <div class="progress-step active">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Carrito</span>
                </div>
                <div class="progress-step">
                    <i class="fas fa-credit-card"></i>
                    <span>Pago</span>
                </div>
                <div class="progress-step">
                    <i class="fas fa-check-circle"></i>
                    <span>Confirmación</span>
                </div>
            </div>
        </nav>
    </header>

    <main class="main-content" style="margin-top: 100px;">
        <div class="container">
            <div class="cart-header">
                <h1>Mi Carrito de Compras</h1>
                <p>Revisa tus productos antes de finalizar la compra.</p>
            </div>

            <div class="cart-content">
                <!-- Lista de Productos en el Carrito -->
                <div class="cart-items-container">
                    <div id="cart-items">
                        <!-- Los productos del carrito se cargarán aquí con JavaScript -->
                        <div class="empty-cart-message" style="display: none;">
                            <i class="fas fa-shopping-cart"></i>
                            <h3>Tu carrito está vacío</h3>
                            <p>Parece que aún no has añadido nada a tu carrito. ¡Explora nuestros productos!</p>
                            <a href="index.php#productos" class="btn btn-primary">Ir a Comprar</a>
                        </div>
                    </div>
                </div>

                <!-- Resumen del Carrito -->
                <div class="cart-summary">
                    <div class="summary-card">
                        <h3>Resumen del Pedido</h3>
                        <div class="summary-details">
                            <div class="summary-row">
                                <span>Subtotal (<span id="total-items-summary">0</span> productos)</span>
                                <span id="cart-subtotal">$0</span>
                            </div>
                            <div class="summary-row">
                                <span>Descuentos</span>
                                <span id="cart-descuentos">-$0</span>
                            </div>
                            <div class="summary-row total">
                                <span>Total</span>
                                <span id="cart-total">$0</span>
                            </div>
                        </div>
                        <button id="checkout-btn" class="btn btn-primary btn-block" disabled>
                            <i class="fas fa-credit-card"></i> Proceder al Pago
                        </button>
                        <a href="index.php" class="btn btn-outline btn-block">
                            <i class="fas fa-arrow-left"></i> Seguir Comprando
                        </a>
                    </div>

                    <div class="security-info">
                        <i class="fas fa-lock"></i>
                        <span>Compra 100% segura y protegida.</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer (opcional, si se desea un footer diferente para el carrito) -->
    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Tienda Juancho & Sofi. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="js/main.js"></script>
    <script src="js/carrito.js"></script>
</body>
</html>
