<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Corporativo | Simtec</title>
    <style>
        /* IMPORTACIÓN DE FUENTE MODERNA */
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap');

        :root {
            --magenta: #a50064;
            --magenta-glow: #ff0095;
            --deep-space: #010409;
            --glass-white: rgba(255, 255, 255, 0.04);
            --border-light: rgba(255, 255, 255, 0.1);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body, html {
            height: 100%;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--deep-space);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* FONDO DINÁMICO MEJORADO */
        #bg-canvas {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            z-index: 0;
            background: radial-gradient(circle at 50% 50%, #001a33, #010409);
        }

        /* CONTENEDOR MÁS GRANDE Y ESTILIZADO */
        .main-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 500px; /* Tamaño aumentado */
            padding: 20px;
        }

        .glass-card {
            background: rgba(10, 15, 25, 0.7);
            backdrop-filter: blur(45px);
            -webkit-backdrop-filter: blur(45px);
            border: 1px solid var(--border-light);
            border-radius: 50px; /* Bordes más curvos */
            padding: 70px 50px;
            box-shadow: 0 50px 100px rgba(0, 0, 0, 0.8), 
                        inset 0 0 20px rgba(255, 255, 255, 0.02);
            text-align: center;
            animation: superReveal 1.5s cubic-bezier(0.19, 1, 0.22, 1);
            position: relative;
            overflow: hidden;
        }

        /* EFECTO DE LUZ QUE RECORRE EL BORDE */
        .glass-card::after {
            content: "";
            position: absolute;
            top: -2px; left: -2px; right: -2px; bottom: -2px;
            background: linear-gradient(135deg, transparent, rgba(165, 0, 100, 0.3), transparent);
            z-index: -1;
            border-radius: 50px;
        }

        @keyframes superReveal {
            from { opacity: 0; transform: translateY(60px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* LOGO CON ANIMACIÓN DE PULSO */
        .logo-box {
            margin-bottom: 50px;
            position: relative;
            display: inline-block;
        }

        .brand-logo {
            max-width: 220px; /* Logo más grande */
            filter: drop-shadow(0 0 20px rgba(165, 0, 100, 0.4));
            animation: floating 6s ease-in-out infinite;
        }

        @keyframes floating {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-15px) rotate(1deg); }
        }

        /* TIPOGRAFÍA */
        h2 {
            color: white;
            font-weight: 800;
            font-size: 1.8rem;
            margin-bottom: 10px;
            letter-spacing: -1px;
        }

        p.welcome-text {
            color: rgba(255,255,255,0.4);
            font-size: 0.9rem;
            margin-bottom: 40px;
            font-weight: 500;
        }

        /* INPUTS PREMIUM */
        .input-group {
            margin-bottom: 30px;
            text-align: left;
        }

        .label-style {
            color: var(--magenta-glow);
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 12px;
            display: block;
            padding-left: 10px;
        }

        .custom-input {
            width: 100%;
            background: rgba(255, 255, 255, 0.03);
            border: 2px solid rgba(255, 255, 255, 0.08);
            border-radius: 22px;
            padding: 20px 25px;
            color: white;
            font-size: 1.1rem;
            outline: none;
            transition: all 0.4s ease;
        }

        .custom-input:focus {
            background: rgba(255, 255, 255, 0.07);
            border-color: var(--magenta-glow);
            box-shadow: 0 0 25px rgba(165, 0, 100, 0.3);
            transform: scale(1.02);
        }

        /* BOTÓN DE ACCIÓN MASIVO */
        .btn-action {
            width: 100%;
            padding: 22px;
            background: linear-gradient(90deg, var(--magenta), var(--magenta-glow));
            border: none;
            border-radius: 22px;
            color: white;
            font-weight: 800;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 3px;
            cursor: pointer;
            box-shadow: 0 20px 40px rgba(165, 0, 100, 0.4);
            transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1);
            margin-top: 15px;
        }

        .btn-action:hover {
            transform: translateY(-7px) scale(1.03);
            box-shadow: 0 30px 60px rgba(165, 0, 100, 0.6);
            filter: saturate(1.2);
        }

        /* LINKS */
        .footer-links {
            margin-top: 40px;
        }

        .create-account {
            color: rgba(255,255,255,0.3);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: 0.3s;
        }

        .create-account span { color: var(--magenta-glow); }

        .create-account:hover { color: white; }

        /* ALERTA ERROR */
        .error-banner {
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid rgba(255, 0, 0, 0.2);
            color: #ff5f5f;
            padding: 15px;
            border-radius: 15px;
            margin-bottom: 25px;
            font-size: 0.85rem;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <canvas id="bg-canvas"></canvas>

    <div class="main-container">
        <div class="glass-card">
            <div class="logo-box">
                <img src="logosimtec.png" alt="Simtec" class="brand-logo" onerror="this.src='https://via.placeholder.com/220x80?text=SIMTEC'">
            </div>

            <h2>Acceso al Sistema</h2>
            <p class="welcome-text">Identifícate para gestionar tu jornada.</p>

            <?php if(isset($_GET['error'])): ?>
                <div class="error-banner">❌ Usuario o contraseña incorrectos</div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="input-group">
                    <label class="label-style">ID Usuario</label>
                    <input type="text" name="usuario" class="custom-input" placeholder="Introduce tu usuario" required autofocus>
                </div>

                <div class="input-group">
                    <label class="label-style">Password</label>
                    <input type="password" name="password" class="custom-input" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn-action">Iniciar Sesión</button>
            </form>

            <div class="footer-links">
                <a href="registro.php" class="create-account">¿Eres nuevo? <span>Regístrate ahora</span></a>
            </div>
        </div>
    </div>

    <script>
        const canvas = document.getElementById('bg-canvas');
        const ctx = canvas.getContext('2d');
        let particles = [];

        function init() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            particles = [];
            for (let i = 0; i < 90; i++) {
                particles.push({
                    x: Math.random() * canvas.width,
                    y: Math.random() * canvas.height,
                    vx: (Math.random() - 0.5) * 0.3,
                    vy: (Math.random() - 0.5) * 0.3,
                    size: Math.random() * 2
                });
            }
        }

        function draw() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.lineWidth = 0.8;
            
            for (let i = 0; i < particles.length; i++) {
                let p = particles[i];
                p.x += p.vx; p.y += p.vy;

                if (p.x < 0 || p.x > canvas.width) p.vx *= -1;
                if (p.y < 0 || p.y > canvas.height) p.vy *= -1;

                ctx.fillStyle = 'rgba(165, 0, 100, 0.4)';
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
                ctx.fill();

                for (let j = i + 1; j < particles.length; j++) {
                    let p2 = particles[j];
                    let dist = Math.hypot(p.x - p2.x, p.y - p2.y);
                    if (dist < 180) {
                        ctx.strokeStyle = `rgba(165, 0, 100, ${0.2 * (1 - dist/180)})`;
                        ctx.beginPath();
                        ctx.moveTo(p.x, p.y);
                        ctx.lineTo(p2.x, p2.y);
                        ctx.stroke();
                    }
                }
            }
            requestAnimationFrame(draw);
        }

        window.addEventListener('resize', init);
        init();
        draw();
    </script>
</body>
</html>
