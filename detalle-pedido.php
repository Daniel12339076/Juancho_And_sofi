<?php
session_start();
require_once 'config/database.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Obtener el código del pedido
$codigo = $_GET['codigo'] ?? '';
if (!$codigo) {
    echo "Código de pedido no especificado.";
    exit();
}

$database = new Database();
$db = $database->getConnection();
 
// Obtener datos del pedido
$stmt = $db->prepare("SELECT * FROM ordenes WHERE codigo = ? AND id_usuario = ?");
$stmt->execute([$codigo, $_SESSION['user_id']]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    echo "Pedido no encontrado.";
    exit();
}

// Obtener datos del pedido
// ...existing code...
$stmt = $db->prepare("SELECT p.nombre AS producto, v.cantidad, v.precio_unitario, v.valor_total FROM ventas v JOIN productos p ON v.id_producto = p.id WHERE v.id_orden = ?");
$stmt->execute([$pedido['id']]);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
// ...existing code...
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda Juancho & Sofi - Tecnología y Accesorios</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 50%, #0f0f0f 100%);
            color: #ffffff;
            min-height: 100vh;
            line-height: 1.6;
        }

        /* Header moderno con logo y navegación */
        .header {
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 215, 0, 0.2);
            padding: 1rem 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
        }

        .logo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4, #45b7d1, #96ceb4, #feca57);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.8rem;
            text-align: center;
            color: white;
            border: 3px solid #ffd700;
        }

        .nav {
    display: flex;
    gap: 2.0rem; /* Más espacio entre enlaces */
    justify-content: center;
    margin-top: 1rem;
}

.nav a {
    color: #fff !important; /* Blanco */
    text-decoration: none;
    font-weight: 600;
    font-size: 1.1rem;
    transition: color 0.3s;
    padding: 0.5rem 1rem; /* Espaciado interno */
}

.nav a:hover {
    color: #ffd700 !important; /* Amarillo al pasar el mouse */
}

        /* Container principal con efectos de cristal */
        .container {
            max-width: 1000px;
            margin: 120px auto 2rem;
            padding: 0 2rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #cccccc;
            font-size: 1.1rem;
        }

        /* Card de información del pedido con efectos modernos */
        .order-info-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 215, 0, 0.2);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .order-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .info-item {
            text-align: center;
        }

        .info-label {
            color: #ffd700;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
        }

        .info-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: #ffffff;
        }

        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pendiente { background: linear-gradient(135deg, #ff9a56, #ff6b6b); }
        .status-procesando { background: linear-gradient(135deg, #4ecdc4, #44a08d); }
        .status-enviado { background: linear-gradient(135deg, #667eea, #764ba2); }
        .status-entregado { background: linear-gradient(135deg, #56ab2f, #a8e6cf); }

        /* Tabla de productos moderna con efectos hover */
        .products-section {
            margin-top: 2rem;
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: #ffd700;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .table-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 215, 0, 0.2);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
        }

        .products-table th {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.2), rgba(255, 215, 0, 0.1));
            color: #ffd700;
            padding: 1.2rem;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }

        .products-table td {
            padding: 1.2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: #ffffff;
            font-weight: 500;
        }

        .products-table tr:hover {
            background: rgba(255, 215, 0, 0.05);
            transform: translateY(-2px);
            transition: all 0.3s ease;
        }

        .products-table tr:last-child td {
            border-bottom: none;
        }

        .price-cell {
            font-weight: 700;
            color: #ffd700;
        }

        /* Botón de regreso moderno */
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #000000;
            text-decoration: none;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
            margin-top: 2rem;
        }

        .back-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4);
            background: linear-gradient(135deg, #ffed4e, #ffd700);
        }

        .back-button::before {
            content: "←";
            font-size: 1.2rem;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .header-content {
                padding: 0 1rem;
            }

            .nav-links {
                display: none;
            }

            .container {
                padding: 0 1rem;
                margin-top: 100px;
            }

            .page-title {
                font-size: 2rem;
            }

            .order-info-grid {
                grid-template-columns: 1fr;
            }

            .products-table {
                font-size: 0.9rem;
            }

            .products-table th,
            .products-table td {
                padding: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header moderno agregado -->
     <header class="header">
        <div class="header-content">
            <div class="nav-brand">
                <a href="index.php">
                    <img src="Logo/Logo juancho.png" alt="Logo" class="logo-img" style="width: 100px; height: 100px;">
                </a>
            </div>
            <nav class="nav">
                <a href="index.php">Inicio</a>
                <a href="productos.php">Productos</a>
                <a href="perfil.php">Mi Perfil</a>
                <a href="logout.php">Salir</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <!-- Header de página rediseñado -->
        <div class="page-header">
            <h1 class="page-title">Detalle del Pedido</h1>
            <p class="page-subtitle">Información completa de tu orden</p>
        </div>

        <!-- Card de información del pedido modernizada -->
        <div class="order-info-card">
            <div class="order-info-grid">
                <div class="info-item">
                    <div class="info-label">Código</div>
                    <div class="info-value"><?= htmlspecialchars($pedido['codigo']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Fecha</div>
                    <div class="info-value"><?= date('d/m/Y H:i', strtotime($pedido['fecha'])); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Estado</div>
                    <div class="info-value">
                        <span class="status-badge status-<?= strtolower($pedido['estado']); ?>">
                            <?= htmlspecialchars($pedido['estado']); ?>
                        </span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Total</div>
                    <div class="info-value price-cell">$<?= number_format($pedido['total']); ?></div>
                </div>
            </div>
        </div>

        <!-- Sección de productos rediseñada -->
        <div class="products-section">
            <h2 class="section-title">Productos del Pedido</h2>
            <div class="table-container">
                <table class="products-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Valor Unitario</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $prod): ?>
                        <tr>
                            <td><?= htmlspecialchars($prod['producto']); ?></td>
                            <td><?= $prod['cantidad']; ?></td>
                            <td class="price-cell">$<?= number_format($prod['precio_unitario']); ?></td>
                            <td class="price-cell">$<?= number_format($prod['valor_total']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Botón de regreso modernizado -->
        <a href="perfil.php" class="back-button">Volver al perfil</a>
    </div>
</body>
</html>
