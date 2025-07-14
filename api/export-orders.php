<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Obtener filtros de la URL
$filtro_estado = $_GET['estado'] ?? '';
$filtro_fecha = $_GET['fecha'] ?? '';
$buscar = $_GET['buscar'] ?? '';

// Construir consulta con filtros (similar a admin/pedidos.php)
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

$query = "
    SELECT o.codigo, o.fecha, o.total, o.estado, o.tipo_venta, o.direccion_envio, o.metodo_pago, o.telefono_contacto,
           u.nombre as cliente_nombre, u.correo as cliente_correo, u.celular as cliente_celular
    FROM ordenes o 
    JOIN usuarios u ON o.id_usuario = u.id 
    $where_clause
    ORDER BY o.fecha DESC
";

try {
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($pedidos)) {
        die("No hay pedidos para exportar con los filtros seleccionados.");
    }

    // Nombre del archivo CSV
    $filename = "reporte_pedidos_" . date('Ymd_His') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // Escribir encabezados
    fputcsv($output, array_keys($pedidos[0]));

    // Escribir datos
    foreach ($pedidos as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit();

} catch (Exception $e) {
    die("Error al generar el reporte: " . $e->getMessage());
}
?>
