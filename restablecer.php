<?php
require_once 'config/database.php';

$token = $_GET['token'] ?? '';
$mensaje = '';
$error = '';

if (!$token) {
    $error = "Token inválido.";
} else {
    $database = new Database();
    $db = $database->getConnection();

    // Buscar usuario por token
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE token_recuperacion = ?");
    $stmt->execute([$token]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        $error = "El enlace de recuperación no es válido o ha expirado.";
    } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $clave = $_POST['clave'] ?? '';
        $clave2 = $_POST['clave2'] ?? '';

        if (strlen($clave) < 6) {
            $error = "La contraseña debe tener al menos 6 caracteres.";
        } elseif ($clave !== $clave2) {
            $error = "Las contraseñas no coinciden.";
        } else {
            // Actualizar contraseña y eliminar token
            $hash = password_hash($clave, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE usuarios SET clave = ?, token_recuperacion = NULL WHERE id = ?");
            $stmt->execute([$hash, $usuario['id']]);
            $mensaje = "¡Contraseña restablecida exitosamente! Ahora puedes iniciar sesión.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer Contraseña - Tienda Juancho & Sofi</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <a href="login.php" class="back-home"><i class="fas fa-arrow-left"></i> Volver al login</a>
                <h2>Restablecer Contraseña</h2>
                <p>Ingresa tu nueva contraseña.</p>
            </div>
            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if (!$mensaje && !$error): ?>
            <form action="restablecer.php?token=<?php echo htmlspecialchars($token); ?>" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="clave">Nueva Contraseña</label>
                    <input type="password" id="clave" name="clave" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="clave2">Repetir Contraseña</label>
                    <input type="password" id="clave2" name="clave2" required minlength="6">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Restablecer</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>