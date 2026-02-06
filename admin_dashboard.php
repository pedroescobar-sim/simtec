<?php
// 1. Iniciar sesión y base de datos
session_start();
require 'db.php';

// 2. Control de acceso
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php"); 
    exit();
}

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$nombre_admin = $_SESSION['usuario'] ?? 'Admin'; 
$fecha_hoy = date('Y-m-d');

// 3. Consulta de asistencia de hoy para el Admin
$stmtAsis = $pdo->prepare("SELECT * FROM asistencias WHERE usuario_id = ? AND fecha = ?");
$stmtAsis->execute([$usuario_id, $fecha_hoy]);
$asistencia = $stmtAsis->fetch();

$ya_entro = ($asistencia && $asistencia['hora_entrada']) ? true : false;
$ya_salio = ($asistencia && $asistencia['hora_salida']) ? true : false;
$en_descanso = ($asistencia && $asistencia['estado'] == 'descanso') ? true : false;

// 4. Lista de empleados con JOIN para que el buscador tenga datos reales
$stmtEmp = $pdo->query("SELECT u.id, u.usuario, dp.nombre, dp.apellido1, dp.apellido2, dp.empresa 
                        FROM usuarios u 
                        LEFT JOIN datos_personales dp ON u.id = dp.usuario_id 
                        WHERE u.rol != 'admin' 
                        ORDER BY u.usuario ASC");
$empleados = $stmtEmp->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Core | Simtec Ingeniería</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { 
            --simtec-magenta: #a50064; 
            --bg-dark: #000a1a;
            --glass: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.1);
            --info-blue: #3b82f6;
        }

        body { 
            background: radial-gradient(circle at top left, #001f3f, var(--bg-dark)) no-repeat fixed;
            color: white; font-family: 'Plus Jakarta Sans', sans-serif; min-height: 100vh; margin: 0;
        }

        .nav-simtec { 
            background: rgba(0,0,0,0.4); backdrop-filter: blur(15px);
            border-bottom: 1px solid var(--glass-border); padding: 12px 0;
        }

        .admin-section { 
            background: var(--glass); border-radius: 25px; padding: 25px; 
            border: 1px solid var(--glass-border); backdrop-filter: blur(10px);
        }

        .search-box {
            position: relative; margin-bottom: 20px;
        }
        .search-box input {
            background: rgba(0,0,0,0.4) !important; border: 1px solid var(--glass-border) !important;
            color: white !important; padding: 15px 15px 15px 45px; border-radius: 15px; width: 100%;
        }
        .search-box i {
            position: absolute; left: 15px; top: 50%; transform: translateY(-50%);
            color: var(--simtec-magenta); font-size: 1.2rem;
        }

        .scroll-area { max-height: 600px; overflow-y: auto; padding-right: 5px; }
        .scroll-area::-webkit-scrollbar { width: 5px; }
        .scroll-area::-webkit-scrollbar-thumb { background: var(--simtec-magenta); border-radius: 10px; }

        .btn-action {
            padding: 8px 12px; border-radius: 10px; font-weight: 600; font-size: 0.75rem;
            text-decoration: none; transition: 0.3s; display: inline-flex; align-items: center; justify-content: center;
            min-width: 100px;
        }
        .btn-gest { background: rgba(59, 130, 246, 0.1); border: 1px solid var(--info-blue); color: var(--info-blue); }
        .btn-gest:hover { background: var(--info-blue); color: white; }
        
        .btn-pass { background: transparent; border: 1px solid #ffc107; color: #ffc107; }
        .btn-pass:hover { background: #ffc107; color: black; }
        
        .btn-hist { background: var(--simtec-magenta); color: white; border: none; }
        .btn-hist:hover { background: #ff0095; color: white; }

        .user-row {
            background: rgba(255,255,255,0.02); border-radius: 15px;
            margin-bottom: 10px; padding: 15px; border: 1px solid transparent;
            transition: 0.3s;
        }
        .user-row:hover { border-color: rgba(165, 0, 100, 0.4); background: rgba(255,255,255,0.05); }

        .badge-empresa {
            font-size: 0.65rem; background: rgba(255,255,255,0.1); padding: 2px 8px; border-radius: 5px; color: #aaa;
        }

        @media (max-width: 768px) {
            .user-row { flex-direction: column; align-items: flex-start !important; }
            .actions-group { width: 100%; margin-top: 15px; display: flex; gap: 5px; flex-wrap: wrap; }
            .btn-action { flex: 1; }
        }
    </style>
</head>
<body>

<nav class="nav-simtec mb-4">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <span class="fw-bold" style="letter-spacing: 1px; color: var(--simtec-magenta);">SIMTEC</span>
            <span class="ms-2 fw-light">SISTEMA ADMIN</span>
        </div>
        <a href="logout.php" class="btn btn-sm btn-outline-danger rounded-pill px-3">Cerrar Sesión</a>
    </div>
</nav>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-11">
            <div class="admin-section">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold m-0 text-uppercase">Ingenieros Registrados</h5>
                    <span class="badge bg-dark border border-secondary"><?=count($empleados)?> Total</span>
                </div>

                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="inputNombre" placeholder="Buscar por nombre, apellido, usuario o empresa...">
                </div>

                <div class="scroll-area">
                    <div id="listaUsuarios">
                        <?php foreach ($empleados as $e): 
                            $nombre_real = trim(($e['nombre'] ?? '') . ' ' . ($e['apellido1'] ?? '') . ' ' . ($e['apellido2'] ?? ''));
                            $identificador = !empty($nombre_real) ? $nombre_real : $e['usuario'];
                            $empresa = $e['empresa'] ?? 'SIN ASIGNAR';
                        ?>
                        <div class="user-row d-flex justify-content-between align-items-center fila-ingeniero">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-dark d-flex align-items-center justify-content-center me-3" style="width:45px; height:45px; border: 1px solid var(--glass-border);">
                                    <i class="fas fa-user-gear text-white-50"></i>
                                </div>
                                <div class="info-busqueda">
                                    <h6 class="nombre-u m-0 fw-bold"><?=htmlspecialchars($identificador)?></h6>
                                    <div class="d-flex align-items-center gap-2">
                                        <small class="text-white-50">@<?=htmlspecialchars($e['usuario'])?></small>
                                        <span class="badge-empresa"><?=htmlspecialchars($empresa)?></span>
                                    </div>
                                </div>
                                
                            </div>
                            
                            <div class="actions-group d-flex gap-2">
                                <a href="editar_usuario.php?id=<?=$e['id']?>" class="btn-action btn-gest">
                                    <i class="fas fa-user-edit me-2"></i> GESTIÓN
                                </a>

                                <a href="resetear_contrasena.php?id=<?=$e['id']?>" class="btn-action btn-pass">
                                    <i class="fas fa-key me-2"></i> CLAVE
                                </a>

                                <a href="ver_calendario_empleado.php?id=<?=$e['id']?>" class="btn-action btn-hist">
                                    <i class="fas fa-clock-rotate-left me-2"></i> HISTORIAL
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('inputNombre').addEventListener('input', function() {
        let filtro = this.value.toLowerCase().trim();
        let filas = document.querySelectorAll('.fila-ingeniero');

        filas.forEach(fila => {
            let textoFila = fila.querySelector('.info-busqueda').innerText.toLowerCase();
            if (textoFila.includes(filtro)) {
                fila.style.setProperty('display', 'flex', 'important');
            } else {
                fila.style.setProperty('display', 'none', 'important');
            }
        });
    });
</script>

</body>
</html>