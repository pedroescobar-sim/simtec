<?php
// ESTO DEBE IR AL PRINCIPIO DEL TODO, SIN ESPACIOS ANTES
$ya_rellenado = isset($_COOKIE['hamze_completado']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_hamze'])) {
    // Seteamos la cookie por 1 año
    setcookie('hamze_completado', 'true', time() + (365 * 24 * 60 * 60), "/");
    // Forzamos la recarga para que el cambio sea instantáneo
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Hamze El Botón</title>
    <style>
        /* Aquí pegas los estilos CSS que te pasé antes */
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f4f4f4; }
        .contenedor { background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center; }
        .boton-hamze { background: #000; color: #fff; border: none; padding: 10px 25px; border-radius: 50px; cursor: pointer; font-size: 1.1rem; }
        .exito { color: #27ae60; font-weight: bold; }
    </style>
</head>
<body>

    <div class="contenedor">
        <?php if (!$ya_rellenado): ?>
            <h2>Bienvenido</h2>
            <form method="POST">
                <button type="submit" name="enviar_hamze" class="boton-hamze">
                    PRESIONAR BOTÓN
                </button>
            </form>
        <?php else: ?>
            <p class="exito">✅ Ya has registrado tu click. ¡Vuelve pronto!</p>
        <?php endif; ?>
    </div>

</body>
</html>