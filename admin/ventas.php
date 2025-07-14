<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Obtener ventas
$stmt = $db->prepare("
    SELECT v.*, p.nombre as producto_nombre, p.imagen as producto_imagen,
           o.codigo as orden_codigo, o.fecha as orden_fecha, o.estado as orden_estado,
           u.nombre as cliente_nombre, u.correo as cliente_correo
    FROM ventas v
    JOIN productos p ON v.id_producto = p.id
    JOIN ordenes o ON v.id_orden = o.id
    JOIN usuarios u ON o.id_usuario = u.id
    ORDER BY o.fecha DESC
");
$stmt->execute();
$ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular total de ventas
$total_ventas = $db->query("SELECT SUM(total) FROM ordenes WHERE estado = 'Entregado'")->fetchColumn();
$total_ventas = $total_ventas ?? 0; // Asegurar que no sea NULL

// Ventas por mes (ejemplo simple)
$ventas_por_mes = [];
$stmt_ventas_mes = $db->prepare("
    SELECT DATE_FORMAT(fecha, '%Y-%m') as mes, SUM(total) as total_mes
    FROM ordenes
    WHERE estado = 'Entregado'
    GROUP BY mes
    ORDER BY mes ASC
");
$stmt_ventas_mes->execute();
while ($row = $stmt_ventas_mes->fetch(PDO::FETCH_ASSOC)) {
    $ventas_por_mes[$row['mes']] = $row['total_mes'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Ventas - Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="logo">
                <img src="../Logo/Logo juancho.png" alt="Logo" class="logo-img">
                <h3>Panel Admin</h3>
            </div>
            <ul class="nav-menu">
                <li><a href="index.php"><i class="fas fa-home"></i> Inicio</a></li>
                <li><a href="usuarios.php"><i class="fas fa-users"></i> Usuarios</a></li>
                <li><a href="categorias.php"><i class="fas fa-tags"></i> Categorías</a></li>
                <li><a href="productos.php"><i class="fas fa-box"></i> Productos</a></li>
                <li><a href="ventas.php" class="active"><i class="fas fa-chart-line"></i> Ventas</a></li>
                <li><a href="pedidos.php"><i class="fas fa-shopping-cart"></i> Pedidos</a></li>
                <li><a href="reportes.php"><i class="fas fa-file-alt"></i> Reportes</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <h1><i class="fas fa-chart-line"></i> Gestión de Ventas</h1>
            </header>

            <!-- Estadísticas de Ventas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon sales">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>$<?php echo number_format($total_ventas, 0, ',', '.'); ?></h3>
                        <p>Total Ventas (Entregadas)</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orders">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo count($ventas); ?></h3>
                        <p>Productos Vendidos</p>
                    </div>
                </div>
                <!-- Puedes añadir más estadísticas aquí -->
            </div>

            <!-- Gráfico de Ventas -->
            <div class="card chart-card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-bar"></i> Ventas Mensuales</h3>
                </div>
                <div class="card-body">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>

            <!-- Lista de Ventas Detalladas -->
            <div class="table-container">
                <h2><i class="fas fa-list"></i> Detalle de Ventas</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID Venta</th>
                            <th>Orden</th>
                            <th>Fecha Orden</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio Unitario</th>
                            <th>Valor Total</th>
                            <th>Cliente</th>
                            <th>Estado Orden</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($ventas) > 0): ?>
                            <?php foreach ($ventas as $venta): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($venta['id']); ?></td>
                                    <td><a href="pedidos.php?buscar=<?php echo htmlspecialchars($venta['orden_codigo']); ?>"><?php echo htmlspecialchars($venta['orden_codigo']); ?></a></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($venta['orden_fecha'])); ?></td>
                                    <td>
                                        <div class="product-cell">
                                            <img src="../images/productos/<?php echo htmlspecialchars($venta['producto_imagen']); ?>" alt="<?php echo htmlspecialchars($venta['producto_nombre']); ?>" class="table-img">
                                            <span><?php echo htmlspecialchars($venta['producto_nombre']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($venta['cantidad']); ?></td>
                                    <td>$<?php echo number_format($venta['precio_unitario'], 0, ',', '.'); ?></td>
                                    <td>$<?php echo number_format($venta['valor_total'], 0, ',', '.'); ?></td>
                                    <td><?php echo htmlspecialchars($venta['cliente_nombre']); ?> (<?php echo htmlspecialchars($venta['cliente_correo']); ?>)</td>
                                    <td><span class="status-badge status-<?php echo strtolower($venta['orden_estado']); ?>"><?php echo htmlspecialchars($venta['orden_estado']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="empty-state">No hay ventas registradas.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script src="../js/admin.js"></script>
    <script>
        // Datos para el gráfico de ventas
        const salesData = {
            labels: <?php echo json_encode(array_keys($ventas_por_mes)); ?>,
            datasets: [{
                label: 'Ventas Mensuales',
                data: <?php echo json_encode(array_values($ventas_por_mes)); ?>,
                backgroundColor: 'rgba(52, 152, 219, 0.6)',
                borderColor: 'rgba(52, 152, 219, 1)',
                borderWidth: 1,
                fill: true,
                tension: 0.3
            }]
        };

        // Configuración del gráfico
        const salesConfig = {
            type: 'line',
            data: salesData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: '#333'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value, index, values) {
                                return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(value);
                            },
                            color: '#555'
                        },
                        grid: {
                            color: '#eee'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#555'
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        };

        // Renderizar el gráfico
        window.onload = function() {
            const salesChartCtx = document.getElementById('salesChart').getContext('2d');
            new Chart(salesChartCtx, salesConfig);
        };
    </script>
</body>
</html>
