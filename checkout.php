<?php
session_start();
require_once 'config/database.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$database = new Database();
$db = $database->getConnection();
$mensaje = '';
$error = '';
function obtenerusuario($db, $id_usuario) {
    $stmt = $db->prepare("SELECT nombre, correo, celular FROM usuarios WHERE id = ?");
    $stmt->execute([$id_usuario]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
$usuarios = obtenerusuario($db, $_SESSION['user_id']);
$correo = $usuarios['correo'] ?? '';
$correo = $_SESSION['correo'] ?? '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_usuario = $_SESSION['user_id'];
    $items_data = json_decode($_POST['items_data'] ?? '[]', true);
    $total_orden = $_POST['total_data'] ?? 0;
    $tipo_entrega = $_POST['tipo_entrega'] ?? 'online'; // 'online' o 'local'
    $direccion = $_POST['direccion'] ?? null;
    $metodo_pago = $_POST['metodo_pago'] ?? null;
    $telefono_contacto = $_POST['telefono'] ?? null;
    $notas = $_POST['notas'] ?? null;

    $server_calculated_total = 0; // Variable para almacenar el total calculado por el servidor
    $processed_items = []; // Para almacenar los ítems con precios validados por el servidor

    if (empty($items_data)) {
        $error = "No hay productos en el pedido.";
    } else {
        try {
            $db->beginTransaction();

            // Primera pasada: Validar stock y calcular el total en el servidor
            foreach ($items_data as $item) {
                $id_producto = $item['id'];
                $cantidad = $item['cantidad'];

                // Obtener precio actual del producto de la DB para asegurar consistencia
                $stmt_prod = $db->prepare("SELECT precio_unitario, cantidad FROM productos WHERE id = ?");
                $stmt_prod->execute([$id_producto]);
                $producto_db = $stmt_prod->fetch(PDO::FETCH_ASSOC);

                if (!$producto_db || $producto_db['cantidad'] < $cantidad) {
                    throw new Exception("Stock insuficiente para el producto: " . htmlspecialchars($item['nombre']));
                }
                
                // Usar el precio unitario de la base de datos para el cálculo del total
                $precio_unitario_db = $producto_db['precio_unitario'];
                $valor_total_item = $precio_unitario_db * $cantidad;
                $server_calculated_total += $valor_total_item;

                // Almacenar el ítem con el precio validado por el servidor para la segunda pasada
                $processed_items[] = [
                    'id' => $id_producto,
                    'cantidad' => $cantidad,
                    'precio_unitario_db' => $precio_unitario_db,
                    'valor_total_item' => $valor_total_item
                ];
            }

            if ($server_calculated_total <= 0) {
                throw new Exception("El total del pedido es inválido después de la verificación del servidor.");
            }

            // Generar código de orden único
            $codigo_orden = 'ORD' . strtoupper(uniqid());
            
            // Insertar en la tabla de órdenes con el total calculado por el servidor
            $stmt_orden = $db->prepare("INSERT INTO ordenes (id_usuario, codigo, total, estado, tipo_venta, direccion_envio, metodo_pago, telefono_contacto) VALUES (?, ?, ?, 'Solicitado', ?, ?, ?, ?)");
            $stmt_orden->execute([$id_usuario, $codigo_orden, $server_calculated_total, $tipo_entrega, $direccion, $metodo_pago, $telefono_contacto]);
            $id_orden = $db->lastInsertId();

            // Insertar en la tabla de ventas y actualizar stock
            foreach ($items_data as $item) {
                $id_producto = $item['id'];
                $cantidad = $item['cantidad'];
                // $precio_unitario_venta = $item['precio']; // ELIMINAR ESTA LÍNEA

                // Obtener precio actual del producto de la DB para asegurar consistencia
                $stmt_prod = $db->prepare("SELECT precio_unitario, cantidad FROM productos WHERE id = ?");
                $stmt_prod->execute([$id_producto]);
                $producto_db = $stmt_prod->fetch(PDO::FETCH_ASSOC);

                if (!$producto_db || $producto_db['cantidad'] < $cantidad) {
                    throw new Exception("Stock insuficiente para el producto: " . htmlspecialchars($item['nombre']));
                }
                
                // Asegúrate de que $precio_unitario_venta siempre venga de la base de datos
                $precio_unitario_venta = $producto_db['precio_unitario']; 
                
                $valor_total_item = $precio_unitario_venta * $cantidad;

                $stmt_venta = $db->prepare("INSERT INTO ventas (id_orden, id_producto, cantidad, precio_unitario, valor_total) VALUES (?, ?, ?, ?, ?)");
                $stmt_venta->execute([$id_orden, $id_producto, $cantidad, $precio_unitario_venta, $valor_total_item]);

                // Actualizar stock del producto
                $stmt_update_stock = $db->prepare("UPDATE productos SET cantidad = cantidad - ? WHERE id = ?");
                $stmt_update_stock->execute([$cantidad, $id_producto]);
            }

            $db->commit();
            $mensaje = "Pedido realizado exitosamente. Código de orden: " . $codigo_orden;

            // Guardar información adicional en sesión para la página de confirmación
            $_SESSION['last_order'] = [
                'codigo' => $codigo_orden,
                'total' => $server_calculated_total, // Usar el total calculado por el servidor
                'tipo_entrega' => $tipo_entrega,
                'direccion' => $direccion,
                'metodo_pago' => $metodo_pago,
                'telefono' => $telefono_contacto,
                'notas' => $notas
            ];

            // Redirigir a la página de confirmación
            header("Location: orden-confirmada.php?orden=" . $codigo_orden);
            exit();

        } catch (Exception $e) {
            $db->rollBack();
            $error = "Error al procesar el pedido: " . $e->getMessage();
            // Loggear el error detallado en un archivo de log en producción
        }
    }
}

// Borra los productos del carrito después de confirmar la compra
if (isset($_SESSION['carrito'])) {
  unset($_SESSION['carrito']);
}


// Si no es POST o hay un error, se muestra el formulario de checkout
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Tienda Juancho & Sofi</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/checkout.css">
    <link rel="stylesheet" href="css/alerts.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div id="alert-container"></div>
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
                <div class="progress-step active">
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
            <div class="checkout-header">
                <h1>Finalizar Compra</h1>
                <p>Completa tus datos para procesar el pedido.</p>
            </div>

            <div class="checkout-content">
                <!-- Formulario de Checkout -->
                <div class="checkout-form">
                    <form id="checkout-form" action="checkout.php" method="POST">
                        <!-- Campos ocultos para datos del carrito -->
                        <input type="hidden" name="items_data" id="items-data">
                        <input type="hidden" name="total_data" id="total-data">

                        <div class="form-section">
                            <h3><i class="fas fa-user"></i> Información de Contacto</h3>
                            <div class="form-group">
                                <label for="nombre">Nombre Completo</label>
                                <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="correo">Correo Electrónico</label>
                                <input type="email" id="correo" name="correo" value="<?php echo htmlspecialchars($_SESSION['correo'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="telefono">Teléfono de Contacto</label>
                                <input type="tel" id="telefono" name="telefono" value="<?=$_SESSION['celular']??'' ?>" placeholder="Ej: 3001234567" required>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3><i class="fas fa-truck"></i> Tipo de Entrega</h3>
                            <div class="radio-group">
                                <label class="radio-option">
                                    <input type="radio" name="tipo_entrega" value="online" checked>
                                    <span class="radio-custom"></span>
                                    <span class="radio-content">
                                        <strong><i class="fas fa-truck"></i> Envío a Domicilio</strong>
                                        <small>Recibe tu pedido en la dirección que indiques.</small>
                                    </span>
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="tipo_entrega" value="local">
                                    <span class="radio-custom"></span>
                                    <span class="radio-content">
                                        <strong><i class="fas fa-store"></i> Recogida en Tienda</strong>
                                        <small>Recoge tu pedido en nuestra tienda física (Gratis).</small>
                                    </span>
                                </label>
                            </div>
                            <div class="form-group" id="direccion-group" style="display: block; margin-top: 20px;">
                                <label for="direccion">Dirección de Envío</label>
                                <textarea id="direccion" name="direccion" rows="3" placeholder="Calle, número, barrio, ciudad..." required></textarea>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3><i class="fas fa-credit-card"></i> Método de Pago</h3>
                            <div class="radio-group">
                                <label class="radio-option">
                                    <input type="radio" name="metodo_pago" value="tarjeta" checked>
                                    <span class="radio-custom"></span>
                                    <span class="radio-content">
                                        <strong><i class="fas fa-credit-card"></i> Tarjeta de Crédito/Débito</strong>
                                        <small>Paga con Visa, MasterCard, American Express.</small>
                                    </span>
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="metodo_pago" value="pse">
                                    <span class="radio-custom"></span>
                                    <span class="radio-content">
                                        <strong><i class="fas fa-university"></i> PSE / Transferencia Bancaria</strong>
                                        <small>Paga directamente desde tu cuenta bancaria.</small>
                                    </span>
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="metodo_pago" value="efectivo">
                                    <span class="radio-custom"></span>
                                    <span class="radio-content">
                                        <strong><i class="fas fa-money-bill-wave"></i> Pago en Efectivo (Recogida Local)</strong>
                                        <small>Solo disponible para recogida en tienda.</small>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3><i class="fas fa-clipboard"></i> Notas Adicionales</h3>
                            <div class="form-group">
                                <label for="notas">¿Alguna instrucción especial para tu pedido?</label>
                                <textarea id="notas" name="notas" rows="3" placeholder="Ej: Entregar después de las 5 PM, dejar en portería..."></textarea>
                            </div>
                        </div>

                        <div class="form-actions">
                            <a href="carrito.php" class="btn btn-outline">
                                <i class="fas fa-arrow-left"></i> Volver al Carrito
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check-circle"></i> Confirmar Pedido
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Resumen del Pedido -->
                <div class="order-summary">
                    <div class="summary-card">
                        <h3>Resumen de tu Compra</h3>
                        <div id="order-items" class="order-items">
                            <!-- Items del carrito se cargarán aquí con JS -->
                        </div>
                        <div class="summary-totals">
                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span id="checkout-subtotal">$0</span>
                            </div>
                            <div class="summary-row">
                                <span>Descuentos</span>
                                <span id="checkout-descuentos">-$0</span>
                            </div>
                            <div class="summary-row">
                                <span>Envío</span>
                                <span id="checkout-envio">Gratis</span>
                            </div>
                            <div class="summary-row total">
                                <span>Total</span>
                                <span id="checkout-total">$0</span>
                            </div>
                        </div>
                        <div class="security-info">
                            <i class="fas fa-lock"></i>
                            <span>Tu información está segura.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="js/main.js"></script>
    <script src="js/checkout.js"></script>
</body>
</html>
