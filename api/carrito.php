<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Inicializar carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Función para calcular totales
function calcularTotales($carrito) {
    $subtotal = 0;
    $descuentos = 0;
    $totalItems = 0;

    foreach ($carrito as $item) {
        $subtotal += $item['precio_unitario'] * $item['cantidad'];
        $descuentos += ($item['precio_unitario'] - $item['precio_final']) * $item['cantidad'];
        $totalItems += $item['cantidad'];
    }

    $total = $subtotal - $descuentos;

    return [
        "subtotal" => $subtotal,
        "descuentos" => $descuentos,
        "total" => $total,
        "total_items" => $totalItems
    ];
}

// Respuesta base
$response = [
    "success" => false,
    "message" => "",
    "carrito" => $_SESSION['carrito'],
    "totales" => calcularTotales($_SESSION['carrito'])
];

// GET → devolver carrito y totales
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $response["success"] = true;
    $response["carrito"] = $_SESSION['carrito'];
    $response["totales"] = calcularTotales($_SESSION['carrito']);
    echo json_encode($response);
    exit;
}

// POST → manejar add/update/remove/clear
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $action = $data['action'] ?? '';
    $productId = $data['productId'] ?? null;
    $quantity = $data['quantity'] ?? 1;

    try {
        switch ($action) {
            case "add":
                if ($productId) {
                    $stmt = $db->prepare("SELECT id, nombre, precio_unitario, imagen, cantidad as stock, descuento 
                                          FROM productos WHERE id = ?");
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
                                'stock_disponible' => $product['stock']
                            ];
                            $response['success'] = true;
                            $response['message'] = 'Producto añadido al carrito.';
                        }
                    } else {
                        $response['message'] = 'Producto no encontrado.';
                    }
                }
                break;

            case "update":
                if ($productId && isset($_SESSION['carrito'][$productId])) {
                    $stmt = $db->prepare("SELECT cantidad as stock FROM productos WHERE id = ?");
                    $stmt->execute([$productId]);
                    $product_stock = $stmt->fetchColumn();

                    if ($quantity > $product_stock) {
                        $response['message'] = 'No hay suficiente stock disponible. Stock actual: ' . $product_stock;
                    } elseif ($quantity <= 0) {
                        unset($_SESSION['carrito'][$productId]);
                        $response['success'] = true;
                        $response['message'] = 'Producto eliminado del carrito.';
                    } else {
                        $_SESSION['carrito'][$productId]['cantidad'] = $quantity;
                        $response['success'] = true;
                        
                        $response['message'] = 'Cantidad actualizada.';
                    }
                } else {
                    $response['message'] = 'Producto no encontrado en el carrito.';
                }
                break;

            case "remove":
                if ($productId && isset($_SESSION['carrito'][$productId])) {
                    unset($_SESSION['carrito'][$productId]);
                    $response['success'] = true;
                    $response['message'] = 'Producto eliminado del carrito.';
                } else {
                    $response['message'] = 'Producto no encontrado en el carrito.';
                }
                break;

            case "clear":
                $_SESSION['carrito'] = [];
                $response['success'] = true;
                $response['message'] = 'Carrito vaciado.';
                break;

            default:
                $response['message'] = "Acción desconocida.";
        }
    } catch (Exception $e) {
        $response['message'] = "Error: " . $e->getMessage();
    }

    // Siempre devolver carrito y totales
    $response["carrito"] = $_SESSION['carrito'];
    $response["totales"] = calcularTotales($_SESSION['carrito']);
    echo json_encode($response);
    exit;
}
