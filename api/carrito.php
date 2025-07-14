<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Inicializar el carrito en la sesión si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$response = ['success' => false, 'message' => '', 'carrito' => $_SESSION['carrito']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    $productId = $data['productId'] ?? null;
    $quantity = $data['quantity'] ?? 1;

    if ($action === 'add' && $productId) {
        try {
            $stmt = $db->prepare("SELECT id, nombre, precio_unitario, imagen, cantidad as stock, descuento FROM productos WHERE id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($product) {
                $current_quantity_in_cart = $_SESSION['carrito'][$productId]['cantidad'] ?? 0;
                $requested_quantity = $current_quantity_in_cart + $quantity;

                if ($requested_quantity > $product['stock']) {
                    $response['message'] = 'No hay suficiente stock disponible para ' . htmlspecialchars($product['nombre']) . '. Stock actual: ' . $product['stock'];
                } else {
                    $precio_final = $product['precio_unitario'];
                    if ($product['descuento'] > 0) {
                        $precio_final = $product['precio_unitario'] * (1 - $product['descuento'] / 100);
                    }

                    $_SESSION['carrito'][$productId] = [
                        'id' => $product['id'],
                        'nombre' => $product['nombre'],
                        'imagen' => $product['imagen'],
                        'precio_unitario' => $product['precio_unitario'],
                        'descuento' => $product['descuento'],
                        'precio_final' => $precio_final,
                        'cantidad' => $requested_quantity,
                        'stock_disponible' => $product['stock'] // Para referencia en el frontend
                    ];
                    $response['success'] = true;
                    $response['message'] = 'Producto añadido al carrito.';
                }
            } else {
                $response['message'] = 'Producto no encontrado.';
            }
        } catch (Exception $e) {
            $response['message'] = 'Error al añadir producto: ' . $e->getMessage();
        }
    } elseif ($action === 'update' && $productId) {
        if (isset($_SESSION['carrito'][$productId])) {
            try {
                $stmt = $db->prepare("SELECT cantidad as stock FROM productos WHERE id = ?");
                $stmt->execute([$productId]);
                $product_stock = $stmt->fetchColumn();

                if ($quantity > $product_stock) {
                    $response['message'] = 'No hay suficiente stock disponible. Stock actual: ' . $product_stock;
                } else {
                    $_SESSION['carrito'][$productId]['cantidad'] = $quantity;
                    if ($quantity <= 0) {
                        unset($_SESSION['carrito'][$productId]);
                    }
                    $response['success'] = true;
                    $response['message'] = 'Cantidad actualizada.';
                }
            } catch (Exception $e) {
                $response['message'] = 'Error al actualizar cantidad: ' . $e->getMessage();
            }
        } else {
            $response['message'] = 'Producto no encontrado en el carrito.';
        }
    } elseif ($action === 'remove' && $productId) {
        if (isset($_SESSION['carrito'][$productId])) {
            unset($_SESSION['carrito'][$productId]);
            $response['success'] = true;
            $response['message'] = 'Producto eliminado del carrito.';
        } else {
            $response['message'] = 'Producto no encontrado en el carrito.';
        }
    } elseif ($action === 'clear') {
        $_SESSION['carrito'] = [];
        $response['success'] = true;
        $response['message'] = 'Carrito vaciado.';
    }

    // Importante: No usar array_values() aquí para mantener los IDs de producto como claves
    $response['carrito'] = $_SESSION['carrito']; 
    echo json_encode($response);

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Devolver el contenido actual del carrito
    $response['success'] = true;
    $response['carrito'] = $_SESSION['carrito']; // Mantener como array asociativo
    echo json_encode($response);
}
?>
