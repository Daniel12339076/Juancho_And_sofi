<?php
require_once 'config/database.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $correo = $_POST['correo'] ?? '';

    if (filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $database = new Database();
        $db = $database->getConnection();

        $stmt = $db->prepare("SELECT id FROM usuarios WHERE correo = ?");
        $stmt->execute([$correo]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            // Generar token seguro
            $token = bin2hex(random_bytes(32));
            // Guardar token en la base de datos
            $stmt = $db->prepare("UPDATE usuarios SET token_recuperacion = ? WHERE correo = ?");
            $stmt->execute([$token, $correo]);


            // Enviar correo con PHPMailer
            $enlace = "http://localhost/tienda-juancho-and-sofi%20(1)/restablecer.php?token=$token";
            $asunto = "Recupera tu contraseña" ;
            $mensajeCorreo = "Hola,\n\nHaz clic en el siguiente enlace para restablecer tu contraseña:\n$enlace\n\nSi no solicitaste este cambio, ignora este mensaje.";

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'tiendajuanchosofi@gmail.com'; // Cambia por tu correo Gmail
                $mail->Password = 'sagp tvqi ecmw nzfc'; // Cambia por tu contraseña de aplicación
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('tiendajuanchosofi@gmail.com', 'Tienda Juancho & Sofi');
                $mail->addAddress($correo);

                $mail->Subject = $asunto;
                $mail->Body = $mensajeCorreo;

                $mail->send();
            } catch (Exception $e) {
                $error = "No se pudo enviar el correo. Error: {$mail->ErrorInfo}";
            }
        }
        $mensaje = "Si el correo está registrado, recibirás instrucciones para restablecer tu contraseña.";
    } else {
        $error = "Por favor ingresa un correo válido.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña - Tienda Juancho & Sofi</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <a href="login.php" class="back-home"><i class="fas fa-arrow-left"></i> Volver al login</a>
                <h2>Recuperar Contraseña</h2>
                <p>Ingresa tu correo electrónico para recibir instrucciones.</p>
            </div>
            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            <form action="recuperar.php" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="correo">Correo Electrónico</label>
                    <input type="email" id="correo" name="correo" required autocomplete="email">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Enviar instrucciones</button>
            </form>
        </div>
    </div>
</body>
</html>