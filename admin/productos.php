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

// Directorio para subir imágenes de productos
$upload_dir = '../images/productos/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Lógica para agregar/editar producto
if ($_POST && isset($_POST['action'])) {
    $action = $_POST['action'];
    $nombre = $_POST['nombre'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $precio_unitario = $_POST['precio_unitario'] ?? 0;
    $cantidad = $_POST['cantidad'] ?? 0;
    $id_categoria = $_POST['id_categoria'] ?? NULL;
    $marcas = $_POST['marcas'] ?? '';
    $descuento = $_POST['descuento'] ?? 0;
    $tallas = $_POST['tallas'] ?? '';
    $colores = $_POST['colores'] ?? '';

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
            $stmt = $db->prepare("INSERT INTO productos (nombre, descripcion, precio_unitario, cantidad, imagen, id_categoria, marcas, descuento, tallas, colores) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$nombre, $descripcion, $precio_unitario, $cantidad, $final_imagen, $id_categoria, $marcas, $descuento, $tallas, $colores])) {
                $mensaje = "Producto agregado exitosamente.";
            } else {
                $error = "Error al agregar producto.";
            }
        } elseif ($action == 'edit') {
            $id = $_POST['id'];
            $stmt = $db->prepare("UPDATE productos SET nombre = ?, descripcion = ?, precio_unitario = ?, cantidad = ?, imagen = ?, id_categoria = ?, marcas = ?, descuento = ?, tallas = ?, colores = ? WHERE id = ?");
            if ($stmt->execute([$nombre, $descripcion, $precio_unitario, $cantidad, $final_imagen, $id_categoria, $marcas, $descuento, $tallas, $colores, $id])) {
                $mensaje = "Producto actualizado exitosamente.";
            } else {
                $error = "Error al actualizar producto.";
            }
        }
    }
}

// Lógica para eliminar producto
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    // Obtener nombre de la imagen para eliminarla
    $stmt_img = $db->prepare("SELECT imagen FROM productos WHERE id = ?");
    $stmt_img->execute([$id]);
    $producto_img = $stmt_img->fetch(PDO::FETCH_ASSOC);

    $stmt = $db->prepare("DELETE FROM productos WHERE id = ?");
    if ($stmt->execute([$id])) {
        if ($producto_img && $producto_img['imagen'] && file_exists($upload_dir . $producto_img['imagen'])) {
            unlink($upload_dir . $producto_img['imagen']);
        }
        $mensaje = "Producto eliminado exitosamente.";
    } else {
        $error = "Error al eliminar producto.";
    }
}

