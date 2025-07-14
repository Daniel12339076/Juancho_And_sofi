<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$productos = [];
$query = "SELECT p.*, c.nombre as categoria_nombre FROM productos p LEFT JOIN categorias c ON p.id_categoria = c.id";
$params = [];

// Filtrar por categoría
if (isset($_GET['categoria_id']) && is_numeric($_GET['categoria_id'])) {
    $query .= " WHERE p.id_categoria = ?";
    $params[] = $_GET['categoria_id'];
}

// Búsqueda por nombre/descripción
if (isset($_GET['buscar']) && !empty($_GET['buscar'])) {
    $search_term = '%' . $_GET['buscar'] . '%';
    if (strpos($query, 'WHERE') !== false) {
        $query .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ?)";
    } else {
        $query .= " WHERE (p.nombre LIKE ? OR p.descripcion LIKE ?)";
    }
    $params[] = $search_term;
    $params[] = $search_term;
}

$query .= " ORDER BY p.fecha_creacion DESC";

try {
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular precio con descuento
    foreach ($productos as &$producto) {
        $producto['precio_final'] = $producto['precio_unitario'];
        if ($producto['descuento'] > 0) {
            $producto['precio_final'] = $producto['precio_unitario'] * (1 - $producto['descuento'] / 100);
        }
        // Formatear precios para la salida JSON si es necesario
        $producto['precio_unitario_formateado'] = number_format($producto['precio_unitario'], 0, ',', '.');
        $producto['precio_final_formateado'] = number_format($producto['precio_final'], 0, ',', '.');
    }

    echo json_encode($productos);

} catch (Exception $e) {
    echo json_encode(['error' => 'Error al obtener productos: ' . $e->getMessage()]);
}
?>
