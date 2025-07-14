<?php
session_start();
require_once '../config/database.php';

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$mensaje = '';
$error = '';

// Lógica para agregar/editar usuario
if ($_POST && isset($_POST['action'])) {
    $action = $_POST['action'];
    $nombre = $_POST['nombre'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $celular = $_POST['celular'] ?? '';
    $clave = $_POST['clave'] ?? '';
    $rol = $_POST['rol'] ?? 'cliente';

    if ($action == 'add') {
        // Validar que el correo no exista
        $stmt_check = $db->prepare("SELECT id FROM usuarios WHERE correo = ?");
        $stmt_check->execute([$correo]);
        if ($stmt_check->fetch(PDO::FETCH_ASSOC)) {
            $error = "El correo electrónico ya está registrado.";
        } else {
            $hashed_password = password_hash($clave, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO usuarios (nombre, correo, celular, clave, rol) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$nombre, $correo, $celular, $hashed_password, $rol])) {
                $mensaje = "Usuario agregado exitosamente.";
            } else {
                $error = "Error al agregar usuario.";
            }
        }
    } elseif ($action == 'edit') {
        $id = $_POST['id'];
        $sql = "UPDATE usuarios SET nombre = ?, correo = ?, celular = ?, rol = ? WHERE id = ?";
        $params = [$nombre, $correo, $celular, $rol, $id];

        if (!empty($clave)) {
            $hashed_password = password_hash($clave, PASSWORD_BCRYPT);
            $sql = "UPDATE usuarios SET nombre = ?, correo = ?, celular = ?, clave = ?, rol = ? WHERE id = ?";
            $params = [$nombre, $correo, $celular, $hashed_password, $rol, $id];
        }

        $stmt = $db->prepare($sql);
        if ($stmt->execute($params)) {
            $mensaje = "Usuario actualizado exitosamente.";
        } else {
            $error = "Error al actualizar usuario.";
        }
    }
}

// Lógica para eliminar usuario
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    // No permitir que un admin se elimine a sí mismo si es el único admin
    if ($id == $_SESSION['user_id'] && $_SESSION['rol'] == 'administrador') {
        $stmt_admins = $db->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'administrador'")->fetchColumn();
        if ($stmt_admins == 1) {
            $error = "No puedes eliminarte a ti mismo si eres el único administrador.";
        } else {
            $stmt = $db->prepare("DELETE FROM usuarios WHERE id = ?");
            if ($stmt->execute([$id])) {
                $mensaje = "Usuario eliminado exitosamente.";
            } else {
                $error = "Error al eliminar usuario.";
            }
        }
    } else {
        $stmt = $db->prepare("DELETE FROM usuarios WHERE id = ?");
        if ($stmt->execute([$id])) {
            $mensaje = "Usuario eliminado exitosamente.";
        } else {
            $error = "Error al eliminar usuario.";
        }
    }
}

// Obtener lista de usuarios
$stmt = $db->prepare("SELECT * FROM usuarios ORDER BY fecha_registro DESC");
$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Admin</title>
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
                <li><a href="usuarios.php" class="active"><i class="fas fa-users"></i> Usuarios</a></li>
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
                <h1><i class="fas fa-users"></i> Gestión de Usuarios</h1>
                <button class="btn btn-primary" onclick="openModal('add')">
                    <i class="fas fa-plus"></i> Nuevo Usuario
                </button>
            </header>

            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Celular</th>
                            <th>Rol</th>
                            <th>Fecha Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($usuarios) > 0): ?>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($usuario['id']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['correo']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['celular']); ?></td>
                                    <td><span class="role-badge <?php echo strtolower($usuario['rol']); ?>"><?php echo htmlspecialchars($usuario['rol']); ?></span></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($usuario['fecha_registro'])); ?></td>
                                    <td class="actions">
                                        <button class="btn btn-sm btn-edit" onclick="openModal('edit', <?php echo htmlspecialchars(json_encode($usuario)); ?>)">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        <a href="usuarios.php?action=delete&id=<?php echo htmlspecialchars($usuario['id']); ?>" 
                                           class="btn btn-sm btn-delete" 
                                           onclick="return confirm('¿Estás seguro de eliminar a este usuario?');">
                                            <i class="fas fa-trash-alt"></i> Eliminar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="empty-state">No hay usuarios registrados.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Modal para Agregar/Editar Usuario -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Nuevo Usuario</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="userForm" method="POST">
                    <input type="hidden" name="action" id="formAction">
                    <input type="hidden" name="id" id="userId">
                    
                    <div class="form-group">
                        <label for="nombre">Nombre:</label>
                        <input type="text" id="nombre" name="nombre" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="correo">Correo:</label>
                        <input type="email" id="correo" name="correo" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="celular">Celular:</label>
                        <input type="number" id="celular" name="celular">
                    </div>
                    
                    <div class="form-group">
                        <label for="clave">Contraseña (dejar vacío para no cambiar):</label>
                        <input type="password" id="clave" name="clave">
                    </div>
                    
                    <div class="form-group">
                        <label for="rol">Rol:</label>
                        <select id="rol" name="rol">
                            <option value="cliente">Cliente</option>
                            <option value="administrador">Administrador</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
                        <button type="button" class="btn btn-outline" onclick="closeModal()"><i class="fas fa-times"></i> Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../js/admin.js"></script>
    <script>
        function openModal(action, userData = null) {
            const modal = document.getElementById('userModal');
            const modalTitle = document.getElementById('modalTitle');
            const formAction = document.getElementById('formAction');
            const userId = document.getElementById('userId');
            const nombreInput = document.getElementById('nombre');
            const correoInput = document.getElementById('correo');
            const celularInput = document.getElementById('celular');
            const claveInput = document.getElementById('clave');
            const rolSelect = document.getElementById('rol');

            formAction.value = action;
            claveInput.required = (action === 'add'); // Clave es requerida solo al añadir

            if (action === 'add') {
                modalTitle.textContent = 'Nuevo Usuario';
                userId.value = '';
                nombreInput.value = '';
                correoInput.value = '';
                celularInput.value = '';
                claveInput.value = '';
                rolSelect.value = 'cliente';
            } else { // action === 'edit'
                modalTitle.textContent = 'Editar Usuario';
                userId.value = userData.id;
                nombreInput.value = userData.nombre;
                correoInput.value = userData.correo;
                celularInput.value = userData.celular;
                claveInput.value = ''; // Siempre vaciar la clave por seguridad
                rolSelect.value = userData.rol;
            }
            modal.style.display = 'block';
        }

        function closeModal() {
            document.getElementById('userModal').style.display = 'none';
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('userModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>