// Obtener lista de productos con nombre de categoría
$query = "SELECT p.*, c.nombre as categoria_nombre FROM productos p LEFT JOIN categorias c ON p.id_categoria = c.id ORDER BY p.id DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener categorías para el formulario
$stmt_categorias = $db->prepare("SELECT id, nombre FROM categorias ORDER BY nombre ASC");
$stmt_categorias->execute();
$categorias_form = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos - Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/admin-productos.css">
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
                <li><a href="productos.php" class="active"><i class="fas fa-box"></i> Productos</a></li>
                <li><a href="ventas.php"><i class="fas fa-chart-line"></i> Ventas</a></li>
                <li><a href="pedidos.php"><i class="fas fa-shopping-cart"></i> Pedidos</a></li>
                <li><a href="reportes.php"><i class="fas fa-file-alt"></i> Reportes</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <h1><i class="fas fa-box"></i> Gestión de Productos</h1>
                <button class="btn btn-primary" onclick="openModal('add')">
                    <i class="fas fa-plus"></i> Nuevo Producto
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
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Descuento</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($productos) > 0): ?>
                            <?php foreach ($productos as $producto): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($producto['id']); ?></td>
                                    <td>
                                        <?php if ($producto['imagen']): ?>
                                            <img src="../images/productos/<?php echo htmlspecialchars($producto['imagen']); ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>" class="table-img">
                                        <?php else: ?>
                                            <img src="/placeholder.svg?height=50&width=50" alt="Sin imagen" class="table-img">
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($producto['categoria_nombre'] ?? 'N/A'); ?></td>
                                    <td>$<?php echo number_format($producto['precio_unitario'], 0, ',', '.'); ?></td>
                                    <td>
                                        <span class="stock-badge <?php echo $producto['cantidad'] < 10 ? 'low-stock' : 'in-stock'; ?>">
                                            <?php echo htmlspecialchars($producto['cantidad']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($producto['descuento']); ?>%</td>
                                    <td class="actions">
                                        <button class="btn btn-sm btn-edit" onclick="openModal('edit', <?php echo htmlspecialchars(json_encode($producto)); ?>)">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        <a href="productos.php?action=delete&id=<?php echo htmlspecialchars($producto['id']); ?>" 
                                           class="btn btn-sm btn-delete" 
                                           onclick="return confirm('¿Estás seguro de eliminar este producto?');">
                                            <i class="fas fa-trash-alt"></i> Eliminar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="empty-state">No hay productos registrados.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Modal para Agregar/Editar Producto -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Nuevo Producto</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="productForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" id="formAction">
                    <input type="hidden" name="id" id="productId">
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
                        <label for="precio_unitario">Precio Unitario:</label>
                        <input type="number" id="precio_unitario" name="precio_unitario" step="0.01" required>
                    </div>

                    <div class="form-group">
                        <label for="cantidad">Cantidad (Stock):</label>
                        <input type="number" id="cantidad" name="cantidad" required>
                    </div>

                    <div class="form-group">
                        <label for="id_categoria">Categoría:</label>
                        <select id="id_categoria" name="id_categoria">
                            <option value="">Seleccione una categoría</option>
                            <?php foreach ($categorias_form as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['id']); ?>"><?php echo htmlspecialchars($cat['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="marcas">Marca(s):</label>
                        <input type="text" id="marcas" name="marcas">
                        <small>Separar por comas si hay varias (ej: Sony, JBL)</small>
                    </div>

                    <div class="form-group">
                        <label for="descuento">Descuento (%):</label>
                        <input type="number" id="descuento" name="descuento" min="0" max="100" value="0">
                    </div>

                    <div class="form-group">
                        <label for="tallas">Tallas (opcional):</label>
                        <input type="text" id="tallas" name="tallas">
                        <small>Separar por comas (ej: S, M, L, XL)</small>
                    </div>

                    <div class="form-group">
                        <label for="colores">Colores (opcional):</label>
                        <input type="text" id="colores" name="colores">
                        <small>Separar por comas (ej: Rojo, Azul, Negro)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="imagen">Imagen del Producto:</label>
                        <input type="file" id="imagen" name="imagen" accept="image/*">
                        <small>Tamaño recomendado: 400x400px</small>
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
        function openModal(action, productData = null) {
            const modal = document.getElementById('productModal');
            const modalTitle = document.getElementById('modalTitle');
            const formAction = document.getElementById('formAction');
            const productId = document.getElementById('productId');
            const nombreInput = document.getElementById('nombre');
            const descripcionInput = document.getElementById('descripcion');
            const precioInput = document.getElementById('precio_unitario');
            const cantidadInput = document.getElementById('cantidad');
            const categoriaSelect = document.getElementById('id_categoria');
            const marcasInput = document.getElementById('marcas');
            const descuentoInput = document.getElementById('descuento');
            const tallasInput = document.getElementById('tallas');
            const coloresInput = document.getElementById('colores');
            const imagenInput = document.getElementById('imagen');
            const currentImageInput = document.getElementById('currentImage');
            const imagePreview = document.getElementById('imagePreview');

            formAction.value = action;
            imagenInput.required = (action === 'add'); // Imagen es requerida solo al añadir

            if (action === 'add') {
                modalTitle.textContent = 'Nuevo Producto';
                productId.value = '';
                nombreInput.value = '';
                descripcionInput.value = '';
                precioInput.value = '';
                cantidadInput.value = '';
                categoriaSelect.value = '';
                marcasInput.value = '';
                descuentoInput.value = '0';
                tallasInput.value = '';
                coloresInput.value = '';
                imagenInput.value = ''; // Limpiar input de archivo
                currentImageInput.value = '';
                imagePreview.innerHTML = ''; // Limpiar previsualización
            } else { // action === 'edit'
                modalTitle.textContent = 'Editar Producto';
                productId.value = productData.id;
                nombreInput.value = productData.nombre;
                descripcionInput.value = productData.descripcion;
                precioInput.value = productData.precio_unitario;
                cantidadInput.value = productData.cantidad;
                categoriaSelect.value = productData.id_categoria;
                marcasInput.value = productData.marcas;
                descuentoInput.value = productData.descuento;
                tallasInput.value = productData.tallas;
                coloresInput.value = productData.colores;
                imagenInput.value = ''; // Limpiar input de archivo
                currentImageInput.value = productData.imagen;
                if (productData.imagen) {
                    imagePreview.innerHTML = `<img src="../images/productos/${productData.imagen}" alt="Imagen actual" style="max-width: 100px; max-height: 100px; margin-top: 10px;">`;
                } else {
                    imagePreview.innerHTML = '';
                }
            }
            modal.style.display = 'block';
        }

        function closeModal() {
            document.getElementById('productModal').style.display = 'none';
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('productModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>
