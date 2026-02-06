<?php
session_start();
require 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$usuario_nombre = $_SESSION['nombre_usuario'];

// --- Lógica de Navegación de Fechas ---
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');

$fecha_string = sprintf("%04d-%02d-01", $anio, $mes);
$fecha_obj = new DateTime($fecha_string);

$prev_fecha = (clone $fecha_obj)->modify('-1 month');
$next_fecha = (clone $fecha_obj)->modify('+1 month');

// --- Consulta de Asistencias ---
$stmt = $pdo->prepare("SELECT fecha, hora_entrada, hora_salida FROM asistencias WHERE usuario_id = ? AND MONTH(fecha) = ? AND YEAR(fecha) = ?");
$stmt->execute([$usuario_id, $mes, $anio]);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

$eventos = [];
$segundos_mes = 0;

foreach ($registros as $r) {
    $dia_idx = (int)date('j', strtotime($r['fecha']));
    $eventos[$dia_idx] = $r;
    
    if ($r['hora_entrada'] && $r['hora_salida']) {
        $t1 = new DateTime($r['hora_entrada']);
        $t2 = new DateTime($r['hora_salida']);
        $diff = $t1->diff($t2);
        $segundos_mes += ($diff->h * 3600) + ($diff->i * 60);
    }
}

function formatearTiempo($segundos) {
    $h = floor($segundos / 3600);
    $m = ($segundos / 60) % 60;
    return $h . "h " . $m . "m";
}

$total_h_mes = floor($segundos_mes / 3600);
$total_m_mes = ($segundos_mes / 60) % 60;

