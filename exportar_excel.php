<?php
session_start();
require 'db.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    exit("Acceso denegado");
}

// --- VALIDACIÓN DE PARÁMETROS ---
$id_emp = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$mes_url = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
$mes = ($mes_url < 1 || $mes_url > 12) ? (int)date('m') : $mes_url;
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');

// 1. CONSULTA SQL CON JOIN (Uniendo usuarios con datos_personales)
$sqlUser = "SELECT u.usuario, dp.nombre, dp.apellido1, dp.apellido2, dp.dni 
            FROM usuarios u 
            LEFT JOIN datos_personales dp ON u.id = dp.usuario_id 
            WHERE u.id = ?";

$stmtUser = $pdo->prepare($sqlUser);
$stmtUser->execute([$id_emp]);
$empleado = $stmtUser->fetch();

if ($empleado) {
    $nombre_usuario = $empleado['usuario'];
    $nombre_real = trim(($empleado['nombre'] ?? '') . ' ' . ($empleado['apellido1'] ?? '') . ' ' . ($empleado['apellido2'] ?? ''));
    $dni_usuario = $empleado['dni'] ?? 'S/N';
} else {
    $nombre_usuario = 'Desconocido';
    $nombre_real = 'DATOS NO ENCONTRADOS';
    $dni_usuario = '---';
}

// 2. REGISTROS DE ASISTENCIA
$stmt = $pdo->prepare("SELECT * FROM asistencias WHERE usuario_id = ? AND MONTH(fecha) = ? AND YEAR(fecha) = ? ORDER BY fecha ASC");
$stmt->execute([$id_emp, $mes, $anio]);
$registros = $stmt->fetchAll();

$meses_es = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
$nombre_mes = $meses_es[$mes - 1];

// 3. CÁLCULO DE TOTALES
$total_segundos = 0;
foreach ($registros as $r) {
    if (!empty($r['total_horas'])) {
        list($h, $m, $s) = array_pad(explode(':', $r['total_horas']), 3, 0);
        $total_segundos += ($h * 3600) + ($m * 60) + $s;
    }
}
$h_f = floor($total_segundos / 3600);
$m_f = floor(($total_segundos % 3600) / 60);
$tiempo_total_formateado = sprintf('%02d:%02d', $h_f, $m_f);

// 4. CABECERAS EXCEL
$filename = "Parte_Horas_{$nombre_usuario}_{$nombre_mes}.xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=$filename");
header("Pragma: no-cache");
header("Expires: 0");
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<table border="1" style="border-collapse: collapse; font-family: Arial, sans-serif;">
    <tr>
        <th colspan="5" style="background-color: #003366; color: white; height: 35px; font-size: 16px; border: 1px solid #000000;">
            SIMTEC INGENIERÍA - PARTE DE HORAS
        </th>
    </tr>
    
    <tr>
        <td colspan="3" style="background-color: #f2f2f2; padding: 8px; border: 1px solid #ccc;">
            <strong>NOMBRE COMPLETO:</strong> <?php echo mb_strtoupper($nombre_real, 'UTF-8'); ?>
        </td>
        <td colspan="2" style="background-color: #f2f2f2; padding: 8px; border: 1px solid #ccc;">
            <strong>DNI:</strong> <?php echo strtoupper($dni_usuario); ?>
        </td>
    </tr>

    <tr>
        <td colspan="3" style="text-align: left; padding: 5px; border: 1px solid #ccc;">
            USUARIO: <?php echo $nombre_usuario; ?>
        </td>
        <td colspan="2" style="text-align: right; padding: 5px; border: 1px solid #ccc;">
            MES: <?php echo strtoupper($nombre_mes) . " " . $anio; ?>
        </td>
    </tr>

    <tr>
        <td colspan="5" style="background-color: #ffffff; color: #a50064; height: 30px; font-size: 14px; border: 1px solid #ccc; text-align: center;">
            TOTAL HORAS ACUMULADAS: <strong><?php echo $tiempo_total_formateado; ?></strong>
        </td>
    </tr>

    <tr>
        <th width="120" style="background-color: #a50064; color: white; height: 30px; border: 1px solid #80004d;">FECHA</th>
        <th width="100" style="background-color: #a50064; color: white; border: 1px solid #80004d;">ENTRADA</th>
        <th width="100" style="background-color: #a50064; color: white; border: 1px solid #80004d;">SALIDA</th>
        <th width="120" style="background-color: #a50064; color: white; border: 1px solid #80004d;">TOTAL DÍA</th>
        <th width="130" style="background-color: #a50064; color: white; border: 1px solid #80004d;">ESTADO</th>
    </tr>

    <?php 
    if (count($registros) > 0):
        foreach ($registros as $index => $reg): 
            $bg = ($index % 2 == 0) ? '#ffffff' : '#f9f9f9';
    ?>
    <tr style="text-align: center;">
        <td style="background-color: <?php echo $bg; ?>; height: 25px; border: 1px solid #dddddd;"><?php echo date('d/m/Y', strtotime($reg['fecha'])); ?></td>
        <td style="background-color: <?php echo $bg; ?>; border: 1px solid #dddddd;"><?php echo $reg['hora_entrada'] ? substr($reg['hora_entrada'], 0, 5) : '--:--'; ?></td>
        <td style="background-color: <?php echo $bg; ?>; border: 1px solid #dddddd;"><?php echo $reg['hora_salida'] ? substr($reg['hora_salida'], 0, 5) : '--:--'; ?></td>
        <td style="background-color: <?php echo $bg; ?>; border: 1px solid #dddddd; font-weight: bold; color: #003366;">
            <?php echo $reg['total_horas'] ? substr($reg['total_horas'], 0, 5) : '00:00'; ?>
        </td>
        <td style="background-color: <?php echo $bg; ?>; border: 1px solid #dddddd;">
            <?php echo ($reg['estado'] == 'finalizado') ? 'Finalizado' : 'Pendiente'; ?>
        </td>
    </tr>
    <?php 
        endforeach; 
    else:
    ?>
    <tr>
        <td colspan="5" style="text-align: center; padding: 15px; border: 1px solid #ccc;">No hay registros de asistencia.</td>
    </tr>
    <?php endif; ?>
</table>