<?php
session_start();
require 'db.php';

// 1. Control de Acceso
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php"); exit();
}

$id_emp = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');

if ($id_emp === 0) { die("Empleado no especificado."); }

$meses_es = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];

// --- 2. LÓGICA DE EXPORTACIÓN A EXCEL (NUEVO) ---
if (isset($_GET['export_excel'])) {
    $stmtUser = $pdo->prepare("SELECT usuario FROM usuarios WHERE id = ?");
    $stmtUser->execute([$id_emp]);
    $user = $stmtUser->fetch();

    $stmt = $pdo->prepare("SELECT fecha, hora_entrada, hora_salida, total_horas, estado FROM asistencias WHERE usuario_id = ? AND MONTH(fecha) = ? AND YEAR(fecha) = ? ORDER BY fecha ASC");
    $stmt->execute([$id_emp, $mes, $anio]);
    $data = $stmt->fetchAll();

    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=Reporte_" . $user['usuario'] . "_" . $meses_es[$mes-1] . ".xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "<table border='1'>";
    echo "<tr><th colspan='5' style='background:#a50064; color:white;'>REPORTE DE ASISTENCIA - " . strtoupper($user['usuario']) . " (" . $meses_es[$mes-1] . " $anio)</th></tr>";
    echo "<tr><th>Fecha</th><th>Entrada</th><th>Salida</th><th>Total Horas</th><th>Estado</th></tr>";
    
    foreach ($data as $row) {
        echo "<tr>";
        echo "<td>" . date('d/m/Y', strtotime($row['fecha'])) . "</td>";
        echo "<td>" . ($row['hora_entrada'] ?: '--:--') . "</td>";
        echo "<td>" . ($row['hora_salida'] ?: '--:--') . "</td>";
        echo "<td>" . ($row['total_horas'] ?: '00:00') . "</td>";
        echo "<td>" . strtoupper($row['estado']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    exit();
}

// --- 3. Lógica de Actualización Individual ---
if (isset($_POST['update_registro'])) {
    $id_reg = (int)$_POST['id_registro'];
    $h_entrada = $_POST['h_entrada']; 
    $h_salida = $_POST['h_salida'];

    try {
        $inicio = new DateTime($h_entrada);
        $fin = new DateTime($h_salida);
        if ($fin < $inicio) $fin->modify('+1 day');
        $total_h = $inicio->diff($fin)->format('%H:%I:%S');

        $sqlUpd = "UPDATE asistencias SET hora_entrada = ?, hora_salida = ?, total_horas = ?, estado = 'finalizado' WHERE id = ?";
        $stmtUpd = $pdo->prepare($sqlUpd);
        $stmtUpd->execute([$h_entrada, $h_salida, $total_h, $id_reg]);
    } catch (Exception $e) { }
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// --- 4. Lógica de Cierre Masivo ---
if (isset($_POST['cerrar_todo'])) {
    $stmtDocs = $pdo->prepare("SELECT id, hora_entrada, hora_salida FROM asistencias WHERE usuario_id = ? AND MONTH(fecha) = ? AND YEAR(fecha) = ? AND hora_entrada IS NOT NULL AND hora_salida IS NOT NULL");
    $stmtDocs->execute([$id_emp, $mes, $anio]);
    foreach ($stmtDocs->fetchAll() as $reg) {
        $inicio = new DateTime($reg['hora_entrada']);
        $fin = new DateTime($reg['hora_salida']);
        if ($fin < $inicio) $fin->modify('+1 day');
        $total_h = $inicio->diff($fin)->format('%H:%I:%S');
        $pdo->prepare("UPDATE asistencias SET total_horas = ?, estado = 'finalizado' WHERE id = ?")->execute([$total_h, $reg['id']]);
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?id=$id_emp&mes=$mes&anio=$anio");
    exit();
}

// --- 5. Obtener Datos para la Vista ---
$stmtUser = $pdo->prepare("SELECT usuario FROM usuarios WHERE id = ?");
$stmtUser->execute([$id_emp]);
$empleado = $stmtUser->fetch();

$stmt = $pdo->prepare("SELECT * FROM asistencias WHERE usuario_id = ? AND MONTH(fecha) = ? AND YEAR(fecha) = ? ORDER BY fecha ASC");
$stmt->execute([$id_emp, $mes, $anio]);
$registros = $stmt->fetchAll();

$total_segundos = 0;
foreach ($registros as $r) {
    if (!empty($r['total_horas'])) {
        $partes = explode(':', $r['total_horas']);
        $total_segundos += ((int)$partes[0] * 3600) + ((int)($partes[1]??0) * 60) + (int)($partes[2]??0);
    }
}
$h_final = floor($total_segundos / 3600);
$m_final = floor(($total_segundos % 3600) / 60);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Asistencia | Simtec Engineering</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --simtec-magenta: #a50064; --magenta-glow: rgba(165, 0, 100, 0.4); --bg-dark: #000a1a; --card-bg: #0a1120; --glass-border: rgba(255, 255, 255, 0.1); }
        body { background: var(--bg-dark); color: rgba(255, 255, 255, 0.9); font-family: 'Plus Jakarta Sans', sans-serif; min-height: 100vh; }
        .report-section { background: var(--card-bg); border-radius: 20px; padding: 30px; border: 1px solid var(--glass-border); margin-bottom: 30px; }
        .table { color: rgba(255,255,255,0.8) !important; }
        .table thead th { background: rgba(165, 0, 100, 0.1) !important; color: var(--simtec-magenta) !important; border-bottom: 2px solid var(--simtec-magenta) !important; text-transform: uppercase; font-size: 0.75rem; padding: 15px; }
        .table tbody td { padding: 12px 15px !important; border-bottom: 1px solid var(--glass-border) !important; background: transparent !important; vertical-align: middle; font-size: 0.85rem; color: rgba(255, 255, 255, 0.6) !important; }
        .btn-magenta { background: var(--simtec-magenta); color: white; border: none; font-weight: 700; border-radius: 10px; transition: 0.3s; }
        .btn-magenta:hover { filter: brightness(1.2); box-shadow: 0 0 15px var(--magenta-glow); color: white; }
        .btn-excel { background: #1D6F42; color: white; border: none; font-weight: 700; border-radius: 10px; transition: 0.3s; }
        .btn-excel:hover { background: #248c53; color: white; box-shadow: 0 0 10px rgba(29, 111, 66, 0.4); }
        .glass-input { background: #000 !important; border: 1px solid var(--glass-border) !important; color: white !important; }
        .modal-content { background: #020817; border: 1px solid var(--simtec-magenta); border-radius: 24px; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="report-section">
                <div class="text-center mb-4">
                    <h5 class="fw-800 mb-0"><?= htmlspecialchars($empleado['usuario'] ?? 'Empleado') ?></h5>
                    <span class="text-white-50 small">REPORTE ADMINISTRATIVO</span>
                </div>

                <div class="bg-black bg-opacity-40 rounded-4 p-3 border border-white border-opacity-10 mb-4 text-center">
                    <small class="text-white-50 d-block mb-1">HORAS TOTALES ACUMULADAS</small>
                    <h2 class="fw-bold m-0" style="color: var(--simtec-magenta)">
                        <?= sprintf('%02d:%02d', $h_final, $m_final) ?>
                    </h2>
                </div>

                <form action="" method="GET" class="mb-3">
                    <input type="hidden" name="id" value="<?= $id_emp ?>">
                    <div class="d-flex gap-2">
                        <select name="mes" class="form-select glass-input">
                            <?php foreach($meses_es as $i => $m_nombre): ?>
                                <option value="<?= $i+1 ?>" <?= ($i+1 == $mes) ? 'selected' : '' ?>><?= $m_nombre ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-magenta px-3"><i class="fas fa-sync-alt"></i></button>
                    </div>
                </form>
                
                <div class="d-grid gap-2">
                    <form method="POST">
                        <button type="submit" name="cerrar_todo" class="btn btn-outline-success w-100 btn-sm fw-bold mb-2" onclick="return confirm('¿Finalizar todos los registros?');">
                            CONSOLIDAR MES
                        </button>
                    </form>

                    <a href="?id=<?= $id_emp ?>&mes=<?= $mes ?>&anio=<?= $anio ?>&export_excel=1" class="btn btn-excel w-100 btn-sm">
                        <i class="fas fa-file-excel me-2"></i>DESCARGAR EXCEL
                    </a>
                </div>

                <a href="admin_dashboard.php" class="btn btn-link btn-sm w-100 text-white-50 mt-3 text-decoration-none">Regresar</a>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="report-section p-0 overflow-hidden">
                <div class="p-3 border-bottom border-white border-opacity-10 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-white-50">HISTORIAL: <?= strtoupper($meses_es[$mes-1]) ?></h6>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>FECHA</th>
                                <th>ENTRADA</th>
                                <th>SALIDA</th>
                                <th>TOTAL</th>
                                <th class="text-end">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registros as $reg): ?>
                            <tr>
                                <td class="text-white"><?= date('d M', strtotime($reg['fecha'])) ?></td>
                                <td><?= $reg['hora_entrada'] ? substr($reg['hora_entrada'], 0, 5) : '--:--' ?></td>
                                <td><?= $reg['hora_salida'] ? substr($reg['hora_salida'], 0, 5) : '--:--' ?></td>
                                <td style="color: var(--simtec-magenta)">
                                    <?= $reg['total_horas'] ? substr($reg['total_horas'], 0, 5) : '00:00' ?>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-info" onclick="abrirModal('<?= $reg['id'] ?>', '<?= $reg['hora_entrada'] ?>', '<?= $reg['hora_salida'] ?>', '<?= date('d/m/Y', strtotime($reg['fecha'])) ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST">
            <div class="modal-body p-4">
                <h5 class="fw-800 text-white mb-4">Ajustar Registro</h5>
                <input type="hidden" name="id_registro" id="input_id">
                <div class="row g-3">
                    <div class="col-6">
                        <label class="small text-white-50">ENTRADA</label>
                        <input type="time" name="h_entrada" id="input_entrada" class="form-control glass-input" required>
                    </div>
                    <div class="col-6">
                        <label class="small text-white-50">SALIDA</label>
                        <input type="time" name="h_salida" id="input_salida" class="form-control glass-input" required>
                    </div>
                </div>
                <button type="submit" name="update_registro" class="btn btn-magenta w-100 mt-4 py-3">GUARDAR CAMBIOS</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const elModal = new bootstrap.Modal(document.getElementById('modalEditar'));
    function abrirModal(id, entrada, salida, fecha) {
        document.getElementById('input_id').value = id;
        document.getElementById('input_entrada').value = entrada ? entrada.substring(0,5) : "08:00";
        document.getElementById('input_salida').value = salida ? salida.substring(0,5) : "17:00";
        elModal.show();
    }
</script>
</body>
</html>