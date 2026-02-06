<?php
require 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userInput = trim($_POST['usuario']);
    $passInput = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ?");
    $stmt->execute([$userInput]);
    $user = $stmt->fetch();

    if ($user && password_verify($passInput, $user['password'])) {
        
        // Guardamos los datos en la SESIÓN (Incluimos el ROL)
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['nombre_usuario'] = $user['usuario'];
        $_SESSION['rol'] = $user['rol']; // <--- ESTO ES LO QUE TE FALTABA

        // REDIRECCIÓN INTELIGENTE
        if ($_SESSION['rol'] === 'admin') {
            // Si es javi (o cualquier admin), va a su panel especial
            header("Location: admin_dashboard.php");
        } else {
            // El resto de empleados van al panel de fichar normal
            header("Location: dashboard.php");
        }
        exit(); 

    } else {
        header("Location: index.php?error=1");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}