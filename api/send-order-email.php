<?php
header('Content-Type: application/json');
require_once '../config/database.php';
// require_once '../vendor/autoload.php'; // Si usas Composer para PHPMailer

// Simulación de PHPMailer o cualquier librería de envío de correo
function sendEmail($to, $subject, $body) {
    // Aquí iría la lógica real para enviar el correo.
    // Por ejemplo, con PHPMailer:
    /*
    $mail = new PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.example.com'; // Tu servidor SMTP
        $mail->SMTPAuth   = true;
        $mail->Username   = 'user@example.com'; // Tu usuario SMTP
        $mail->Password   = 'secret';           // Tu contraseña SMTP
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        //Recipients
        $mail->setFrom('no-reply@tutienda.com', 'Tienda Juancho & Sofi');
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body); // Versión de texto plano

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar correo a $to: {$mail->ErrorInfo}");
        return false;
    }
    */
    
    // Para esta simulación, siempre devuelve true
    error_log("Simulando envío de correo a: $to, Asunto: $subject");
    return true;
}

$data = json_decode(file_get_contents('php://input'), true);
$orden_id = $data['orderId'] ?? 0;

if (!$orden_id) {
    echo json_encode(['success' => false, 'error' => 'ID de pedido requerido']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Obtener información de la orden y el cliente
    $stmt = $db->prepare("
        SELECT o.*, u.nombre as cliente_nombre, u.correo as cliente_correo
        FROM ordenes o 
        JOIN usuarios u ON o.id_usuario = u.id 
        WHERE o.id = ?
    ");
    $stmt->execute([$orden_id]);
    $orden = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$orden) {
        echo json_encode(['success' => false, 'error' => 'Pedido no encontrado']);
        exit();
    }

    // Obtener items del pedido
    $stmt = $db->prepare("
        SELECT v.*, p.nombre, p.imagen 
        FROM ventas v 
        JOIN productos p ON v.id_producto = p.id 
        WHERE v.id_orden = ?
    ");
    $stmt->execute([$orden_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Construir el cuerpo del correo
    $email_body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { width: 100%; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9; }
                .header { background-color: #ffd700; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .header h2 { margin: 0; color: #1a1a1a; }
                .content { padding: 20px; }
                .content p { margin-bottom: 10px; }
                .order-summary { background-color: #fff; border: 1px solid #eee; border-radius: 8px; padding: 15px; margin-top: 20px; }
                .order-summary h4 { color: #ffd700; margin-top: 0; }
                .item-list { list-style: none; padding: 0; }
                .item-list li { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
                .item-list li:last-child { border-bottom: none; }
                .total { font-size: 1.2em; font-weight: bold; text-align: right; margin-top: 15px; color: #1a1a1a; }
                .footer { text-align: center; margin-top: 30px; font-size: 0.8em; color: #777; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Confirmación de Pedido - Tienda Juancho & Sofi</h2>
                </div>
                <div class='content'>
                    <p>Hola, <strong>" . htmlspecialchars($orden['cliente_nombre']) . "</strong>,</p>
                    <p>Gracias por tu compra en Tienda Juancho & Sofi. Tu pedido <strong>#" . htmlspecialchars($orden['codigo']) . "</strong> ha sido recibido y está en estado: <strong>" . htmlspecialchars($orden['estado']) . "</strong>.</p>
                    
                    <div class='order-summary'>
                        <h4>Resumen de tu Pedido</h4>
                        <p><strong>Fecha del Pedido:</strong> " . date('d/m/Y H:i', strtotime($orden['fecha'])) . "</p>
                        <p><strong>Tipo de Venta:</strong> " . ($orden['tipo_venta'] === 'local' ? 'Recogida en Tienda' : 'Envío a Domicilio') . "</p>
                        " . (!empty($orden['direccion_envio']) ? "<p><strong>Dirección de Envío:</strong> " . htmlspecialchars($orden['direccion_envio']) . "</p>" : "") . "
                        <p><strong>Método de Pago:</strong> " . htmlspecialchars($orden['metodo_pago']) . "</p>
                        <p><strong>Teléfono de Contacto:</strong> " . htmlspecialchars($orden['telefono_contacto']) . "</p>
                        
                        <h4>Productos:</h4>
                        <ul class='item-list'>
    ";

    foreach ($items as $item) {
        $email_body .= "
            <li>
                <span>" . htmlspecialchars($item['nombre']) . " (x" . $item['cantidad'] . ")</span>
                <span>$" . number_format($item['valor_total'], 0, ',', '.') . "</span>
            </li>
        ";
    }

    $email_body .= "
                        </ul>
                        <div class='total'>Total del Pedido: $" . number_format($orden['total'], 0, ',', '.') . "</div>
                    </div>
                    
                    <p>Te mantendremos informado sobre el estado de tu pedido.</p>
                    <p>Si tienes alguna pregunta, no dudes en contactarnos.</p>
                    <p>Saludos cordiales,<br>El equipo de Tienda Juancho & Sofi</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " Tienda Juancho & Sofi. Todos los derechos reservados.</p>
                </div>
            </div>
        </body>
        </html>
    ";

    // Enviar el correo
    $to = $orden['cliente_correo'];
    $subject = "Confirmación de Pedido #" . $orden['codigo'] . " - Tienda Juancho & Sofi";

    if (sendEmail($to, $subject, $email_body)) {
        echo json_encode(['success' => true, 'message' => 'Email de confirmación enviado.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al enviar el email de confirmación.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>
