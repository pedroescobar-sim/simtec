<?php
session_start();
require 'db.php'; 

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$fecha_hoy = date('Y-m-d');

// --- PROCESAR ACTUALIZACIÓN DE DATOS (Mantiene tu lógica de BD) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar_datos'])) {
    $nombre = $_POST['nombre'];
    $ap1 = $_POST['apellido1'];
    $ap2 = $_POST['apellido2'];
    $dni = $_POST['dni'];
    $fnac = $_POST['fecha_nacimiento'];
    $empresa = $_POST['empresa'];

    $check = $pdo->prepare("SELECT id FROM datos_personales WHERE usuario_id = ?");
    $check->execute([$usuario_id]);
    
    if ($check->fetch()) {
        $sql = "UPDATE datos_personales SET nombre=?, apellido1=?, apellido2=?, dni=?, fecha_nacimiento=?, empresa=? WHERE usuario_id=?";
        $pdo->prepare($sql)->execute([$nombre, $ap1, $ap2, $dni, $fnac, $empresa, $usuario_id]);
    } else {
        $sql = "INSERT INTO datos_personales (usuario_id, nombre, apellido1, apellido2, dni, fecha_nacimiento, empresa) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$usuario_id, $nombre, $ap1, $ap2, $dni, $fnac, $empresa]);
    }
    header("Location: dashboard.php?status=updated");
    exit();
}

// --- OBTENER DATOS DE PERFIL Y ASISTENCIA ---
$stmt_p = $pdo->prepare("SELECT * FROM datos_personales WHERE usuario_id = ?");
$stmt_p->execute([$usuario_id]);
$perfil = $stmt_p->fetch();

$empresas_opciones = ["SIMTEC INGENIERIA","ACCIONA AGUA", "FIVES STEEL", "IDOM", "MAIER", "SUEZ", "ODOAN", "FULCRUM", "HIDROHAMBIENTE", "MB-SISTEMAS"];

$stmt = $pdo->prepare("SELECT * FROM asistencias WHERE usuario_id = ? AND fecha = ?");
$stmt->execute([$usuario_id, $fecha_hoy]);
$asistencia = $stmt->fetch();

