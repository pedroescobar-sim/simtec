<?php
session_start();
require 'db.php';

// Verificar que haya una sesión iniciada
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Si viene de la vista de admin, usamos el ID que se envió, si no, el de la sesión
    $usuario_id = isset($_POST['usuario_id_admin']) ? (int)$_POST['usuario_id_admin'] : $_SESSION['usuario_id'];
    $fecha = $_POST['fecha'];
    $h_in = !empty($_POST['h_in']) ? $_POST['h_in'] : null;
    $h_out = !empty($_POST['h_out']) ? $_POST['h_out'] : null;
    
    $total_horas_db = "00:00:00";

    // 1. CÁLCULO DE HORAS TOTALES
    if ($h_in && $h_out) {
        $inicio = new DateTime($h_in);
        $fin = new DateTime($h_out);
        
        // Si la hora de salida es menor a la de entrada (ej: turno nocturno)
        if ($fin < $inicio) {
            $fin->modify('+1 day');
        }
        
        $intervalo = $inicio->diff($fin);
        
        // Formateamos a HH:MM:SS para la base de datos
        // Usamos days * 24 para capturar diferencias de más de un día si fuera necesario
        $horas = ($intervalo->days * 24) + $intervalo->h;
        $minutos = $intervalo->i;
        $total_horas_db = sprintf('%02d:%02d:00', $horas, $minutos);
    }

    try {
        // 2. BUSCAR SI EL REGISTRO YA EXISTE
        $check = $pdo->prepare("SELECT id FROM asistencias WHERE usuario_id = ? AND fecha = ?");
        $check->execute([$usuario_id, $fecha]);
        $registro = $check->fetch();

        if ($registro) {
            // 3. ACTUALIZAR EXISTENTE
            $sql = "UPDATE asistencias SET 
                        hora_entrada = ?, 
                        hora_salida = ?, 
                        total_horas = ?, 
                        estado = 'finalizado' 
                    WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$h_in, $h_out, $total_horas_db, $registro['id']]);
        } else {
            // 4. INSERTAR NUEVO (por si el admin añade un día que el empleado olvidó)
            $sql = "INSERT INTO asistencias (usuario_id, fecha, hora_entrada, hora_salida, total_horas, estado) 
                    VALUES (?, ?, ?, ?, ?, 'finalizado')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$usuario_id, $fecha, $h_in, $h_out, $total_horas_db]);
        }

        // 5. REDIRECCIÓN INTELIGENTE
        // Si el que edita es admin, vuelve al reporte del empleado. Si es usuario, a su calendario.
        if ($_SESSION['rol'] === 'admin') {
            header("Location: ver_calendario_empleado.php?id=$usuario_id&mes=" . date('m', strtotime($fecha)) . "&anio=" . date('Y', strtotime($fecha)) . "&status=ok");
        } else {
            header("Location: calendario.php?status=ok");
        }
        exit();

    } catch (PDOException $e) {
        die("Error crítico al actualizar los tiempos: " . $e->getMessage());
    }
}
