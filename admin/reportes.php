<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$reporte_generado = false;
$resultados_reporte = [];
$titulo_reporte = "Reporte General";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_type'])) {
    $report_type = $_POST['report_type'];
    $fecha_inicio = $_POST['fecha_inicio'] ?? null;
    $fecha_fin = $_POST['fecha_fin'] ?? null;

    $reporte_generado = true;

    switch ($report_type) {
        case 'ventas_por_periodo':
            $titulo_reporte = "Reporte de Ventas por Periodo";
            $query = "
                SELECT DATE(o.fecha) as fecha_venta, SUM(v.valor_total) as total_diario, COUNT(DISTINCT o.id) as total_ordenes
                FROM ventas v
                JOIN ordenes o ON v.id_orden = o.id
                WHERE o.estado = 'Entregado'
            ";
            $params = [];
            if ($fecha_inicio) {
                $query .= " AND DATE(o.fecha) >= ?";
                $params[] = $fecha_inicio;
            }
            if ($fecha_fin) {
                $query .= " AND DATE(o.fecha) <= ?";
                $params[] = $fecha_fin;
            }
            $query .= " GROUP BY DATE(o.fecha) ORDER BY fecha_venta ASC";
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $resultados_reporte = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'productos_mas_vendidos':
            $titulo_reporte = "Reporte de Productos Más Vendidos";
            $query = "
                SELECT p.nombre, p.imagen, p.precio_unitario, SUM(v.cantidad) as cantidad_vendida, SUM(v.valor_total) as total_generado
                FROM ventas v
                JOIN productos p ON v.id_producto = p.id
                JOIN ordenes o ON v.id_orden = o.id
                WHERE o.estado = 'Entregado'
            ";
            $params = [];
            if ($fecha_inicio) {
                $query .= " AND DATE(o.fecha) >= ?";
                $params[] = $fecha_inicio;
            }
            if ($fecha_fin) {
                $query .= " AND DATE(o.fecha) <= ?";
                $params[] = $fecha_fin;
            }
            $query .= " GROUP BY p.id ORDER BY cantidad_vendida DESC LIMIT 10";
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $resultados_reporte = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'clientes_top':
            $titulo_reporte = "Reporte de Clientes con Más Compras";
            $query = "
                SELECT u.nombre, u.correo, u.celular, COUNT(o.id) as total_ordenes, SUM(o.total) as total_gastado
                FROM usuarios u
                JOIN ordenes o ON u.id = o.id_usuario
                WHERE o.estado = 'Entregado'
            ";
            $params = [];
            if ($fecha_inicio) {
                $query .= " AND DATE(o.fecha) >= ?";
                $params[] = $fecha_inicio;
            }
            if ($fecha_fin) {
                $query .= " AND DATE(o.fecha) <= ?";
                $params[] = $fecha_fin;
            }
            $query .= " GROUP BY u.id ORDER BY total_gastado DESC LIMIT 10";
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $resultados_reporte = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
        
        case 'stock_productos':
            $titulo_reporte = "Reporte de Stock de Productos";
            $query = "
                SELECT p.nombre, p.cantidad, p.precio_unitario, c.nombre as categoria_nombre
                FROM productos p
                LEFT JOIN categorias c ON p.id_categoria = c.id
                ORDER BY p.cantidad ASC
            ";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $resultados_reporte = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                <li><a href="ventas.php"><i class="fas fa-chart-line"></i> Ventas</a></li>
                <li><a href="pedidos.php"><i class="fas fa-shopping-cart"></i> Pedidos</a></li>
                <li><a href="reportes.php" class="active"><i class="fas fa-file-alt"></i> Reportes</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <h1><i class="fas fa-file-alt"></i> Generador de Reportes</h1>
            </header>

            <div class="card">
                <div class="card-header">
                    <h3>Seleccionar Tipo de Reporte</h3>
                </div>
                <div class="card-body">
                    <form method="POST" class="report-form">
                        <div class="form-group">
                            <label for="report_type">Tipo de Reporte:</label>
                            <select id="report_type" name="report_type" onchange="toggleDateInputs()">
                                <option value="">Seleccione un reporte</option>
                                <option value="ventas_por_periodo">Ventas por Periodo</option>
                                <option value="productos_mas_vendidos">Productos Más Vendidos</option>
                                <option value="clientes_top">Clientes con Más Compras</option>
                                <option value="stock_productos">Stock de Productos</option>
                            </select>
                        </div>

                        <div class="date-range-group" id="dateRangeGroup" style="display: none;">
                            <div class="form-group">
                                <label for="fecha_inicio">Fecha Inicio:</label>
                                <input type="date" id="fecha_inicio" name="fecha_inicio">
                            </div>
                            <div class="form-group">
                                <label for="fecha_fin">Fecha Fin:</label>
                                <input type="date" id="fecha_fin" name="fecha_fin">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-cogs"></i> Generar Reporte
                        </button>
                    </form>
                </div>
            </div>

            <?php if ($reporte_generado): ?>
                <div class="card report-results">
                    <div class="card-header">
                        <h3><?php echo htmlspecialchars($titulo_reporte); ?></h3>
                        <button class="btn btn-secondary" onclick="printReport()">
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                        <button class="btn btn-secondary" onclick="exportReportToCSV()">
                            <i class="fas fa-file-csv"></i> Exportar CSV
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($resultados_reporte)): ?>
                            <div class="empty-state">
                                <i class="fas fa-info-circle"></i>
                                <p>No se encontraron datos para este reporte con los filtros seleccionados.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <?php 
                                            // Encabezados de tabla dinámicos
                                            $first_row = $resultados_reporte[0];
                                            foreach ($first_row as $key => $value) {
                                                echo "<th>" . htmlspecialchars(ucwords(str_replace('_', ' ', $key))) . "</th>";
                                            }
                                            ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($resultados_reporte as $row): ?>
                                            <tr>
                                                <?php foreach ($row as $key => $value): ?>
                                                    <td>
                                                        <?php 
                                                        if (strpos($key, 'total') !== false || strpos($key, 'precio') !== false || strpos($key, 'gastado') !== false) {
                                                            echo '$' . number_format($value, 0, ',', '.');
                                                        } elseif ($key === 'imagen') {
                                                            echo '<img src="../images/productos/' . htmlspecialchars($value) . '" alt="Producto" class="table-img-sm">';
                                                        } else {
                                                            echo htmlspecialchars($value);
                                                        }
                                                        ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="../js/admin.js"></script>
    <script>
        function toggleDateInputs() {
            const reportType = document.getElementById('report_type').value;
            const dateRangeGroup = document.getElementById('dateRangeGroup');
            if (reportType === 'ventas_por_periodo' || reportType === 'productos_mas_vendidos' || reportType === 'clientes_top') {
                dateRangeGroup.style.display = 'flex';
            } else {
                dateRangeGroup.style.display = 'none';
                document.getElementById('fecha_inicio').value = '';
                document.getElementById('fecha_fin').value = '';
            }
        }

        function printReport() {
            const printContent = document.querySelector('.report-results .card-body').innerHTML;
            const originalBody = document.body.innerHTML;
            document.body.innerHTML = `
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                    .table-img-sm { width: 50px; height: 50px; object-fit: cover; border-radius: 5px; }
                    .empty-state { text-align: center; padding: 20px; color: #777; }
                </style>
                <h1><?php echo htmlspecialchars($titulo_reporte); ?></h1>
                ${printContent}
            `;
            window.print();
            document.body.innerHTML = originalBody; // Restaurar el contenido original
            location.reload(); // Recargar para restaurar scripts y eventos
        }

        function exportReportToCSV() {
            const table = document.querySelector('.report-results table');
            if (!table) {
                alert('No hay tabla para exportar.');
                return;
            }

            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            for (let i = 0; i < rows.length; i++) {
                let row = [], cols = rows[i].querySelectorAll('td, th');
                for (let j = 0; j < cols.length; j++) {
                    let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, '').replace(/(\s\s)/gm, ' ');
                    data = data.replace(/"/g, '""');
                    row.push('"' + data + '"');
                }
                csv.push(row.join(','));
            }

            const csv_string = csv.join('\n');
            const filename = 'reporte_<?php echo strtolower(str_replace(' ', '_', $titulo_reporte)); ?>_' + new Date().toISOString().slice(0,10) + '.csv';
            const blob = new Blob([csv_string], { type: 'text/csv;charset=utf-8;' });

            if (navigator.msSaveBlob) { // IE 10+
                navigator.msSaveBlob(blob, filename);
            } else {
                const link = document.createElement('a');
                if (link.download !== undefined) {
                    const url = URL.createObjectURL(blob);
                    link.setAttribute('href', url);
                    link.setAttribute('download', filename);
                    link.style.visibility = 'hidden';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            }
        }

        // Inicializar el estado de los inputs de fecha al cargar la página
        document.addEventListener('DOMContentLoaded', toggleDateInputs);
    </script>
</body>
</html>