$ya_entro = ($asistencia && $asistencia['hora_entrada']) ? true : false;
$ya_salio = ($asistencia && $asistencia['estado'] == 'finalizado') ? true : false;
$en_descanso = ($asistencia && $asistencia['estado'] == 'descanso') ? true : false;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Simtec - Control Horario</title>
    <style>
        :root { 
            --magenta: #a50064; 
            --magenta-glow: #ff0095;
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text: #f8fafc;
            --success: #22c55e;
            --warning: #eab308;
            --danger: #ef4444;
            --info: #3b82f6;
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        body { 
            margin: 0; padding: 0;
            background-color: var(--bg);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: var(--text);
            min-height: 100vh;
        }

        /* --- NAVBAR ORIGINAL --- */
        .navbar { 
            width: 100%; background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(20px);
            border-bottom: 2px solid var(--magenta); padding: 12px 0;
            position: sticky; top: 0; z-index: 1000;
        }
        .nav-container { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 20px; }
        
        .nav-right { display: flex; gap: 12px; }

        .btn-historial {
            background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); color: white;
            padding: 10px 16px; border-radius: 12px; text-decoration: none; font-weight: 600;
            font-size: 0.85rem; display: flex; align-items: center; gap: 8px; transition: 0.3s;
        }
        .btn-historial:hover { border-color: white; background: rgba(255,255,255,0.1); }

        .dropdown { position: relative; display: inline-block; }
        .dropbtn {
            background: var(--magenta); color: white; padding: 10px 20px;
            border-radius: 12px; border: none; font-weight: 700; cursor: pointer;
            display: flex; align-items: center; gap: 8px; font-family: inherit;
        }
        .dropdown-content {
            display: none; position: absolute; right: 0; background: #1e293b;
            min-width: 220px; border: 1px solid var(--magenta); border-radius: 15px;
            margin-top: 10px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .dropdown-content button, .dropdown-content a {
            width: 100%; padding: 12px 16px; border: none; background: none; color: white;
            text-align: left; cursor: pointer; font-family: inherit; font-size: 0.9rem;
            display: block; text-decoration: none; transition: 0.2s;
        }
        .dropdown-content button:hover, .dropdown-content a:hover { background: rgba(165, 0, 100, 0.2); }
        .show { display: block; }

        /* --- DASHBOARD --- */
        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        
        .hero {
            text-align: center; padding: 40px;
            background: linear-gradient(135deg, rgba(165,0,100,0.1) 0%, rgba(30,41,59,0.5) 100%);
            border-radius: 30px; border: 1px solid var(--glass-border); margin-bottom: 40px;
        }
        #reloj { font-size: 4.5rem; font-weight: 800; margin: 0; text-shadow: 0 0 20px rgba(165,0,100,0.3); }
        .fecha { color: var(--magenta-glow); font-weight: 600; letter-spacing: 3px; margin-top: 10px; text-transform: uppercase;}

        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 25px; }
        
        .card-btn {
            background: var(--card-bg); border: 1px solid var(--glass-border);
            border-radius: 25px; padding: 35px; text-decoration: none; color: white;
            text-align: center; transition: all 0.3s ease; display: flex; flex-direction: column; align-items: center; gap: 15px;
        }
        .card-btn:hover { transform: translateY(-5px); border-color: rgba(255,255,255,0.3); box-shadow: 0 10px 25px rgba(0,0,0,0.3); }

        .entrada { border-bottom: 5px solid var(--success); }
        .pausa { border-bottom: 5px solid var(--warning); }
        .reanudar { border-bottom: 5px solid var(--info); }
        .salida { border-bottom: 5px solid var(--danger); }
        .disabled { opacity: 0.2; pointer-events: none; filter: grayscale(1); }

        /* --- MODAL Y FORMULARIO (RESTABLECIDO) --- */
        .modal {
            position: fixed; top:0; left:0; width:100%; height:100%;
            background: rgba(0,0,0,0.85); backdrop-filter: blur(5px);
            display: none; justify-content: center; align-items: center; z-index: 2000;
        }
        .modal-body {
            background: #1e293b; padding: 30px; border-radius: 24px;
            width: 90%; max-width: 480px; border: 1px solid var(--magenta);
        }
        label { display: block; font-size: 0.75rem; color: #94a3b8; margin-bottom: 5px; margin-left: 5px; }
        input, select {
            width: 100%; padding: 12px; margin-bottom: 15px; background: #0f172a;
            border: 1px solid #334155; border-radius: 10px; color: white; box-sizing: border-box; font-family: inherit;
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="nav-container">
        <a href="#" style="text-decoration:none; color:white; font-weight:800; display:flex; align-items:center; font-size:1.2rem;">
            <img src="logo.jpg" height="30" style="margin-right:10px; border-radius:4px;"> SIMTEC
        </a>
        
        <div class="nav-right">
            <a href="calendario.php" class="btn-historial">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20a2 2 0 002 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10z"/></svg>
                Historial
            </a>

            <div class="dropdown">
                <button onclick="toggleDrop()" class="dropbtn">
                    MI CUENTA <svg width="10" height="10" fill="white" viewBox="0 0 24 24" style="margin-left:5px;"><path d="M7 10l5 5 5-5z"/></svg>
                </button>
                <div id="dropMenu" class="dropdown-content">
                    <button onclick="abrirModal()">Actualizar Mis Datos</button>
                    <a href="cambiar_password.php">Cambiar Contraseña</a>
                    <a href="logout.php" style="color:var(--danger); border-top:1px solid rgba(255,255,255,0.1)">Cerrar Sesión</a>
                </div>
            </div>
        </div>
    </div>
</nav>

<div class="container">
    <div class="hero">
        <div id="reloj">00:00:00</div>
        <div class="fecha"><?php echo date('d / m / Y'); ?></div>
    </div>

    <div class="grid">
        <a href="guardar_registro.php?tipo=entrada" class="card-btn entrada <?php echo $ya_entro ? 'disabled' : ''; ?>">
            <span style="font-size:3rem;">🚀</span>
            <strong>INICIAR DÍA</strong>
        </a>

        <?php if (!$en_descanso): ?>
            <a href="guardar_registro.php?tipo=descanso" class="card-btn pausa <?php echo (!$ya_entro || $ya_salio) ? 'disabled' : ''; ?>">
                <span style="font-size:3rem;">☕</span>
                <strong>PAUSA</strong>
            </a>
        <?php else: ?>
            <a href="guardar_registro.php?tipo=reanudar" class="card-btn reanudar">
                <span style="font-size:3rem;">⚡</span>
                <strong>REANUDAR</strong>
            </a>
        <?php endif; ?>

        <a href="#" onclick="confirmar(event)" class="card-btn salida <?php echo (!$ya_entro || $ya_salio || $en_descanso) ? 'disabled' : ''; ?>">
            <span style="font-size:3rem;">🏁</span>
            <strong>TERMINAR</strong>
        </a>
    </div>
</div>

<div id="modalDatos" class="modal">
    <div class="modal-body">
        <h3 style="margin-top:0; color:var(--magenta-glow); text-align:center;">DATOS PERSONALES</h3>
        
        <form method="POST">
            <label>EMPRESA DESTINO</label>
            <select name="empresa">
                <?php foreach ($empresas_opciones as $op): ?>
                    <option value="<?= $op ?>" <?= ($perfil['empresa']??'')==$op?'selected':'' ?>><?= $op ?></option>
                <?php endforeach; ?>
            </select>

            <label>NOMBRE</label>
            <input type="text" name="nombre" value="<?= htmlspecialchars($perfil['nombre']??'') ?>" required>

            <div style="display:flex; gap:10px">
                <div style="flex:1">
                    <label>1º APELLIDO</label>
                    <input type="text" name="apellido1" value="<?= htmlspecialchars($perfil['apellido1']??'') ?>" required>
                </div>
                <div style="flex:1">
                    <label>2º APELLIDO</label>
                    <input type="text" name="apellido2" value="<?= htmlspecialchars($perfil['apellido2']??'') ?>" required>
                </div>
            </div>

            <div style="display:flex; gap:10px">
                <div style="flex:1">
                    <label>DNI / NIE</label>
                    <input type="text" name="dni" value="<?= htmlspecialchars($perfil['dni']??'') ?>" required>
                </div>
                <div style="flex:1">
                    <label>F. NACIMIENTO</label>
                    <input type="date" name="fecha_nacimiento" value="<?= $perfil['fecha_nacimiento']??'' ?>" required>
                </div>
            </div>

            <div style="margin-top:10px;">
                <button type="submit" name="guardar_datos" style="background:var(--magenta); color:white; border:none; padding:15px; width:100%; border-radius:12px; font-weight:700; cursor:pointer; font-size:1rem;">
                    GUARDAR CAMBIOS
                </button>
                <button type="button" onclick="cerrarModal()" style="background:none; border:none; color:#94a3b8; width:100%; margin-top:10px; cursor:pointer;">
                    Volver al panel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Reloj en tiempo real
    function updateClock() {
        const now = new Date();
        document.getElementById('reloj').innerText = now.toLocaleTimeString('es-ES', {hour12:false});
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Lógica Dropdown
    function toggleDrop() { document.getElementById("dropMenu").classList.toggle("show"); }
    window.onclick = function(event) {
        if (!event.target.matches('.dropbtn')) {
            var dropdowns = document.getElementsByClassName("dropdown-content");
            for (var i = 0; i < dropdowns.length; i++) {
                if (dropdowns[i].classList.contains('show')) dropdowns[i].classList.remove('show');
            }
        }
    }

    // Lógica Modales
    function abrirModal() { document.getElementById('modalDatos').style.display = 'flex'; }
    function cerrarModal() { document.getElementById('modalDatos').style.display = 'none'; }

    // Confirmación de Salida
    function confirmar(e) {
        e.preventDefault();
        if(confirm("¿Estás seguro de que quieres finalizar tu jornada laboral?")) {
            window.location.href = "guardar_registro.php?tipo=salida";
        }
    }
</script>

</body>
</html>