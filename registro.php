<?php
require 'db.php';
$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario']);
    $password = $_POST['password'];

    if (!empty($usuario) && !empty($password)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        try {
            // Nota: El rol es 'empleado' para coincidir con tu lógica anterior de admin/empleado
            $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, password, rol) VALUES (?, ?, 'empleado')");
            $stmt->execute([$usuario, $passwordHash]);
            $mensaje = "<div class='msg success'>✨ ¡Cuenta creada con éxito! <a href='index.php'>Inicia sesión aquí</a></div>";
        } catch (PDOException $e) {
            $mensaje = "<div class='msg error'>❌ El usuario ya existe en el sistema.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro | Simtec Control</title>
    <style>
        /* VARIABLES DE DISEÑO */
        :root {
            --magenta: #a50064;
            --magenta-light: #ff0095;
            --bg-deep: #020617;
            --text-main: #f8fafc;
            --glass: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.12);
        }

        /* RESET & BASE */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background: var(--bg-deep);
            color: var(--text-main);
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden; /* Para las partículas de fondo */
            perspective: 1000px;
        }

        /* FONTO ANIMADO (PARTÍCULAS CSS) */
        .bg-animate {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: -1;
            background: radial-gradient(circle at 50% 50%, #0f172a, #020617);
        }

        .orb {
            position: absolute;
            width: 300px; height: 300px;
            background: var(--magenta);
            filter: blur(120px);
            border-radius: 50%;
            opacity: 0.15;
            animation: move 20s infinite alternate;
        }

        @keyframes move {
            from { transform: translate(-20%, -20%); }
            to { transform: translate(50%, 50%); }
        }

        /* CARD PRINCIPAL (GLASSMORPHISM) */
        .register-card {
            background: var(--glass);
            backdrop-filter: blur(40px);
            -webkit-backdrop-filter: blur(40px);
            border: 1px solid var(--glass-border);
            border-radius: 40px;
            padding: 50px 40px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
            text-align: center;
            animation: cardEntrance 1s cubic-bezier(0.17, 0.67, 0.83, 0.67);
            position: relative;
        }

        @keyframes cardEntrance {
            from { opacity: 0; transform: rotateX(-10deg) translateY(30px); }
            to { opacity: 1; transform: rotateX(0) translateY(0); }
        }

        /* LOGO ESTILIZADO */
        .logo-container {
            margin-bottom: 25px;
            display: inline-block;
            position: relative;
        }

        .logo-img {
            width: 80px; height: 80px;
            border-radius: 22px;
            border: 2px solid var(--magenta);
            padding: 5px;
            background: rgba(0,0,0,0.3);
            box-shadow: 0 0 30px rgba(165, 0, 100, 0.4);
        }

        h1 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 10px;
            letter-spacing: -1px;
        }

        h1 span { color: var(--magenta-light); text-shadow: 0 0 15px rgba(255, 0, 149, 0.3); }

        p.subtitle {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.9rem;
            margin-bottom: 30px;
        }

        /* MENSAJES */
        .msg {
            padding: 15px;
            border-radius: 15px;
            font-size: 0.9rem;
            margin-bottom: 20px;
            animation: slideIn 0.4s ease;
        }
        .success { background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
        .success a { color: white; font-weight: bold; }
        .error { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }

        @keyframes slideIn { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }

        /* FORMULARIO Y INPUTS */
        .form-group {
            position: relative;
            margin-bottom: 20px;
        }

        .icon-svg {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            fill: rgba(255, 255, 255, 0.3);
            transition: 0.3s;
        }

        input {
            width: 100%;
            padding: 18px 20px 18px 55px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--glass-border);
            border-radius: 18px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
            outline: none;
        }

        input:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--magenta-light);
            box-shadow: 0 0 20px rgba(165, 0, 100, 0.15);
        }

        input:focus + .icon-svg { fill: var(--magenta-light); }

        /* BOTÓN DE ACCIÓN */
        .btn-submit {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, var(--magenta) 0%, #6d0042 100%);
            border: none;
            border-radius: 18px;
            color: white;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 10px;
            transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 10px 25px rgba(165, 0, 100, 0.3);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-submit:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 15px 35px rgba(165, 0, 100, 0.5);
            background: linear-gradient(135deg, var(--magenta-light) 0%, var(--magenta) 100%);
        }

        .btn-submit:active { transform: translateY(0); }

        /* LINK INFERIOR */
        .footer-link {
            display: block;
            margin-top: 30px;
            color: rgba(255, 255, 255, 0.4);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: 0.3s;
        }

        .footer-link:hover { color: white; transform: scale(1.05); }

        /* MEDIA QUERIES */
        @media (max-width: 480px) {
            .register-card { padding: 35px 25px; border-radius: 0; height: 100vh; display: flex; flex-direction: column; justify-content: center; }
        }
    </style>
</head>
<body>

    <div class="bg-animate">
        <div class="orb"></div>
        <div class="orb" style="bottom: 0; right: 0; animation-delay: -5s; background: #4f46e5;"></div>
    </div>

    <div class="register-card">
        <div class="logo-container">
            <img src="logo.jpg" class="logo-img" onerror="this.style.display='none'">
        </div>
        
        <h1>Únete a <span>Simtec</span></h1>
        <p class="subtitle">Crea tu cuenta de empleado para comenzar.</p>

        <?php echo $mensaje; ?>

        <form method="POST">
            <div class="form-group">
                <svg class="icon-svg" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                <input type="text" name="usuario" placeholder="Nombre de usuario" autocomplete="off" required>
            </div>

            <div class="form-group">
                <svg class="icon-svg" viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                <input type="password" name="password" placeholder="Contraseña de acceso" required>
            </div>

            <button type="submit" class="btn-submit">Registrarme Ahora</button>

            <a href="index.php" class="footer-link">
                ← Volver al inicio de sesión
            </a>
        </form>
    </div>

</body>
</html>