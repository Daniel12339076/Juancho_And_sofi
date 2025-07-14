<?php
session_start();
require_once '../config/database.php';

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: ../login.php"); // Redirigir al login si no es admin
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Obtener estadísticas
$total_productos = $db->query("SELECT COUNT(*) FROM productos")->fetchColumn();
$total_usuarios = $db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$total_categorias = $db->query("SELECT COUNT(*) FROM categorias")->fetchColumn();
$total_pedidos = $db->query("SELECT COUNT(*) FROM ordenes")->fetchColumn();

// Pedidos recientes
$stmt_pedidos = $db->prepare("
    SELECT o.*, u.nombre as cliente_nombre 
    FROM ordenes o 
    JOIN usuarios u ON o.id_usuario = u.id 
    ORDER BY o.fecha DESC 
    LIMIT 5
");
$stmt_pedidos->execute();
$pedidos_recientes = $stmt_pedidos->fetchAll(PDO::FETCH_ASSOC);

// Productos con bajo stock
$stmt_stock = $db->prepare("SELECT * FROM productos WHERE cantidad < 10 ORDER BY cantidad ASC LIMIT 5");
$stmt_stock->execute();
$productos_bajo_stock = $stmt_stock->fetchAll(PDO::FETCH_ASSOC);

// Mensajes de contacto recientes
$stmt_contacto = $db->prepare("SELECT * FROM contactar ORDER BY fecha_envio DESC LIMIT 5");
$stmt_contacto->execute();
$mensajes_contacto = $stmt_contacto->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Tienda Juancho & Sofi</title>
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
                <li><a href="index.php" class="active"><i class="fas fa-home"></i> Inicio</a></li>
                <li><a href="usuarios.php"><i class="fas fa-users"></i> Usuarios</a></li>
                <li><a href="categorias.php"><i class="fas fa-tags"></i> Categorías</a></li>
                <li><a href="productos.php"><i class="fas fa-box"></i> Productos</a></li>
                <li><a href="ventas.php"><i class="fas fa-chart-line"></i> Ventas</a></li>
                <li><a href="pedidos.php"><i class="fas fa-shopping-cart"></i> Pedidos</a></li>
                <li><a href="reportes.php"><i class="fas fa-file-alt"></i> Reportes</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                <div class="user-info">
                    <span>Hola, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <img src="../images/admin-avatar.png" alt="Avatar" class="user-avatar">
                </div>
            </header>

            <!-- Cards de Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon products">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_productos; ?></h3>
                        <p>Productos</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_usuarios; ?></h3>
                        <p>Usuarios</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon categories">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_categorias; ?></h3>
                        <p>Categorías</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orders">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_pedidos; ?></h3>
                        <p>Pedidos</p>
                    </div>
                </div>
            </div>

            <!-- Secciones de Resumen -->
            <div class="summary-sections">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-history"></i> Pedidos Recientes</h3>
                        <a href="pedidos.php" class="view-all">Ver todos</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($pedidos_recientes)): ?>
                        <ul class="recent-list">
                            <?php foreach ($pedidos_recientes as $pedido): ?>
                            <li>
                                <span class="list-item-title">#<?php echo htmlspecialchars($pedido['codigo']); ?></span>
                                <span class="list-item-meta">Cliente: <?php echo htmlspecialchars($pedido['cliente_nombre']); ?></span>
                                <span class="list-item-status status-<?php echo strtolower($pedido['estado']); ?>"><?php echo htmlspecialchars($pedido['estado']); ?></span>
                                <span class="list-item-amount">$<?php echo number_format($pedido['total'], 0, ',', '.'); ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php else: ?>
                        <p class="empty-state-text">No hay pedidos recientes.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-exclamation-triangle"></i> Productos con Bajo Stock</h3>
                        <a href="productos.php?stock=low" class="view-all">Ver todos</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($productos_bajo_stock)): ?>
                        <ul class="recent-list">
                            <?php foreach ($productos_bajo_stock as $producto): ?>
                            <li>
                                <span class="list-item-title"><?php echo htmlspecialchars($producto['nombre']); ?></span>
                                <span class="list-item-meta">Categoría: <?php echo htmlspecialchars($producto['id_categoria']); ?></span>
                                <span class="list-item-status status-low-stock">Stock: <?php echo htmlspecialchars($producto['cantidad']); ?></span>
                                <span class="list-item-amount">$<?php echo number_format($producto['precio_unitario'], 0, ',', '.'); ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php else: ?>
                        <p class="empty-state-text">Todos los productos tienen buen stock.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-envelope"></i> Mensajes de Contacto</h3>
                        <a href="#" class="view-all">Ver todos</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($mensajes_contacto)): ?>
                        <ul class="recent-list">
                            <?php foreach ($mensajes_contacto as $mensaje): ?>
                            <li>
                                <span class="list-item-title"><?php echo htmlspecialchars($mensaje['asunto']); ?></span>
                                <span class="list-item-meta">De: <?php echo htmlspecialchars($mensaje['nombre']); ?> (<?php echo htmlspecialchars($mensaje['correo']); ?>)</span>
                                <span class="list-item-status status-new">Nuevo</span>
                                <span class="list-item-amount"><?php echo date('d/m H:i', strtotime($mensaje['fecha_envio'])); ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php else: ?>
                        <p class="empty-state-text">No hay mensajes de contacto recientes.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../js/admin.js"></script>
</body>
</html>
