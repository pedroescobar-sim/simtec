<?php
// 1. CONEXIÓN (Nombre de la BD actualizado a simtec_control)
$host = 'localhost'; $db = 'simtec_control'; $user = 'root'; $pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) { die("Error de conexión a simtec_control"); }

$id_usuario = $_GET['id'] ?? 1; 

// 2. LÓGICA DE PROCESAMIENTO
if (isset($_POST['importar_masivo'])) {
    $texto = $_POST['texto_asistencia'];
    $texto_limpio = str_ireplace(['O', 'o', 'h', 'H'], ['0', '0', ':', ':'], $texto);
    
    preg_match_all('/(\d{1,2}\/\d{1,2}\/\d{4})/', $texto_limpio, $fechas, PREG_OFFSET_CAPTURE);
    
    $registros_ok = 0;
    $registros_omitidos = 0;

    for ($i = 0; $i < count($fechas[0]); $i++) {
        $fecha_actual = $fechas[0][$i][0];
        $pos_actual = $fechas[0][$i][1];
        $pos_siguiente = isset($fechas[0][$i+1]) ? $fechas[0][$i+1][1] : strlen($texto_limpio);
        $bloque = substr($texto_limpio, $pos_actual, $pos_siguiente - $pos_actual);

        // Validar Fin de Semana
        $f = DateTime::createFromFormat('d/m/Y', $fecha_actual);
        if (!$f) continue;
        if ($f->format('N') >= 6) { $registros_omitidos++; continue; }

        // Extraer horas del bloque
        preg_match_all('/\d{1,2}:\d{2}(:\d{2})?/', $bloque, $todos_los_tiempos);
        $tiempos = $todos_los_tiempos[0];

        if (count($tiempos) >= 2) {
            $entrada_str = $tiempos[0]; // Primera hora
            $total_str = end($tiempos);  // Última hora (Total Jornada)

            if ($total_str !== '0:00:00' && $total_str !== '0:00' && !str_contains(strtoupper($bloque), 'VACACIONES')) {
                
                $t_parts = explode(':', $total_str);
                $horas = (int)$t_parts[0];
                $minutos = (int)$t_parts[1];
                $segundos = isset($t_parts[2]) ? (int)$t_parts[2] : 0;

                $total_decimal = round($horas + ($minutos / 60) + ($segundos / 3600), 2);

                // Cálculo automático de salida
                $inicio = new DateTime($entrada_str);
                $segundos_a_sumar = ($horas * 3600) + ($minutos * 60) + $segundos;
                $fin = clone $inicio;
                $fin->modify("+$segundos_a_sumar seconds");

                $fecha_sql = $f->format('Y-m-d');
                $h_entrada = $inicio->format('H:i:s');
                $h_salida = $fin->format('H:i:s');

                $sql = "INSERT INTO asistencias (usuario_id, fecha, hora_entrada, hora_salida, total_horas, estado) 
                        VALUES (?, ?, ?, ?, ?, 'finalizado')
                        ON DUPLICATE KEY UPDATE 
                        hora_entrada=VALUES(hora_entrada), 
                        hora_salida=VALUES(hora_salida), 
                        total_horas=VALUES(total_horas)";
                
                $pdo->prepare($sql)->execute([$id_usuario, $fecha_sql, $h_entrada, $h_salida, $total_decimal]);
                $registros_ok++;
            }
        }
    }
    $mensaje = "<div class='alert alert-success'>✅ <b>$registros_ok días procesados</b> con éxito en la base de datos <b>simtec_control</b>. (Se ignoraron $registros_omitidos fines de semana).</div>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Importador Simtec Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
    <style>
        body { background: #0d1117; color: #c9d1d9; font-family: sans-serif; padding: 40px 0; }
        .container { max-width: 800px; }
        .card { background: #161b22; border: 1px solid #30363d; border-radius: 12px; }
        .drop-zone { border: 2px dashed #238636; background: #0d1117; padding: 40px; border-radius: 10px; cursor: pointer; }
        .btn-success { background-color: #238636; border: none; }
        .btn-success:hover { background-color: #2ea043; }
        .btn-secondary { background-color: #21262d; border: 1px solid #30363d; color: #c9d1d9; }
        textarea { background: #0d1117 !important; color: #8b949e !important; border-color: #30363d !important; resize: none; }
    </style>
</head>
<body>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Importador Simtec</h1>
        <a href="admin_dashboard.php" class="btn btn-secondary">⬅ Volver al Panel</a>
    </div>

    <div class="card p-4">
        <?php if(isset($mensaje)) echo $mensaje; ?>
        
        <div id="drop-area" class="drop-zone text-center mb-4">
            <p id="status" class="mb-0">Suelta aquí tu PDF/Imagen o pulsa <b>Ctrl + V</b> para pegar una captura</p>
        </div>

        <form method="POST">
            <textarea name="texto_asistencia" id="output" class="form-control mb-4" rows="10" placeholder="Los datos extraídos aparecerán aquí..."></textarea>
            <button type="submit" name="importar_masivo" class="btn btn-success btn-lg w-100 fw-bold">PROCESAR E IMPORTAR</button>
        </form>
    </div>
</div>

<script>
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';
    
    async function process(file) {
        const status = document.getElementById('status');
        status.innerHTML = "⌛ <span class='text-warning'>Analizando...</span>";
        
        if (file.type === 'application/pdf') {
            const reader = new FileReader();
            reader.onload = async function() {
                const pdf = await pdfjsLib.getDocument(new Uint8Array(this.result)).promise;
                let text = '';
                for (let i = 1; i <= pdf.numPages; i++) {
                    const page = await pdf.getPage(i);
                    const content = await page.getTextContent();
                    text += content.items.map(item => item.str).join(' ') + '\n';
                }
                document.getElementById('output').value = text;
                status.innerHTML = "✅ PDF cargado.";
            };
            reader.readAsArrayBuffer(file);
        } else {
            const { data: { text } } = await Tesseract.recognize(file, 'spa');
            document.getElementById('output').value = text;
            status.innerHTML = "✅ Imagen procesada.";
        }
    }

    const dropArea = document.getElementById('drop-area');
    dropArea.addEventListener('dragover', e => { e.preventDefault(); dropArea.style.background = "#121d2f"; });
    dropArea.addEventListener('dragleave', e => { dropArea.style.background = "#0d1117"; });
    dropArea.addEventListener('drop', e => { e.preventDefault(); process(e.dataTransfer.files[0]); });
    window.addEventListener('paste', e => { 
        const item = e.clipboardData.items[0];
        if(item && item.type.includes('image')) process(item.getAsFile()); 
    });
</script>

</body>
</html>