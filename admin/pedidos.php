<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Actualizar estado del pedido
if ($_POST && isset($_POST['action']) && $_POST['action'] == 'update_status') {
    $orden_id = $_POST['orden_id'];
    $nuevo_estado = $_POST['nuevo_estado'];
    
    $stmt = $db->prepare("UPDATE ordenes SET estado = ? WHERE id = ?");
    if ($stmt->execute([$nuevo_estado, $orden_id])) {
        $mensaje = "Estado del pedido actualizado exitosamente";
    } else {
        $error = "Error al actualizar el estado del pedido";
    }
}

// Obtener filtros
$filtro_estado = $_GET['estado'] ?? '';
$filtro_fecha = $_GET['fecha'] ?? '';
$buscar = $_GET['buscar'] ?? '';

// Construir consulta con filtros
$where_conditions = [];
$params = [];

if (!empty($filtro_estado)) {
    $where_conditions[] = "o.estado = ?";
    $params[] = $filtro_estado;
}

if (!empty($filtro_fecha)) {
    $where_conditions[] = "DATE(o.fecha) = ?";
    $params[] = $filtro_fecha;
}

if (!empty($buscar)) {
    $where_conditions[] = "(o.codigo LIKE ? OR u.nombre LIKE ? OR u.correo LIKE ?)";
    $search_term = "%$buscar%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Obtener pedidos
$stmt = $db->prepare("
    SELECT o.*, u.nombre as cliente_nombre, u.correo as cliente_correo, u.celular as cliente_celular,
           COUNT(v.id) as total_items
    FROM ordenes o 
    JOIN usuarios u ON o.id_usuario = u.id 
    LEFT JOIN ventas v ON o.id = v.id_orden
    $where_clause
    GROUP BY o.id
    ORDER BY o.fecha DESC, o.id DESC
");
$stmt->execute($params);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas
$stmt = $db->prepare("SELECT estado, COUNT(*) as total FROM ordenes GROUP BY estado");
$stmt->execute();
$estadisticas = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos - Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/admin-pedidos.css">
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
                <li><a href="pedidos.php" class="active"><i class="fas fa-shopping-cart"></i> Pedidos</a></li>
                <li><a href="reportes.php"><i class="fas fa-file-alt"></i> Reportes</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <h1><i class="fas fa-shopping-cart"></i> Gestión de Pedidos</h1>
            </header>

            <?php if (isset($mensaje)): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon solicitado">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $estadisticas['Solicitado'] ?? 0; ?></h3>
                        <p>Solicitados</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon atendido">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $estadisticas['Atendido'] ?? 0; ?></h3>
                        <p>En Proceso</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon entregado">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $estadisticas['Entregado'] ?? 0; ?></h3>
                        <p>Entregados</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon rechazado">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $estadisticas['Rechazado'] ?? 0; ?></h3>
                        <p>Rechazados</p>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="filters-section">
                <form method="GET" class="filters-form">
                    <div class="filter-group">
                        <label>Estado:</label>
                        <select name="estado">
                            <option value="">Todos los estados</option>
                            <option value="Solicitado" <?php echo $filtro_estado === 'Solicitado' ? 'selected' : ''; ?>>Solicitado</option>
                            <option value="Atendido" <?php echo $filtro_estado === 'Atendido' ? 'selected' : ''; ?>>Atendido</option>
                            <option value="Entregado" <?php echo $filtro_estado === 'Entregado' ? 'selected' : ''; ?>>Entregado</option>
                            <option value="Rechazado" <?php echo $filtro_estado === 'Rechazado' ? 'selected' : ''; ?>>Rechazado</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Fecha:</label>
                        <input type="date" name="fecha" value="<?php echo $filtro_fecha; ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label>Buscar:</label>
                        <input type="text" name="buscar" placeholder="Código, cliente..." value="<?php echo htmlspecialchars($buscar); ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    
                    <a href="pedidos.php" class="btn btn-outline">
                        <i class="fas fa-times"></i> Limpiar
                    </a>
                </form>
            </div>

            <!-- Lista de Pedidos -->
            <div class="orders-section">
                <div class="orders-header">
                    <h2>Lista de Pedidos (<?php echo count($pedidos); ?>)</h2>
                    <button onclick="exportOrders()" class="btn btn-secondary">
                        <i class="fas fa-download"></i> Exportar
                    </button>
                </div>

                <div class="orders-grid">
                    <?php foreach ($pedidos as $pedido): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-number">
                                <strong><?php echo $pedido['codigo']; ?></strong>
                                <span class="order-date"><?php echo date('d/m/Y H:i', strtotime($pedido['fecha'])); ?></span>
                            </div>
                            <div class="order-status status-<?php echo strtolower($pedido['estado']); ?>">
                                <?php echo $pedido['estado']; ?>
                            </div>
                        </div>

                        <div class="order-customer">
                            <div class="customer-info">
                                <i class="fas fa-user"></i>
                                <div>
                                    <strong><?php echo htmlspecialchars($pedido['cliente_nombre']); ?></strong>
                                    <small><?php echo htmlspecialchars($pedido['cliente_correo']); ?></small>
                                </div>
                            </div>
                            <div class="customer-contact">
                                <i class="fas fa-phone"></i>
                                <span><?php echo $pedido['cliente_celular']; ?></span>
                            </div>
                        </div>

                        <div class="order-details">
                            <div class="detail-item">
                                <span class="label">Tipo:</span>
                                <span class="value">
                                    <?php echo $pedido['tipo_venta'] === 'local' ? 'Recogida' : 'Domicilio'; ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Items:</span>
                                <span class="value"><?php echo $pedido['total_items']; ?> productos</span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Total:</span>
                                <span class="value total-amount">$<?php echo number_format($pedido['total']); ?></span>
                            </div>
                        </div>

                        <div class="order-actions">
                            <button onclick="viewOrderDetails(<?php echo $pedido['id']; ?>)" class="btn btn-info btn-sm">
                                <i class="fas fa-eye"></i> Ver Detalles
                            </button>
                            
                            <div class="status-update">
                                <form method="POST" class="status-form">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="orden_id" value="<?php echo $pedido['id']; ?>">
                                    <select name="nuevo_estado" onchange="this.form.submit()">
                                        <option value="Solicitado" <?php echo $pedido['estado'] === 'Solicitado' ? 'selected' : ''; ?>>Solicitado</option>
                                        <option value="Atendido" <?php echo $pedido['estado'] === 'Atendido' ? 'selected' : ''; ?>>Atendido</option>
                                        <option value="Entregado" <?php echo $pedido['estado'] === 'Entregado' ? 'selected' : ''; ?>>Entregado</option>
                                        <option value="Rechazado" <?php echo $pedido['estado'] === 'Rechazado' ? 'selected' : ''; ?>>Rechazado</option>
                                    </select>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($pedidos)): ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>No hay pedidos</h3>
                    <p>No se encontraron pedidos con los filtros aplicados</p>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal para detalles del pedido -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Detalles del Pedido</h3>
                <span class="close" onclick="closeOrderModal()">&times;</span>
            </div>
            <div class="modal-body" id="orderModalBody">
                <!-- Contenido se carga dinámicamente -->
            </div>
        </div>
    </div>

    <script src="../js/admin.js"></script>
    <script src="../js/admin-pedidos.js"></script>
</body>
</html>
