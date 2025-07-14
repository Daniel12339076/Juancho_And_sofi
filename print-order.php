<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: ../login.php");
    exit();
}

$orden_id = $_GET['id'] ?? 0;

if (!$orden_id) {
    die("ID de pedido requerido.");
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Obtener información de la orden
    $stmt = $db->prepare("
        SELECT o.*, u.nombre as cliente_nombre, u.correo as cliente_correo, u.celular as cliente_celular
        FROM ordenes o 
        JOIN usuarios u ON o.id_usuario = u.id 
        WHERE o.id = ?
    ");
    $stmt->execute([$orden_id]);
    $orden = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$orden) {
        die("Pedido no encontrado.");
    }

    // Obtener items del pedido
    $stmt = $db->prepare("
        SELECT v.*, p.nombre, p.imagen, p.precio_unitario 
        FROM ventas v 
        JOIN productos p ON v.id_producto = p.id 
        WHERE v.id_orden = ?
    ");
    $stmt->execute([$orden_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Error interno del servidor: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imprimir Pedido #<?php echo htmlspecialchars($orden['codigo']); ?></title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 20px;
            color: #333;
            font-size: 12px;
        }
        .invoice-container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #eee;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            background: #fff;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #ffd700;
            padding-bottom: 20px;
        }
        .header img {
            max-width: 100px;
            margin-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #ffd700;
        }
        .header p {
            margin: 5px 0;
            font-size: 14px;
            color: #666;
        }
        .section-title {
            font-size: 16px;
            color: #ffd700;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
        }
        .info-item {
            display: flex;
            flex-direction: column;
        }
        .info-item label {
            font-weight: bold;
            color: #555;
            margin-bottom: 3px;
        }
        .info-item span {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        table th, table td {
            border: 1px solid #eee;
            padding: 8px;
            text-align: left;
        }
        table th {
            background-color: #f2f2f2;
            color: #555;
        }
        .total-section {
            text-align: right;
            font-size: 16px;
            font-weight: bold;
            color: #ffd700;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #777;
            font-size: 10px;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            display: inline-block;
        }
        .status-solicitado { background-color: rgba(243, 156, 18, 0.1); color: #f39c12; }
        .status-atendido { background-color: rgba(52, 152, 219, 0.1); color: #3498db; }
        .status-entregado { background-color: rgba(39, 174, 96, 0.1); color: #27ae60; }
        .status-rechazado { background-color: rgba(231, 76, 60, 0.1); color: #e74c3c; }

        @media print {
            body {
                margin: 0;
            }
            .invoice-container {
                box-shadow: none;
                border: none;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header">
            <img src="Logo/Logo juancho.png" alt="Logo Tienda Juancho & Sofi">
            <h1>Tienda Juancho & Sofi</h1>
            <p>Factura de Pedido</p>
            <p>Fecha de Impresión: <?php echo date('d/m/Y H:i'); ?></p>
        </div>

        <div class="section-title">Información del Pedido</div>
        <div class="info-grid">
            <div class="info-item">
                <label>Código de Pedido:</label>
                <span><?php echo htmlspecialchars($orden['codigo']); ?></span>
            </div>
            <div class="info-item">
                <label>Fecha del Pedido:</label>
                <span><?php echo date('d/m/Y H:i', strtotime($orden['fecha'])); ?></span>
            </div>
            <div class="info-item">
                <label>Estado:</label>
                <span class="status-badge status-<?php echo strtolower($orden['estado']); ?>"><?php echo htmlspecialchars($orden['estado']); ?></span>
            </div>
            <div class="info-item">
                <label>Tipo de Venta:</label>
                <span><?php echo $orden['tipo_venta'] === 'local' ? 'Recogida en Tienda' : 'Envío a Domicilio'; ?></span>
            </div>
            <div class="info-item">
                <label>Método de Pago:</label>
                <span><?php echo htmlspecialchars($orden['metodo_pago']); ?></span>
            </div>
            <?php if (!empty($orden['direccion_envio'])): ?>
            <div class="info-item">
                <label>Dirección de Envío:</label>
                <span><?php echo htmlspecialchars($orden['direccion_envio']); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="section-title">Información del Cliente</div>
        <div class="info-grid">
            <div class="info-item">
                <label>Nombre:</label>
                <span><?php echo htmlspecialchars($orden['cliente_nombre']); ?></span>
            </div>
            <div class="info-item">
                <label>Correo:</label>
                <span><?php echo htmlspecialchars($orden['cliente_correo']); ?></span>
            </div>
            <div class="info-item">
                <label>Celular:</label>
                <span><?php echo htmlspecialchars($orden['cliente_celular']); ?></span>
            </div>
        </div>

        <div class="section-title">Detalle de Productos</div>
        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio Unitario</th>
                    <th>Valor Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($item['cantidad']); ?></td>
                    <td>$<?php echo number_format($item['precio_unitario'], 0, ',', '.'); ?></td>
                    <td>$<?php echo number_format($item['valor_total'], 0, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="total-section">
            Total del Pedido: $<?php echo number_format($orden['total'], 0, ',', '.'); ?>
        </div>

        <div class="footer">
            <p>Gracias por tu compra. Esperamos verte de nuevo.</p>
            <p>Tienda Juancho & Sofi | info@tiendajuanchosofi.com | +57 300 123 4567</p>
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
            // Opcional: cerrar la ventana después de imprimir
            // window.close(); 
        };
    </script>
</body>
</html>
