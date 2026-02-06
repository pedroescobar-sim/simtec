<?php
require 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_form = trim($_POST['usuario']);
    $password_form = trim($_POST['password']);

    // 1. Buscamos al usuario
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usuario_form]);
    $user = $stmt->fetch();

    // 2. Verificamos si existe y si la contraseña coincide
    if ($user && password_verify($password_form, $user['password'])) {
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['usuario'] = $user['usuario'];
        $_SESSION['rol']     = $user['rol']; 

        // Si es admin, a su panel
        if ($_SESSION['rol'] === 'admin') {
            header("Location: admin_dashboard.php");
            exit();
        } 

        // Verificamos si es nuevo para mandarlo a bienvenida o al dashboard
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) as total FROM asistencias WHERE usuario_id = ?");
        $stmtCheck->execute([$user['id']]);
        $res = $stmtCheck->fetch();
        $total = (int)$res['total'];

        if ($total === 0) {
            header("Location: bienvenida.php");
        } else {
            header("Location: dashboard.php");
        }
        exit(); 

    } else {
        // --- AQUÍ ESTÁ EL CAMBIO ---
        // Si falla, volvemos al index con el error en la URL
        header("Location: index.php?error=credenciales");
        exit();
    }
} else {
    // Si alguien intenta entrar a este archivo sin POST, lo echamos
    header("Location: index.php");
    exit();
}