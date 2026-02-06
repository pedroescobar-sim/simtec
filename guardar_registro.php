<?php
session_start();
require 'db.php';

if (!isset($_SESSION['usuario_id']) || !isset($_GET['tipo'])) {
    header("Location: dashboard.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$tipo = $_GET['tipo'];
$fecha_hoy = date('Y-m-d');
$hora_actual = date('H:i:s');

switch ($tipo) {
    case 'entrada':
        // Lógica para insertar entrada (si no existe ya)
        $stmt = $pdo->prepare("INSERT IGNORE INTO asistencias (usuario_id, fecha, hora_entrada, estado) VALUES (?, ?, ?, 'activo')");
        $stmt->execute([$usuario_id, $fecha_hoy, $hora_actual]);
        break;

    case 'descanso':
        // Cambiar estado a descanso
        $stmt = $pdo->prepare("UPDATE asistencias SET estado = 'descanso' WHERE usuario_id = ? AND fecha = ? AND estado = 'activo'");
        $stmt->execute([$usuario_id, $fecha_hoy]);
        break;

    case 'reanudar':
        // Cambiar estado de vuelta a activo
        $stmt = $pdo->prepare("UPDATE asistencias SET estado = 'activo' WHERE usuario_id = ? AND fecha = ? AND estado = 'descanso'");
        $stmt->execute([$usuario_id, $fecha_hoy]);
        break;

    case 'salida':
        // ESTA ES LA PARTE QUE BUSCAS:
        // Guarda la hora de salida y marca como finalizado
        $stmt = $pdo->prepare("UPDATE asistencias SET hora_salida = ?, estado = 'finalizado' WHERE usuario_id = ? AND fecha = ? AND hora_salida IS NULL");
        $stmt->execute([$hora_actual, $usuario_id, $fecha_hoy]);
        break;
}

header("Location: dashboard.php");
exit(); 