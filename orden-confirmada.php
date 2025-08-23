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

    <style>
  @media print {
  body * {
    visibility: hidden !important;
  }
  #print-receipt, #print-receipt * {
    visibility: visible !important;
    display: block !important;
  }
  #print-receipt {
    position: absolute !important;
    left: 0 !important;
    top: 0 !important;
    width: 100vw !important;
    background: #000 !important;
    color: #fff !important;
    box-shadow: none !important;
    border: none !important;
    margin: 0 !important;
    padding: 0 !important;
  }
}

  #print-receipt .logo {
    width: 80px;
    display: block;
    margin: 0 auto 10px;
  }

  #print-receipt h2 {
    text-align: center;
    margin-bottom: 10px;
  }

  #print-receipt .info {
    margin-bottom: 15px;
    font-size: 12px;
  }

  #print-receipt .items {
    border-top: 1px solid yellow;
    border-bottom: 1px solid yellow;
    padding: 10px 0;
    margin-bottom: 10px;
  }

  #print-receipt .item-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
  }

  #print-receipt .total {
    text-align: right;
    font-weight: bold;
    font-size: 14px;
  }

  #print-receipt .thanks {
    text-align: center;
    margin-top: 15px;
    font-size: 12px;
  }

            
            #print-receipt, #print-receipt * {
                visibility: visible;
            }
            #print-receipt {
                position: absolute;
                left: 0;
                top: 0;
                width: 100vw;
                padding: 0;
                margin: 0;
                background: #000; /* Fondo negro */
                color: #fff;      /* Texto blanco */
                font-family: 'Poppins', Arial, sans-serif;
                border: 4px solid transparent;
                border-radius: 12px;
                background-clip: padding-box;
                position: relative;
            }
            #print-receipt::before {
                content: "";
                position: absolute;
                top: -4px; left: -4px; right: -4px; bottom: -4px;
                border-radius: 12px;
                background: linear-gradient(45deg, #FFD700, #FFA500, #FFD700);
                z-index: -1;
            }
            .no-print {
                display: none !important;
            }
        }

        /* Estilos para el recibo en pantalla */
        #print-receipt {
            max-width: 400px;
            margin: 40px auto;
            background: #000; /* Fondo negro */
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(255,215,0,0.3);
            padding: 24px;
            color: #fff; /* Texto blanco */
            border: 4px solid transparent;
            background-clip: padding-box;
            position: relative;
        }
        #print-receipt::before {
            content: "";
            position: absolute;
            top: -4px; left: -4px; right: -4px; bottom: -4px;
            border-radius: 12px;
            background: linear-gradient(45deg, #FFD700, #FFA500, #FFD700);
            z-index: -1;
        }
        #print-receipt .logo {
            display: block;
            margin: 0 auto 12px auto;
            max-width: 120px;
            filter: brightness(0) invert(1); /* Hace el logo blanco si es oscuro */
        }
        #print-receipt h2 {
            text-align: center;
            margin-bottom: 8px;
            color: #FFD700; /* Dorado para el título */
        }
        #print-receipt .info {
            font-size: 14px;
            margin-bottom: 12px;
            color: #ddd;
        }
        #print-receipt .items {
            border-top: 1px dashed #FFD700; /* Línea amarilla punteada */
            margin-top: 12px;
            margin-bottom: 12px;
        }
        #print-receipt .item-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            padding: 4px 0;
            color: #eee;
        }
        #print-receipt .total {
            font-weight: bold;
            font-size: 16px;
            text-align: right;
            margin-top: 12px;
            color: #FFD700; /* Total en dorado */
        }
        #print-receipt .thanks {
            text-align: center;
            margin-top: 18px;
            font-size: 15px;
            color: #4caf50; /* Verde para resaltar */
        }
    </style>
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

           <!-- Recibo para impresión -->
    <div id="print-receipt" class="no-print">
        <img src="Logo/Logo juancho.png" alt="Logo" class="logo">
        <h2>Recibo de Pedido</h2>
        <div class="info">
            <div><strong>N° Pedido:</strong> <?php echo $codigo_orden; ?></div>
            <div><strong>Fecha y Hora:</strong> <?php echo date('d/m/Y H:i:s', strtotime($orden['fecha'])); ?></div>
            <div><strong>Cliente:</strong> <?php echo htmlspecialchars($orden['cliente_nombre']); ?></div>
            <div><strong>Correo:</strong> <?php echo htmlspecialchars($orden['cliente_correo']); ?></div>
            <?php if (isset($info_adicional['telefono'])): ?>
            <div><strong>Teléfono:</strong> <?php echo $info_adicional['telefono']; ?></div>
            <?php endif; ?>
            <?php if (isset($info_adicional['direccion']) && !empty($info_adicional['direccion'])): ?>
            <div><strong>Dirección:</strong> <?php echo htmlspecialchars($info_adicional['direccion']); ?></div>
            <?php endif; ?>
        </div>
        <div class="items">
            <?php foreach ($items as $item): ?>
            <div class="item-row">
                <span><?php echo htmlspecialchars($item['nombre']); ?> x<?php echo $item['cantidad']; ?></span>
                <span>$<?php echo number_format($item['valor_total']); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="total">
            Total Pagado: $<?php echo number_format($orden['total']); ?>
        </div>
        <div class="thanks">
            ¡Gracias por tu compra!<br>
            Tienda Juancho & Sofi
        </div>
    </div>

    <div class="confirmation-actions">
        <a href="index.php" class="btn btn-primary">
            <i class="fas fa-home"></i> Volver al Inicio
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
