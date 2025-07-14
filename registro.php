<?php
session_start();
require_once 'config/database.php';

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $celular = $_POST['celular'] ?? '';
    $clave = $_POST['clave'] ?? '';
    $confirm_clave = $_POST['confirm_clave'] ?? '';

    if ($clave !== $confirm_clave) {
        $error = "Las contraseñas no coinciden.";
    } else {
        $database = new Database();
        $db = $database->getConnection();

        // Verificar si el correo ya existe
        $stmt_check = $db->prepare("SELECT id FROM usuarios WHERE correo = ?");
        $stmt_check->execute([$correo]);
        if ($stmt_check->fetch(PDO::FETCH_ASSOC)) {
            $error = "El correo electrónico ya está registrado.";
        } else {
            $hashed_password = password_hash($clave, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO usuarios (nombre, correo, celular, clave, rol) VALUES (?, ?, ?, ?, 'cliente')");
            if ($stmt->execute([$nombre, $correo, $celular, $hashed_password])) {
                $mensaje = "Registro exitoso. Ahora puedes iniciar sesión.";
                // Opcional: iniciar sesión automáticamente
                // $_SESSION['user_id'] = $db->lastInsertId();
                // $_SESSION['user_name'] = $nombre;
                // $_SESSION['rol'] = 'cliente';
                // header("Location: index.php");
                // exit();
            } else {
                $error = "Error al registrar usuario. Intenta nuevamente.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrarse - Tienda Juancho & Sofi</title>
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
                <h2>Crear Cuenta</h2>
                <p>Regístrate para disfrutar de todas nuestras ofertas.</p>
            </div>
            
            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?php echo $mensaje; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="registro.php" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="nombre">Nombre Completo</label>
                    <input type="text" id="nombre" name="nombre" required autocomplete="name">
                </div>
                <div class="form-group">
                    <label for="correo">Correo Electrónico</label>
                    <input type="email" id="correo" name="correo" required autocomplete="email">
                </div>
                <div class="form-group">
                    <label for="celular">Número de Celular</label>
                    <input type="tel" id="celular" name="celular" autocomplete="tel">
                </div>
                <div class="form-group">
                    <label for="clave">Contraseña</label>
                    <input type="password" id="clave" name="clave" required autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="confirm_clave">Confirmar Contraseña</label>
                    <input type="password" id="confirm_clave" name="confirm_clave" required autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Registrarse</button>
            </form>
            
            <div class="auth-footer">
                <p>¿Ya tienes una cuenta? <a href="login.php">Inicia Sesión</a></p>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
</body>
</html>