$meses_es = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
$hoy_full = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario Laboral | Simtec</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root { 
            --magenta: #a50064; 
            --magenta-glow: #ff0095;
            --bg-dark: #020817;
            --glass: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.12);
            --warning-bg: rgba(245, 158, 11, 0.1);
            --warning-border: rgba(245, 158, 11, 0.4);
            --warning-text: #fbbf24;
        }

        body { 
            background: radial-gradient(circle at 0% 0%, #001f3f 0%, transparent 50%),
                        radial-gradient(circle at 100% 100%, #1a0010 0%, var(--bg-dark) 50%);
            background-color: var(--bg-dark);
            color: #f8fafc;
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0; padding-bottom: 50px;
        }

        .nav-simtec { 
            background: rgba(2, 8, 23, 0.8);
            backdrop-filter: blur(15px);
            border-bottom: 1px solid var(--glass-border);
            padding: 1rem 2rem;
            display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 100;
        }

        .btn-nav {
            text-decoration: none; color: white; font-size: 0.8rem; font-weight: 700;
            padding: 0.7rem 1.4rem; border-radius: 12px; background: var(--glass);
            border: 1px solid var(--glass-border); transition: 0.3s;
        }

        .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }

        .summary-box { 
            background: linear-gradient(135deg, rgba(255,255,255,0.05), rgba(165,0,100,0.1));
            border-radius: 30px; padding: 2rem; border: 1px solid var(--glass-border);
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;
        }
        .summary-box h2 { font-size: 3rem; margin: 0; font-weight: 800; }
        .summary-box h2 span { color: var(--magenta-glow); }

        .card-cal { 
            background: rgba(15, 23, 42, 0.6); border-radius: 32px; 
            border: 1px solid var(--glass-border); overflow: hidden;
            backdrop-filter: blur(20px);
        }

        .cal-header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 1.5rem 2rem; background: rgba(255,255,255,0.03);
            border-bottom: 1px solid var(--glass-border);
        }

        .grid-cal { 
            display: grid; 
            grid-template-columns: repeat(7, 1fr) 110px; 
            gap: 1px; 
            background: var(--glass-border); 
        }

        .day-head { 
            background: rgba(0,0,0,0.4); padding: 1rem; text-align: center; 
            font-weight: 800; font-size: 0.7rem; color: #94a3b8; text-transform: uppercase; 
        }
        .head-total { background: rgba(165, 0, 100, 0.2); color: white; }

        .day-box { background: #020817; min-height: 110px; padding: 0.8rem; cursor: pointer; transition: 0.3s; position: relative; }
        .day-box:hover:not(.future):not(.day-total) { background: rgba(165, 0, 100, 0.1); }
        .day-box.future { opacity: 0.2; cursor: not-allowed; }
        
        .es-hoy { background: rgba(165, 0, 100, 0.15) !important; box-shadow: inset 0 0 0 2px var(--magenta-glow); }

        .day-num { font-weight: 800; color: #cbd5e1; font-size: 1.1rem; margin-bottom: 0.5rem; }

        .day-total {
            background: rgba(165, 0, 100, 0.05);
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            border-left: 1px solid var(--magenta);
            cursor: default;
        }
        .total-label { font-size: 0.6rem; color: var(--magenta-glow); font-weight: 800; text-transform: uppercase; margin-bottom: 4px; }
        .total-amount { font-weight: 800; font-size: 0.85rem; color: white; }

        .badge-time { font-size: 0.65rem; padding: 4px 6px; border-radius: 6px; margin-top: 4px; font-weight: 800; display: block; width: fit-content; }
        .b-in { color: #2dd4bf; background: rgba(45, 212, 191, 0.1); border: 1px solid rgba(45, 212, 191, 0.2); }
        .b-out { color: #fb7185; background: rgba(251, 113, 133, 0.1); border: 1px solid rgba(251, 113, 133, 0.2); }

        /* Modal */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(2, 8, 17, 0.9); backdrop-filter: blur(15px);
            display: none; justify-content: center; align-items: center; z-index: 1000;
        }
        .modal-content {
            background: #0f172a; border: 1px solid var(--magenta);
            padding: 2.5rem; border-radius: 30px; width: 90%; max-width: 420px;
        }

        .warning-box {
            background: var(--warning-bg);
            border: 1px solid var(--warning-border);
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 20px;
            text-align: center;
        }
        .warning-box p {
            margin: 0;
            color: var(--warning-text);
            font-size: 0.75rem;
            line-height: 1.4;
            font-weight: 600;
        }

        .form-control {
            width: 100%; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border);
            padding: 0.8rem; border-radius: 10px; color: white; margin-top: 0.5rem; box-sizing: border-box;
        }
        .btn-save {
            background: var(--magenta); color: white; border: none; width: 100%; padding: 1rem;
            border-radius: 12px; font-weight: 800; cursor: pointer; margin-top: 1.5rem; transition: 0.3s;
        }
        .btn-save:hover { background: var(--magenta-glow); }

        @media (max-width: 768px) {
            .grid-cal { grid-template-columns: repeat(7, 1fr) 65px; }
            .day-num { font-size: 0.9rem; }
            .total-amount { font-size: 0.7rem; }
            .summary-box h2 { font-size: 2rem; }
        }
    </style>
</head>
<body>

<nav class="nav-simtec">
    <a href="dashboard.php" class="btn-nav">← Panel</a>
    <div style="text-align:center">
        <span style="font-size: 0.6rem; color: #94a3b8; letter-spacing: 2px; display: block; font-weight: 800;">CALENDARIO</span>
        <span style="font-weight: 700; color: white;"><?= strtoupper(htmlspecialchars($usuario_nombre)) ?></span>
    </div>
    <a href="calendario.php" class="btn-nav" style="color: var(--magenta-glow); border-color: var(--magenta);">Hoy</a>
</nav>

<div class="container">
    <div class="summary-box">
        <div>
            <p style="margin:0; font-size:0.7rem; color:#94a3b8; font-weight:800; text-transform: uppercase;">Total Mes</p>
            <h2><?= $total_h_mes ?><span>H</span> <?= $total_m_mes ?><span>M</span></h2>
        </div>
    </div>

    <div class="card-cal">
        <div class="cal-header">
            <a href="?mes=<?= $prev_fecha->format('m') ?>&anio=<?= $prev_fecha->format('Y') ?>" style="color: white; text-decoration: none;">❮</a>
            <h3 style="margin:0"><?= $meses_es[$mes-1] ?> <?= $anio ?></h3>
            <a href="?mes=<?= $next_fecha->format('m') ?>&anio=<?= $next_fecha->format('Y') ?>" style="color: white; text-decoration: none;">❯</a>
        </div>

        <div class="grid-cal">
            <div class="day-head">Lun</div><div class="day-head">Mar</div><div class="day-head">Mié</div>
            <div class="day-head">Jue</div><div class="day-head">Vie</div><div class="day-head">Sáb</div><div class="day-head">Dom</div>
            <div class="day-head head-total">Total</div>

            <?php
            $start_day = (int)$fecha_obj->format('N');
            $days_in_month = (int)$fecha_obj->format('t');
            
            for ($i = 1; $i < $start_day; $i++) echo '<div class="day-box" style="opacity:0"></div>';

            $segundos_semanales = 0;

            for ($d = 1; $d <= $days_in_month; $d++) {
                $fecha_iterada = sprintf("%04d-%02d-%02d", $anio, $mes, $d);
                $es_futuro = ($fecha_iterada > $hoy_full);
                $data = $eventos[$d] ?? null;
                $es_hoy = ($fecha_iterada == $hoy_full) ? 'es-hoy' : '';
                
                if ($data && $data['hora_entrada'] && $data['hora_salida']) {
                    $t1 = new DateTime($data['hora_entrada']);
                    $t2 = new DateTime($data['hora_salida']);
                    $diff = $t1->diff($t2);
                    $segundos_semanales += ($diff->h * 3600) + ($diff->i * 60);
                }
                ?>
                <div class="day-box <?= $es_hoy ?> <?= $es_futuro ? 'future' : '' ?>" 
                     onclick="abrirAjuste('<?= $fecha_iterada ?>', '<?= $data['hora_entrada'] ?? '' ?>', '<?= $data['hora_salida'] ?? '' ?>', <?= $es_futuro ? 'true' : 'false' ?>)">
                    <div class="day-num"><?= $d ?></div>
                    <?php if($data): ?>
                        <span class="badge-time b-in">↑ <?= substr($data['hora_entrada'], 0, 5) ?></span>
                        <?php if($data['hora_salida']): ?>
                            <span class="badge-time b-out">↓ <?= substr($data['hora_salida'], 0, 5) ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php
                
                $dia_semana_actual = date('N', strtotime($fecha_iterada));
                if ($dia_semana_actual == 7 || $d == $days_in_month) {
                    if ($d == $days_in_month && $dia_semana_actual < 7) {
                        for ($fill = $dia_semana_actual + 1; $fill <= 7; $fill++) echo '<div class="day-box" style="opacity:0"></div>';
                    }
                    echo '<div class="day-box day-total"><span class="total-label">Semana</span><span class="total-amount">'.formatearTiempo($segundos_semanales).'</span></div>';
                    $segundos_semanales = 0;
                }
            }
            ?>
        </div>
    </div>
</div>

<div id="modalEdit" class="modal-overlay">
    <div class="modal-content">
        <h2 style="margin: 0; font-size: 1.5rem; color: white; text-align:center; font-weight: 800;">Ajustar Turno</h2>
        <p id="label_fecha" style="color: var(--magenta-glow); font-weight: 800; margin: 10px 0; text-align:center; font-size: 1.1rem;"></p>
        
        <div class="warning-box">
            <p>⚠️ <strong>ATENCIÓN:</strong> Cualquier cambio realizado quedará guardado y será revisado por el administrador del sistema.</p>
        </div>

        <form action="actualizar_asistencia.php" method="POST">
            <input type="hidden" name="fecha" id="f_input">
            <div style="display:flex; gap:15px;">
                <div style="flex:1">
                    <label style="font-size: 0.65rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Entrada</label>
                    <input type="time" name="h_in" id="h_in" class="form-control" required>
                </div>
                <div style="flex:1">
                    <label style="font-size: 0.65rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Salida</label>
                    <input type="time" name="h_out" id="h_out" class="form-control">
                </div>
            </div>
            <button type="submit" class="btn-save">Confirmar Cambios</button>
            <button type="button" onclick="cerrarModal()" style="background:none; border:none; color:#64748b; margin-top:1.2rem; cursor:pointer; width:100%; font-weight:700; font-size: 0.85rem;">CANCELAR</button>
        </form>
    </div>
</div>

<script>
    function abrirAjuste(fecha, in_val, out_val, esFuturo) {
        if (esFuturo) return;
        const d = fecha.split("-");
        document.getElementById('label_fecha').innerText = `${d[2]}/${d[1]}/${d[0]}`;
        document.getElementById('f_input').value = fecha;
        document.getElementById('h_in').value = in_val ? in_val.substring(0,5) : '';
        document.getElementById('h_out').value = out_val ? out_val.substring(0,5) : '';
        document.getElementById('modalEdit').style.display = 'flex';
    }
    function cerrarModal() { document.getElementById('modalEdit').style.display = 'none'; }
</script>

</body>
</html>