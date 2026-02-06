<?php
session_start();
require 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$mensaje = "";
$tipo_alerta = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pass_actual = $_POST['pass_actual'];
    $pass_nueva = $_POST['pass_nueva'];
    $pass_confirmar = $_POST['pass_confirmar'];
    $usuario_id = $_SESSION['usuario_id'];

    // 1. Obtener la contraseña actual de la DB
    $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $user = $stmt->fetch();

    // 2. Validaciones (Sin restricción de longitud)
    if (!password_verify($pass_actual, $user['password'])) {
        $mensaje = "La contraseña actual es incorrecta.";
        $tipo_alerta = "error";
    } elseif ($pass_nueva !== $pass_confirmar) {
        $mensaje = "Las nuevas contraseñas no coinciden.";
        $tipo_alerta = "error";
    } else {
        // 3. Actualizar en la base de datos
        $nuevo_hash = password_hash($pass_nueva, PASSWORD_BCRYPT);
        $update = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        
        if ($update->execute([$nuevo_hash, $usuario_id])) {
            $mensaje = "Contraseña actualizada con éxito.";
            $tipo_alerta = "success";
        } else {
            $mensaje = "Error al acceder a la base de datos.";
            $tipo_alerta = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguridad | Simtec</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --magenta: #a50064;
            --magenta-glow: #ff0095;
            --bg-deep: #020617;
            --glass: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: radial-gradient(circle at center, #001e3c, var(--bg-deep));
            color: white;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .glass-card {
            background: rgba(10, 15, 25, 0.7);
            backdrop-filter: blur(40px);
            border: 1px solid var(--glass-border);
            border-radius: 45px;
            padding: 60px 50px;
            width: 90%;
            max-width: 480px;
            box-shadow: 0 50px 100px rgba(0,0,0,0.6);
            text-align: center;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 { 
            font-weight: 800; 
            margin-bottom: 35px; 
            font-size: 2rem;
            letter-spacing: -1px;
            background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .input-group { text-align: left; margin-bottom: 25px; }

        label {
            display: block;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            color: var(--magenta-glow);
            margin-bottom: 10px;
            letter-spacing: 1.5px;
            padding-left: 5px;
        }

        .custom-input {
            width: 100%;
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 18px 22px;
            color: white;
            font-size: 1rem;
            outline: none;
            transition: all 0.4s ease;
        }

        .custom-input:focus {
            border-color: var(--magenta-glow);
            background: rgba(255,255,255,0.08);
            box-shadow: 0 0 30px rgba(165, 0, 100, 0.25);
            transform: scale(1.01);
        }

        .btn-save {
            width: 100%;
            padding: 20px;
            background: linear-gradient(90deg, var(--magenta), var(--magenta-glow));
            border: none;
            border-radius: 20px;
            color: white;
            font-weight: 800;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;
            transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            margin-top: 15px;
        }

        .btn-save:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(165, 0, 100, 0.45);
            filter: brightness(1.1);
        }

        .btn-back {
            display: inline-block;
            margin-top: 30px;
            color: rgba(255,255,255,0.4);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-back:hover { color: white; transform: translateX(-5px); }

        .alert {
            padding: 18px;
            border-radius: 20px;
            margin-bottom: 30px;
            font-size: 0.9rem;
            font-weight: 700;
            animation: shake 0.4s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .alert-error { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #ff8e8e; }
        .alert-success { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); color: #4ade80; }
    </style>
</head>
<body>

    <div class="glass-card">
        <h2>Seguridad</h2>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_alerta; ?>">
                <?php echo ($tipo_alerta == 'success' ? '✓ ' : '✕ ') . $mensaje; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <label>Clave Actual</label>
                <input type="password" name="pass_actual" class="custom-input" placeholder="••••••••" required>
            </div>

            <div class="input-group">
                <label>Nueva Clave</label>
                <input type="password" name="pass_nueva" class="custom-input" placeholder="Nueva contraseña" required>
            </div>

            <div class="input-group">
                <label>Confirmar Nueva Clave</label>
                <input type="password" name="pass_confirmar" class="custom-input" placeholder="Repite la contraseña" required>
            </div>

            <button type="submit" class="btn-save">Guardar Cambios</button>
        </form>

        <a href="dashboard.php" class="btn-back">← Volver al Panel</a>
    </div>

</body>
</html>