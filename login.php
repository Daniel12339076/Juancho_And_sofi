<?php
session_start();
require_once 'config/database.php';

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $correo = $_POST['correo'] ?? '';
    $clave = $_POST['clave'] ?? '';

    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("SELECT * FROM usuarios WHERE correo = ?");
    $stmt->execute([$correo]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($clave, $usuario['clave'])) {
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['user_name'] = $usuario['nombre'];
        $_SESSION['rol'] = $usuario['rol'];
        $_SESSION['correo'] = $usuario['correo'];
        $_SESSION['celular'] = $usuario['celular'];

        if ($usuario['rol'] === 'administrador') {
            header("Location: admin/index.php");
        } else {
            header("Location: index.php");

        }
        exit();
    } else {
        $error = "Correo o contraseña incorrectos.";
    }
}

// Mensaje de logout
if (isset($_GET['logout']) && $_GET['logout'] == 'success') {
    $mensaje = "Has cerrado sesión exitosamente.";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Tienda Juancho & Sofi</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <a href="index.php" class="back-home"><i class="fas fa-arrow-left"></i> Volver al inicio</a>
                <br>
                <img src="Logo/Logo juancho.png" alt="Logo" class="auth-logo">
                <h2>Iniciar Sesión</h2>
                <p>Accede a tu cuenta para continuar comprando.</p>
            </div>
            
            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="correo">Correo Electrónico</label>
                    <input type="email" id="correo" name="correo" required autocomplete="email">
                </div>
                <div class="form-group">
                    <label for="clave">Contraseña</label>
                    <input type="password" id="clave" name="clave" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Iniciar Sesión</button>
            </form>
            
            <div class="auth-footer">
                <p>¿No tienes una cuenta? <a href="registro.php">Regístrate aquí</a></p>
                <p><a href="recuperar.php">¿Olvidaste tu contraseña?</a></p>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
</body>
</html>
