<?php
session_start();
require 'db.php';

// 1. Verificación de seguridad (Solo Admin)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$id_empleado = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT usuario FROM usuarios WHERE id = ? AND rol != 'admin'");
$stmt->execute([$id_empleado]);
$empleado = $stmt->fetch();

if (!$empleado) {
    die("Usuario no encontrado.");
}

$mensaje = "";

// 2. Procesar el cambio
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nueva_pass = $_POST['nueva_pass'];
    
    if ($nueva_pass !== "") {
        $pass_hash = password_hash($nueva_pass, PASSWORD_BCRYPT);
        $update = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        
        if ($update->execute([$pass_hash, $id_empleado])) {
            $mensaje = "<div class='alert alert-success' style='background:rgba(25,135,84,0.2); color:#2ecc71; border:1px solid #2ecc71; border-radius:12px;'>✅ ¡Listo! Contraseña actualizada.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Simtec</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --magenta: #a50064; --bg-dark: #000a1a; }
        body { 
            background: radial-gradient(circle at top left, #001f3f, var(--bg-dark)) no-repeat fixed;
            color: white; font-family: 'Plus Jakarta Sans', sans-serif;
            display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0;
            flex-direction: column;
        }
        
        /* Botón Volver Superior */
        .btn-back-top {
            position: absolute; top: 20px; left: 20px;
            background: rgba(255,255,255,0.05); color: white;
            border: 1px solid rgba(255,255,255,0.1); padding: 8px 15px;
            border-radius: 12px; text-decoration: none; font-size: 0.8rem;
            font-weight: 600; transition: 0.3s;
        }
        .btn-back-top:hover { background: var(--magenta); border-color: var(--magenta); color: white; }

        .card-reset {
            background: rgba(255,255,255,0.05); backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1); border-radius: 25px;
            padding: 40px; width: 90%; max-width: 400px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
        }
        .form-control {
            background: rgba(0,0,0,0.3) !important; border: 1px solid rgba(255,255,255,0.1) !important;
            color: white !important; padding: 12px; border-radius: 12px; font-weight: 600; text-align: center;
        }
        .btn-magenta {
            background: var(--magenta); color: white; border: none; padding: 12px;
            border-radius: 12px; font-weight: 700; width: 100%; transition: 0.3s;
            text-transform: uppercase; letter-spacing: 1px;
        }
        .btn-magenta:hover { background: #ff0095; transform: scale(1.02); }
        
        .btn-gen {
            background: rgba(0, 242, 255, 0.1); color: #00f2ff; border: 1px solid #00f2ff;
            padding: 4px 12px; border-radius: 8px; font-size: 0.7rem; font-weight: 800;
            cursor: pointer; transition: 0.3s;
        }
        .btn-gen:hover { background: #00f2ff; color: black; }
    </style>
</head>
<body>

<a href="admin_dashboard.php" class="btn-back-top">
    <i class="fas fa-chevron-left me-2"></i> Volver al Panel
</a>

<div class="card-reset text-center">
    <div class="mb-4">
        <div class="rounded-circle bg-dark d-inline-flex align-items-center justify-content-center mb-3" style="width:70px; height:70px; border: 2px solid #00f2ff;">
            <i class="fas fa-key fa-2x" style="color: #00f2ff;"></i>
        </div>
        <h4 class="fw-bold m-0">Cambiar Clave</h4>
        <p class="text-white-50 small mt-2">Usuario: <span class="text-white fw-bold"><?= htmlspecialchars($empleado['usuario']) ?></span></p>
    </div>

    <?= $mensaje ?>

    <form method="POST">
        <div class="mb-4 text-start">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="small fw-bold text-white-50">NUEVA CONTRASEÑA</label>
                
            </div>
            <input type="text" name="nueva_pass" id="pass_input" class="form-control" placeholder="Escribe aquí..." required autocomplete="off">
        </div>

        <button type="submit" class="btn-magenta">Confirmar Cambio</button>
    </form>
</div>

<script>
    function generarClave() {
        const chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
        let pass = "";
        for (let i = 0; i < 6; i++) {
            pass += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        document.getElementById('pass_input').value = pass;
    }
</script>

</body>
</html>