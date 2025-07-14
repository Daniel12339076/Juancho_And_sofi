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

// Directorio para subir imágenes
$upload_dir = '../images/categorias/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Lógica para agregar/editar categoría
if ($_POST && isset($_POST['action'])) {
    $action = $_POST['action'];
    $nombre = $_POST['nombre'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $imagen_actual = $_POST['imagen_actual'] ?? '';
    $imagen_subida = $_FILES['imagen']['name'] ?? '';

    $final_imagen = $imagen_actual;

    // Manejo de la imagen
    if ($imagen_subida) {
        $target_file = $upload_dir . basename($imagen_subida);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $check = getimagesize($_FILES["imagen"]["tmp_name"]);
        if ($check !== false) {
            if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $target_file)) {
                $final_imagen = basename($imagen_subida);
                // Eliminar imagen antigua si existe y es diferente
                if ($imagen_actual && $imagen_actual != $final_imagen && file_exists($upload_dir . $imagen_actual)) {
                    unlink($upload_dir . $imagen_actual);
                }
            } else {
                $error = "Error al subir la imagen.";
            }
        } else {
            $error = "El archivo no es una imagen válida.";
        }
    }

    if (!$error) {
        if ($action == 'add') {
            // Validar que el nombre no exista
            $stmt_check = $db->prepare("SELECT id FROM categorias WHERE nombre = ?");
            $stmt_check->execute([$nombre]);
            if ($stmt_check->fetch(PDO::FETCH_ASSOC)) {
                $error = "El nombre de la categoría ya existe.";
            } else {
                $stmt = $db->prepare("INSERT INTO categorias (nombre, descripcion, imagen) VALUES (?, ?, ?)");
                if ($stmt->execute([$nombre, $descripcion, $final_imagen])) {
                    $mensaje = "Categoría agregada exitosamente.";
                } else {
                    $error = "Error al agregar categoría.";
                }
            }
        } elseif ($action == 'edit') {
            $id = $_POST['id'];
            $stmt = $db->prepare("UPDATE categorias SET nombre = ?, descripcion = ?, imagen = ? WHERE id = ?");
            if ($stmt->execute([$nombre, $descripcion, $final_imagen, $id])) {
                $mensaje = "Categoría actualizada exitosamente.";
            } else {
                $error = "Error al actualizar categoría.";
            }
        }
    }
}

// Lógica para eliminar categoría
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    // Obtener nombre de la imagen para eliminarla
    $stmt_img = $db->prepare("SELECT imagen FROM categorias WHERE id = ?");
    $stmt_img->execute([$id]);
    $categoria_img = $stmt_img->fetch(PDO::FETCH_ASSOC);

    $stmt = $db->prepare("DELETE FROM categorias WHERE id = ?");
    if ($stmt->execute([$id])) {
        if ($categoria_img && $categoria_img['imagen'] && file_exists($upload_dir . $categoria_img['imagen'])) {
            unlink($upload_dir . $categoria_img['imagen']);
        }
        $mensaje = "Categoría eliminada exitosamente.";
    } else {
        $error = "Error al eliminar categoría.";
    }
}

// Obtener lista de categorías
$stmt = $db->prepare("SELECT * FROM categorias ORDER BY nombre ASC");
$stmt->execute();
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Categorías - Admin</title>
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
                <li><a href="categorias.php" class="active"><i class="fas fa-tags"></i> Categorías</a></li>
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
                <h1><i class="fas fa-tags"></i> Gestión de Categorías</h1>
                <button class="btn btn-primary" onclick="openModal('add')">
                    <i class="fas fa-plus"></i> Nueva Categoría
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
                            <th>Imagen</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($categorias) > 0): ?>
                            <?php foreach ($categorias as $categoria): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($categoria['id']); ?></td>
                                    <td>
                                        <?php if ($categoria['imagen']): ?>
                                            <img src="../images/categorias/<?php echo htmlspecialchars($categoria['imagen']); ?>" alt="<?php echo htmlspecialchars($categoria['nombre']); ?>" class="table-img">
                                        <?php else: ?>
                                            <img src="/placeholder.svg?height=50&width=50" alt="Sin imagen" class="table-img">
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($categoria['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($categoria['descripcion']); ?></td>
                                    <td class="actions">
                                        <button class="btn btn-sm btn-edit" onclick="openModal('edit', <?php echo htmlspecialchars(json_encode($categoria)); ?>)">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        <a href="categorias.php?action=delete&id=<?php echo htmlspecialchars($categoria['id']); ?>" 
                                           class="btn btn-sm btn-delete" 
                                           onclick="return confirm('¿Estás seguro de eliminar esta categoría? Se eliminarán los productos asociados.');">
                                            <i class="fas fa-trash-alt"></i> Eliminar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="empty-state">No hay categorías registradas.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Modal para Agregar/Editar Categoría -->
    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Nueva Categoría</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="categoryForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" id="formAction">
                    <input type="hidden" name="id" id="categoryId">
                    <input type="hidden" name="imagen_actual" id="currentImage">
                    
                    <div class="form-group">
                        <label for="nombre">Nombre:</label>
                        <input type="text" id="nombre" name="nombre" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion">Descripción:</label>
                        <textarea id="descripcion" name="descripcion"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="imagen">Imagen:</label>
                        <input type="file" id="imagen" name="imagen" accept="image/*">
                        <small>Tamaño recomendado: 100x100px</small>
                        <div id="imagePreview" class="image-preview">
                            <!-- La imagen se cargará aquí -->
                        </div>
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
        function openModal(action, categoryData = null) {
            const modal = document.getElementById('categoryModal');
            const modalTitle = document.getElementById('modalTitle');
            const formAction = document.getElementById('formAction');
            const categoryId = document.getElementById('categoryId');
            const nombreInput = document.getElementById('nombre');
            const descripcionInput = document.getElementById('descripcion');
            const imagenInput = document.getElementById('imagen');
            const currentImageInput = document.getElementById('currentImage');
            const imagePreview = document.getElementById('imagePreview');

            formAction.value = action;
            imagenInput.required = (action === 'add'); // Imagen es requerida solo al añadir

            if (action === 'add') {
                modalTitle.textContent = 'Nueva Categoría';
                categoryId.value = '';
                nombreInput.value = '';
                descripcionInput.value = '';
                imagenInput.value = ''; // Limpiar input de archivo
                currentImageInput.value = '';
                imagePreview.innerHTML = ''; // Limpiar previsualización
            } else { // action === 'edit'
                modalTitle.textContent = 'Editar Categoría';
                categoryId.value = categoryData.id;
                nombreInput.value = categoryData.nombre;
                descripcionInput.value = categoryData.descripcion;
                imagenInput.value = ''; // Limpiar input de archivo
                currentImageInput.value = categoryData.imagen;
                if (categoryData.imagen) {
                    imagePreview.innerHTML = `<img src="../images/categorias/${categoryData.imagen}" alt="Imagen actual" style="max-width: 100px; max-height: 100px; margin-top: 10px;">`;
                } else {
                    imagePreview.innerHTML = '';
                }
            }
            modal.style.display = 'block';
        }

        function closeModal() {
            document.getElementById('categoryModal').style.display = 'none';
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('categoryModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>
