<?php
session_start();
require_once 'config/database.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Obtener código de orden
$codigo_orden = $_GET['orden'] ?? '';
if (empty($codigo_orden)) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Obtener detalles de la orden
$stmt = $db->prepare("
    SELECT o.*, u.nombre as cliente_nombre, u.correo as cliente_correo 
    FROM ordenes o 
    JOIN usuarios u ON o.id_usuario = u.id 
    WHERE o.codigo = ? AND o.id_usuario = ?
");
$stmt->execute([$codigo_orden, $_SESSION['user_id']]);
$orden = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$orden) {
    header("Location: index.php");
    exit();
}

// Obtener items de la orden
$stmt = $db->prepare("
    SELECT v.*, p.nombre, p.imagen, p.precio_unitario 
    FROM ventas v 
    JOIN productos p ON v.id_producto = p.id 
    WHERE v.id_orden = ?
");
$stmt->execute([$orden['id']]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener información adicional del pedido desde la sesión
$info_adicional = $_SESSION['last_order'] ?? [];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido Confirmado - Tienda Juancho & Sofi</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/confirmacion.css">
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
                <div class="progress-step completed">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Carrito</span>
                </div>
                <div class="progress-step completed">
                    <i class="fas fa-credit-card"></i>
                    <span>Pago</span>
                </div>
                <div class="progress-step active">
                    <i class="fas fa-check-circle"></i>
                    <span>Confirmación</span>
                </div>
            </div>
        </nav>
    </header>

    <main class="main-content" style="margin-top: 100px;">
        <div class="container">
            <!-- Mensaje de Confirmación -->
            <div class="confirmation-header">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1>¡Pedido Confirmado!</h1>
                <p>Tu pedido ha sido procesado exitosamente</p>
                <div class="order-number">
                    <strong>Número de Pedido: <?php echo $codigo_orden; ?></strong>
                </div>
            </div>

            <div class="confirmation-content">
                <!-- Detalles del Pedido -->
                <div class="order-details">
                    <div class="details-card">
                        <h3><i class="fas fa-receipt"></i> Detalles del Pedido</h3>
                        
                        <div class="order-info">
                            <div class="info-row">
                                <span class="label">Fecha:</span>
                                <span class="value"><?php echo date('d/m/Y', strtotime($orden['fecha'])); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="label">Estado:</span>
                                <span class="value status-<?php echo strtolower($orden['estado']); ?>">
                                    <?php echo $orden['estado']; ?>
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="label">Tipo de Entrega:</span>
                                <span class="value">
                                    <?php echo $orden['tipo_venta'] === 'local' ? 'Recogida en Tienda' : 'Envío a Domicilio'; ?>
                                </span>
                            </div>
                            <?php if (isset($info_adicional['metodo_pago'])): ?>
                            <div class="info-row">
                                <span class="label">Método de Pago:</span>
                                <span class="value"><?php echo ucfirst($info_adicional['metodo_pago']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (isset($info_adicional['telefono'])): ?>
                            <div class="info-row">
                                <span class="label">Teléfono:</span>
                                <span class="value"><?php echo $info_adicional['telefono']; ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (isset($info_adicional['direccion']) && !empty($info_adicional['direccion'])): ?>
                            <div class="info-row">
                                <span class="label">Dirección:</span>
                                <span class="value"><?php echo htmlspecialchars($info_adicional['direccion']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="order-items">
                            <h4>Productos Pedidos:</h4>
                            <?php foreach ($items as $item): ?>
                            <div class="order-item">
                                <div class="item-image">
                                    <img src="images/productos/<?php echo $item['imagen']; ?>" alt="<?php echo htmlspecialchars($item['nombre']); ?>">
                                </div>
                                <div class="item-info">
                                    <div class="item-name"><?php echo htmlspecialchars($item['nombre']); ?></div>
                                    <div class="item-quantity">Cantidad: <?php echo $item['cantidad']; ?></div>
                                </div>
                                <div class="item-total">
                                    $<?php echo number_format($item['valor_total']); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="order-total">
                            <div class="total-row">
                                <span class="total-label">Total Pagado:</span>
                                <span class="total-amount">$<?php echo number_format($orden['total']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información Adicional -->
                <div class="additional-info">
                    <div class="info-card">
                        <h3><i class="fas fa-info-circle"></i> ¿Qué sigue?</h3>
                        
                        <?php if ($orden['tipo_venta'] === 'local'): ?>
                        <div class="next-steps">
                            <div class="step">
                                <i class="fas fa-clock"></i>
                                <div>
                                    <strong>Preparación</strong>
                                    <p>Tu pedido estará listo en aproximadamente 2 horas</p>
                                </div>
                            </div>
                            <div class="step">
                                <i class="fas fa-store"></i>
                                <div>
                                    <strong>Recogida</strong>
                                    <p>Puedes recoger tu pedido en nuestra tienda física</p>
                                </div>
                            </div>
                            <div class="step">
                                <i class="fas fa-phone"></i>
                                <div>
                                    <strong>Notificación</strong>
                                    <p>Te llamaremos cuando esté listo para recoger</p>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="next-steps">
                            <div class="step">
                                <i class="fas fa-box"></i>
                                <div>
                                    <strong>Preparación</strong>
                                    <p>Estamos preparando tu pedido para el envío</p>
                                </div>
                            </div>
                            <div class="step">
                                <i class="fas fa-truck"></i>
                                <div>
                                    <strong>Envío</strong>
                                    <p>Tu pedido será enviado en 1-3 días hábiles</p>
                                </div>
                            </div>
                            <div class="step">
                                <i class="fas fa-home"></i>
                                <div>
                                    <strong>Entrega</strong>
                                    <p>Recibirás tu pedido en la dirección indicada</p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="contact-card">
                        <h3><i class="fas fa-headset"></i> ¿Necesitas Ayuda?</h3>
                        <p>Si tienes alguna pregunta sobre tu pedido, no dudes en contactarnos:</p>
                        <div class="contact-methods">
                            <div class="contact-method">
                                <i class="fas fa-phone"></i>
                                <span>+57 300 123 4567</span>
                            </div>
                            <div class="contact-method">
                                <i class="fas fa-envelope"></i>
                                <span>info@tiendajuanchosofi.com</span>
                            </div>
                            <div class="contact-method">
                                <i class="fab fa-whatsapp"></i>
                                <span>WhatsApp: +57 300 123 4567</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="confirmation-actions">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> Volver al Inicio
                </a>
                <a href="mis-pedidos.php" class="btn btn-outline">
                    <i class="fas fa-list"></i> Ver Mis Pedidos
                </a>
                <button onclick="window.print()" class="btn btn-secondary">
                    <i class="fas fa-print"></i> Imprimir Pedido
                </button>
            </div>
        </div>
    </main>

    <script>
        // Limpiar datos del carrito y sesión
        localStorage.removeItem('carrito');
        sessionStorage.removeItem('orderData');
        
        // Animación de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.details-card, .info-card, .contact-card');
            elements.forEach((el, index) => {
                setTimeout(() => {
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, index * 200);
            });
        });
    </script>
</body>
</html>
