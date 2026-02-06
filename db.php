<?php
$host = 'localhost';
$db   = 'simtec_control';
$user = 'root'; // En XAMPP/WAMP el usuario por defecto siempre es 'root'
$pass = '';     // En XAMPP suele estar vacío. En MAMP es 'root'
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
     // Si quieres probar que funciona, puedes quitar el comentario a la siguiente línea:
     // echo "Conexión exitosa"; 
} catch (\PDOException $e) {
     // Mostramos un mensaje más amigable
     die("Error al conectar a la base de datos: " . $e->getMessage());
}
?>