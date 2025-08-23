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

// Procesar actualización de datos
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar'])) {
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $celular = trim($_POST['celular']);

    $stmt = $db->prepare("UPDATE usuarios SET nombre = ?, correo = ?, celular = ? WHERE id = ?");
    if ($stmt->execute([$nombre, $correo, $celular, $_SESSION['user_id']])) {
        $mensaje = "Datos actualizados correctamente.";
    } else {
        $mensaje = "Error al actualizar los datos.";
    }
}

// Obtener información del usuario actualizada
$stmt = $db->prepare("SELECT nombre, correo, celular FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener pedidos del usuario
$stmt = $db->prepare("SELECT codigo, fecha, estado, total, direccion_envio 
                      FROM ordenes 
                      WHERE id_usuario = ? 
                      ORDER BY fecha DESC");
$stmt->execute([$_SESSION['user_id']]);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener la última orden para mostrar la dirección de envío
$pedidoUltimo = !empty($pedidos) ? end($pedidos) : null;

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_clave'])) {
    $clave_actual = $_POST['clave_actual'] ?? '';
    $nueva_clave = $_POST['nueva_clave'] ?? '';
    $confirmar_clave = $_POST['confirmar_clave'] ?? '';

    // Obtener la clave actual del usuario
    $stmt = $db->prepare("SELECT clave FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $usuario_clave = $stmt->fetchColumn();

    if (!password_verify($clave_actual, $usuario_clave)) {
        $mensaje = "La contraseña actual es incorrecta.";
    } elseif ($nueva_clave !== $confirmar_clave) {
        $mensaje = "Las contraseñas nuevas no coinciden.";
    } elseif (strlen($nueva_clave) < 6) {
        $mensaje = "La nueva contraseña debe tener al menos 6 caracteres.";
    } else {
        $clave_hash = password_hash($nueva_clave, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE usuarios SET clave = ? WHERE id = ?");
        if ($stmt->execute([$clave_hash, $_SESSION['user_id']])) {
            $mensaje = "Contraseña actualizada correctamente.";
        } else {
            $mensaje = "Error al actualizar la contraseña.";
        }
    }
}
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
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: #ffffff;
            min-height: 100vh;
            line-height: 1.6;
        }

        /* Header */
        .header {
            background: rgba(0, 0, 0, 0.9);
            padding: 1rem 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 193, 7, 0.2);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        .logo {
            width: 60px;
            height: 60px;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4, #45b7d1, #96ceb4, #feca57);
            border-radius: 50%;
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
            gap: 2rem;
        }

        .nav a {
            color: #ffffff;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav a:hover {
            color: #ffd700;
        }

        .header-icons {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .icon-btn {
            background: none;
            border: none;
            color: #ffffff;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .icon-btn:hover {
            background: rgba(255, 215, 0, 0.1);
            color: #ffd700;
        }

        /* Main Content */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 120px 2rem 2rem;
        }

        .page-title {
            text-align: center;
            font-size: 3rem;
            font-weight: 700;
            color: #ffd700;
            margin-bottom: 3rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .profile-section {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 2.5rem;
            margin-bottom: 3rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 215, 0, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .section-title {
            font-size: 1.8rem;
            color: #ffd700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title::before {
            content: '';
            width: 4px;
            height: 30px;
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            border-radius: 2px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .info-item {
            background: rgba(255, 255, 255, 0.03);
            padding: 1.5rem;
            border-radius: 12px;
            border-left: 4px solid #ffd700;
            transition: transform 0.3s ease;
        }

        .info-item:hover {
            transform: translateY(-2px);
            background: rgba(255, 255, 255, 0.08);
        }

        .info-label {
            font-weight: 600;
            color: #ffd700;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
        }

        .info-value {
            font-size: 1.1rem;
            color: #ffffff;
        }

        /* Table Styles */
        .table-container {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 2.5rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 215, 0, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .table-wrapper {
            overflow-x: auto;
            border-radius: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 12px;
            overflow: hidden;
        }

        thead {
            background: linear-gradient(45deg, #ffd700, #ffed4e);
        }

        thead th {
            padding: 1.5rem 1rem;
            text-align: left;
            font-weight: 700;
            color: #000000;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        tbody tr {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        tbody tr:hover {
            background: rgba(255, 215, 0, 0.1);
            transform: scale(1.01);
        }

        tbody td {
            padding: 1.5rem 1rem;
            color: #ffffff;
            font-size: 0.95rem;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .status-pendiente {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
            border: 1px solid #ffc107;
        }

        .status-completado {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border: 1px solid #28a745;
        }

        .status-cancelado {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 1px solid #dc3545;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: rgba(255, 255, 255, 0.6);
            font-style: italic;
        }

        .empty-state::before {
            content: '📦';
            font-size: 3rem;
            display: block;
            margin-bottom: 1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                padding: 0 1rem;
            }

            .nav {
                display: none;
            }

            .container {
                padding: 100px 1rem 2rem;
            }

            .page-title {
                font-size: 2rem;
            }

            .profile-section,
            .table-container {
                padding: 1.5rem;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            thead th,
            tbody td {
                padding: 1rem 0.5rem;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
   <!-- Header -->
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

    <!-- Main Content -->
        <div class="container">
        <h1 class="page-title">Mi Perfil</h1>
        
        <!-- Mensaje de actualización -->
        <?php if ($mensaje): ?>
            <div style="background:#ffd700;color:#222;padding:10px;border-radius:8px;margin-bottom:20px;text-align:center;">
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <!-- Información Personal y Formulario de Edición -->
        <div class="profile-section">
            <h2 class="section-title">Información Personal</h2>
            <form method="post" style="margin-bottom:2rem;">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Nombre Completo</div>
                        <input type="text" name="nombre" value="<?= htmlspecialchars($usuario['nombre']); ?>" required style="width:100%;padding:8px;border-radius:6px;">
                    </div>
                    <div class="info-item">
                        <div class="info-label">Correo Electrónico</div>
                        <input type="email" name="correo" value="<?= htmlspecialchars($usuario['correo']); ?>" required style="width:100%;padding:8px;border-radius:6px;">
                    </div>
                    <div class="info-item">
                        <div class="info-label">Teléfono</div>
                        <input type="text" name="celular" value="<?= htmlspecialchars($usuario['celular']); ?>" required style="width:100%;padding:8px;border-radius:6px;">
                    </div>
                    <div class="info-item">
                        <div class="info-label">Dirección de Envío</div>
                        <div class="info-value">
                            <?= $pedidoUltimo && isset($pedidoUltimo['direccion_envio']) 
                                ? htmlspecialchars($pedidoUltimo['direccion_envio']) 
                                : 'No registrada'; ?>
                        </div>
                    </div>
                </div>
                <button type="submit" name="actualizar" style="margin-top:20px;padding:10px 24px;background:#ffd700;color:#222;border:none;border-radius:8px;font-weight:bold;cursor:pointer;">
                    Guardar Cambios
                </button>
            </form>
        </div>

        <!-- Agrega este bloque debajo del formulario de datos personales -->
<div class="profile-section" style="margin-top:2rem;">
    <h2 class="section-title"><i class="fas fa-key"></i> Cambiar Contraseña</h2>
    <form method="post">
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Contraseña Actual</div>
                <input type="password" name="clave_actual" required style="width:100%;padding:8px;border-radius:6px;">
            </div>
            <div class="info-item">
                <div class="info-label">Nueva Contraseña</div>
                <input type="password" name="nueva_clave" required style="width:100%;padding:8px;border-radius:6px;">
            </div>
            <div class="info-item">
                <div class="info-label">Confirmar Nueva Contraseña</div>
                <input type="password" name="confirmar_clave" required style="width:100%;padding:8px;border-radius:6px;">
            </div>
        </div>
        <button type="submit" name="cambiar_clave" style="margin-top:20px;padding:10px 24px;background:#ffd700;color:#222;border:none;border-radius:8px;font-weight:bold;cursor:pointer;">
            Cambiar Contraseña
        </button>
    </form>
</div>
        <!-- Historial de Pedidos igual que antes... -->
        <div class="table-container">
            <h2 class="section-title">Historial de Pedidos</h2>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Código de Pedido</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pedidos)): ?>
                      <?php foreach ($pedidos as $pedido): ?>
<tr>
    <td>
        <a href="detalle-pedido.php?codigo=<?= urlencode($pedido['codigo']); ?>" style="color:#ffd700;font-weight:bold;text-decoration:underline;">
            <?= htmlspecialchars($pedido['codigo']); ?>
        </a>
    </td>
    <td><?= date('d/m/Y H:i', strtotime($pedido['fecha'])); ?></td>
    <td>
        <span class="status-badge status-<?= strtolower($pedido['estado']); ?>">
            <?= htmlspecialchars($pedido['estado']); ?>
        </span>
    </td>
    <td><strong>$<?= number_format($pedido['total']); ?></strong></td>
</tr>
<?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="empty-state">
                                    No tienes pedidos realizados aún.<br>
                                    <small>¡Explora nuestros productos y realiza tu primera compra!</small>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
