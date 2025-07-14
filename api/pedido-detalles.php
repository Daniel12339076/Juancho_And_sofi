<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$orden_id = $_GET['id'] ?? 0;

if (!$orden_id) {
echo json_encode(['error' => 'ID de pedido requerido']);
exit();
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
    echo json_encode(['error' => 'Pedido no encontrado']);
    exit();
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

// Preparar respuesta
$response = [
    'orden' => $orden,
    'cliente' => [
        'nombre' => $orden['cliente_nombre'],
        'correo' => $orden['cliente_correo'],
        'celular' => $orden['cliente_celular']
    ],
    'items' => $items
];

echo json_encode($response);

} catch (Exception $e) {
echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>
