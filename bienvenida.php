<?php
session_start();
require 'db.php';

// Si no hay sesión iniciada, mandarlo al login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass1 = $_POST['pass1'];
    $pass2 = $_POST['pass2'];

    if (empty($pass1) || empty($pass2)) {
        $error = "Por favor, completa ambos campos.";
    } elseif (strlen($pass1) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } elseif ($pass1 !== $pass2) {
        $error = "Las contraseñas no coinciden.";
    } else {
        // Encriptamos
        $hashed_password = password_hash($pass1, PASSWORD_BCRYPT);
        
        // Actualizamos en la tabla USUARIOS
        $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        
        if ($stmt->execute([$hashed_password, $user_id])) {
            // IMPORTANTE: Después de cambiar la clave, lo mandamos al dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Error al guardar en la base de datos.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bienvenida | Simtec</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: radial-gradient(circle, #003366, #000a1a); height: 100vh; display: flex; align-items: center; justify-content: center; color: white; font-family: sans-serif; }
        .glass-card { background: rgba(255,255,255,0.05); backdrop-filter: blur(15px); padding: 40px; border-radius: 30px; border: 1px solid rgba(255,255,255,0.1); width: 100%; max-width: 400px; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.4); }
        .form-control { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.1); color: white; border-radius: 12px; margin-bottom: 15px; padding: 12px; }
        .form-control:focus { background: rgba(255,255,255,0.2); color: white; border-color: #ff0095; }
        .btn-update { background: linear-gradient(45deg, #a50064, #ff0095); border: none; color: white; width: 100%; padding: 12px; font-weight: bold; border-radius: 12px; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="glass-card">
        <i class="fas fa-user-shield fa-3x mb-3" style="color: #ff0095;"></i>
        <h3 class="fw-bold">¡Bienvenido!</h3>
        <p class="text-white-50 small mb-4">Por ser tu primer ingreso, asigna una contraseña personal para activar tu cuenta.</p>
        
        <?php if($error): ?>
            <div class="alert alert-danger py-2 small" style="background: rgba(255,0,0,0.2); border: none; color: #ffcccc;">
                <i class="fas fa-times-circle me-1"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <input type="password" name="pass1" class="form-control" placeholder="Nueva Contraseña" required>
            <input type="password" name="pass2" class="form-control" placeholder="Confirma Contraseña" required>
            <button type="submit" class="btn btn-update">Activar Cuenta <i class="fas fa-arrow-right ms-2"></i></button>
        </form>
    </div>
</body>
</html>