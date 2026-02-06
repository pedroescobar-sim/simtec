<?php
session_start();
require 'db.php';

// 1. Seguridad
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php"); exit();
}

// 2. Parámetros
$id_admin = $_SESSION['usuario_id'];
$nombre_display = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'Administrador';
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');

// 3. Consulta de registros
$stmt = $pdo->prepare("SELECT * FROM asistencias WHERE usuario_id = ? AND MONTH(fecha) = ? AND YEAR(fecha) = ? ORDER BY fecha ASC");
$stmt->execute([$id_admin, $mes, $anio]);
$registros = $stmt->fetchAll();

// 4. Lógica de suma
$total_segundos = 0;
foreach ($registros as $r) {
    if (!empty($r['total_horas']) && $r['total_horas'] !== '00:00:00') {
        list($h, $m, $s) = array_pad(explode(':', $r['total_horas']), 3, 0);
        $total_segundos += ($h * 3600) + ($m * 60) + $s;
    } 
    elseif (!empty($r['hora_entrada']) && !empty($r['hora_salida'])) {
        $inicio = strtotime($r['hora_entrada']);
        $fin = strtotime($r['hora_salida']);
        if ($fin > $inicio) { $total_segundos += ($fin - $inicio); }
    }
}

$h_final = floor($total_segundos / 3600);
$m_final = floor(($total_segundos % 3600) / 60);

$meses_es = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Historial | Simtec</title>
    <link rel="shortcut icon" href="logo.jpg" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { 
            --simtec-blue: #003366; 
            --simtec-magenta: #a50064; 
            --bg-dark: #000a1a;
            --glass: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        body { 
            background: radial-gradient(circle at bottom right, #001f3f, var(--bg-dark));
            color: white;
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
        }

        /* Corregir Navbar */
        .navbar { background: rgba(0,0,0,0.5) !important; backdrop-filter: blur(10px); border-bottom: 1px solid var(--glass-border); }

        /* Corregir Tarjetas */
        .card-custom { 
            background: var(--glass); 
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
        }
        .border-magenta { border-left: 5px solid var(--simtec-magenta); }

        /* CORRECCIÓN DE DESPLEGABLES (SELECT) */
        .form-select {
            background-color: rgba(0,0,0,0.3) !important;
            border: 1px solid var(--glass-border) !important;
            color: white !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ffffff'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e") !important;
        }

        /* Estilo para las opciones dentro del select */
        .form-select option {
            background-color: #001a33; /* Fondo oscuro para que se vea el texto */
            color: white;
        }

        /* Corregir Tabla */
        .table-container { 
            background: rgba(255,255,255,0.02); 
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            overflow: hidden;
        }
        .table { color: white !important; margin-bottom: 0; }
        .table thead th { 
            background: rgba(255,255,255,0.05) !important; 
            color: rgba(255,255,255,0.6) !important;
            border: none;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
            padding: 15px;
        }
        .table td { border-bottom: 1px solid var(--glass-border); padding: 15px; background: transparent !important; color: white !important; }

        .badge-time { 
            background: rgba(255,255,255,0.08); 
            color: white; 
            padding: 6px 12px; 
            border-radius: 8px;
            border: 1px solid var(--glass-border);
        }

        .btn-primary { background: var(--simtec-magenta); border: none; font-weight: 700; border-radius: 12px; }
        .btn-primary:hover { background: #850050; }

        .text-magenta { color: var(--simtec-magenta); }
    </style>
</head>
<body>

<nav class="navbar navbar-dark p-3 mb-4">
    <div class="container">
        <a href="dashboard.php" class="text-white text-decoration-none fw-bold small">
            <i class="fas fa-chevron-left me-2"></i> VOLVER AL PANEL
        </a>
        <span class="text-white-50 fw-bold small" style="letter-spacing: 1px;">SIMTEC HISTORIAL</span>
    </div>
</nav>

<div class="container pb-5">
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card card-custom border-magenta p-4 h-100">
                <p class="text-white-50 small fw-bold mb-1 text-uppercase">Total del Mes</p>
                <div class="d-flex align-items-baseline">
                    <h1 class="display-5 fw-800 mb-0 text-white"><?= sprintf('%02d:%02d', $h_final, $m_final) ?></h1>
                    <span class="ms-2 text-magenta fw-bold">HRS</span>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card card-custom p-4 h-100">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h4 class="fw-800 text-white mb-0"><?= htmlspecialchars($nombre_display) ?></h4>
                        <p class="text-white-50 mb-0 small">Historial de <?= $meses_es[$mes-1] ?> <?= $anio ?></p>
                    </div>
                    <form action="" method="GET" class="d-flex gap-2">
                        <select name="mes" class="form-select form-select-sm shadow-none">
                            <?php foreach($meses_es as $i => $m_nombre): ?>
                                <option value="<?= $i+1 ?>" <?= ($i+1 == $mes) ? 'selected' : '' ?>><?= $m_nombre ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm px-4">FILTRAR</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="table-container shadow-lg">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th class="ps-4">Fecha</th>
                        <th>Entrada</th>
                        <th>Salida</th>
                        <th>Tiempo Neto</th>
                        <th class="text-center">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($registros)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-white-50">No hay registros este mes.</td></tr>
                    <?php else: ?>
                        <?php foreach ($registros as $reg): ?>
                        <?php 
                            $diario = "00:00";
                            if (!empty($reg['total_horas']) && $reg['total_horas'] !== '00:00:00') {
                                $diario = substr($reg['total_horas'], 0, 5);
                            } elseif (!empty($reg['hora_entrada']) && !empty($reg['hora_salida'])) {
                                $diff = strtotime($reg['hora_salida']) - strtotime($reg['hora_entrada']);
                                $diario = sprintf('%02d:%02d', floor($diff/3600), floor(($diff%3600)/60));
                            }
                        ?>
                        <tr>
                            <td class="ps-4 fw-bold">
                                <?= date('d/m/y', strtotime($reg['fecha'])) ?>
                            </td>
                            <td><span class="badge-time"><i class="fas fa-sign-in-alt me-2 text-success"></i><?= substr($reg['hora_entrada'], 0, 5) ?></span></td>
                            <td><span class="badge-time"><i class="fas fa-sign-out-alt me-2 text-danger"></i><?= $reg['hora_salida'] ? substr($reg['hora_salida'], 0, 5) : '--:--' ?></span></td>
                            <td class="fw-800" style="color: #00ffa3;"><?= $diario ?> <small class="text-white-50">h</small></td>
                            <td class="text-center">
                                <span class="badge rounded-pill" style="background: rgba(0, 255, 163, 0.1); color: #00ffa3; font-size: 0.65rem; border: 1px solid rgba(0, 255, 163, 0.2);">VERIFICADO</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>