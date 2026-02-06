<?php
session_start();
require 'db.php';

// 1. Seguridad
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php"); exit();
}

$id_asis = isset($_GET['id_asistencia']) ? (int)$_GET['id_asistencia'] : 0;

// 2. Obtener datos actuales del registro
$stmt = $pdo->prepare("SELECT a.*, u.usuario FROM asistencias a JOIN usuarios u ON a.usuario_id = u.id WHERE a.id = ?");
$stmt->execute([$id_asis]);
$reg = $stmt->fetch();

if (!$reg) { die("Registro no encontrado."); }

// 3. Procesar la actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $h_entrada = $_POST['hora_entrada'];
    $h_salida = $_POST['hora_salida'];

    // CÁLCULO AUTOMÁTICO DE HORAS TOTALES
    $entrada = new DateTime($h_entrada);
    $salida = new DateTime($h_salida);
    
    // Si la salida es menor que la entrada (ej. turno noche), se asume siguiente día
    if ($salida < $entrada) {
        $salida->modify('+1 day');
    }

    $intervalo = $entrada->diff($salida);
    $total_horas = $intervalo->format('%H:%I:%S'); // Formato HH:MM:SS

    // Guardar en la base de datos
    $update = $pdo->prepare("UPDATE asistencias SET hora_entrada = ?, hora_salida = ?, total_horas = ?, estado = 'finalizado' WHERE id = ?");
    
    if ($update->execute([$h_entrada, $h_salida, $total_horas, $id_asis])) {
        // Volver al reporte del empleado
        header("Location: ver_calendario_empleado.php?id=" . $reg['usuario_id']);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corregir Horas | Simtec</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        .edit-card { max-width: 500px; margin: 50px auto; background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .btn-save { background: #a50064; color: white; border: none; font-weight: bold; }
        .btn-save:hover { background: #80004d; color: white; }
    </style>
</head>
<body>

<div class="container">
    <div class="edit-card">
        <h4 class="fw-bold mb-1">Corregir Jornada</h4>
        <p class="text-muted small mb-4">Ingeniero: <?= htmlspecialchars($reg['usuario']) ?> | Fecha: <?= $reg['fecha'] ?></p>
        
        <form method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">HORA DE ENTRADA</label>
                <input type="time" name="hora_entrada" class="form-control form-control-lg" value="<?= $reg['hora_entrada'] ?>" required>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold text-muted">HORA DE SALIDA</label>
                <input type="time" name="hora_salida" class="form-control form-control-lg" value="<?= $reg['hora_salida'] ?>" required>
            </div>

            <div class="alert alert-info py-2 small">
                <i class="fas fa-info-circle me-1"></i> 
                El sistema recalculará las <strong>horas totales</strong> automáticamente al guardar.
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-save btn-lg">GUARDAR CAMBIOS</button>
                <a href="ver_calendario_empleado.php?id=<?= $reg['usuario_id'] ?>" class="btn btn-light text-muted">Cancelar</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>