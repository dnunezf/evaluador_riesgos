<?php
// procesar.php
// Procesa respuestas y muestra: tabla por riesgo + barra global + barras por actividad y riesgo
require_once 'modelo.php';

// Etiquetas y contadores
$labels = ['C' => 'Confidencialidad', 'I' => 'Integridad', 'D' => 'Disponibilidad'];

$resultadoRiesgos = [
    'C' => ['S' => 0, 'N' => 0, 'NA' => 0],
    'I' => ['S' => 0, 'N' => 0, 'NA' => 0],
    'D' => ['S' => 0, 'N' => 0, 'NA' => 0],
];

// Para encabezados por actividad
$tareas = getTareas();
$mapTA = [];
foreach ($tareas as $t) {
    $mapTA[$t['Id']] = $t['Nombre'];
}

// Agregado por Actividad y Riesgo
$porTA = []; // $porTA[TA_ID]['C'|'I'|'D'] = ['S'=>x,'N'=>y,'NA'=>z]

// Analizar todas las respuestas recibidas
foreach ($_POST as $campo => $respuesta) {
    if (strpos($campo, 'respuesta_') === 0) {
        // Nombre: respuesta_{TAId}_{REId}
        $partes = explode('_', $campo);
        if (count($partes) < 3) continue;
        $taId = $partes[1];
        $reId = $partes[2];

        // Obtener riesgos del requisito
        $riesgos = getRiesgosPorRequisito($reId);

        foreach ($riesgos as $r) {
            // Normalizamos a C/I/D
            $tipo = strtoupper(substr($r['Tipo'], 0, 1));
            if (!isset($resultadoRiesgos[$tipo])) {
                $resultadoRiesgos[$tipo] = ['S' => 0, 'N' => 0, 'NA' => 0];
            }
            if (!isset($porTA[$taId][$tipo])) {
                $porTA[$taId][$tipo] = ['S' => 0, 'N' => 0, 'NA' => 0];
            }
            if (in_array($respuesta, ['S', 'N', 'NA'], true)) {
                $resultadoRiesgos[$tipo][$respuesta]++;
                $porTA[$taId][$tipo][$respuesta]++;
            }
        }
    }
}

// Helpers
function colorRiesgo(array $datos)
{
    $totalSN = $datos['S'] + $datos['N'];
    if ($totalSN == 0) return 'gris';
    $porcN = ($datos['N'] / $totalSN) * 100;
    if ($porcN >= 60) return 'rojo';
    if ($porcN >= 30) return 'amarillo';
    return 'verde';
}
function porcentajeN(array $datos)
{
    $totalSN = $datos['S'] + $datos['N'];
    if ($totalSN == 0) return 0;
    return round(($datos['N'] / $totalSN) * 100, 1);
}
function porcentajeS(array $datos)
{
    $totalSN = $datos['S'] + $datos['N'];
    if ($totalSN == 0) return 0;
    return round(($datos['S'] / $totalSN) * 100, 1);
}

/**
 * Barra con gradiente (rojo→amarillo→verde) y flecha.
 * $porcSi posiciona la flecha. La leyenda muestra %No para coherencia con semáforo.
 */
function barraHTML(float $porcSi, string $etiquetaColor, float $porcNo = null)
{
    $porcSi = max(0, min(100, $porcSi));
    if ($porcNo === null) $porcNo = round(100 - $porcSi, 1);
    $left = $porcSi; // Flecha en %Sí
    return '
    <div class="progress">
        <div class="gradient"></div>
        <div class="marker" style="left: calc(' . $left . '% - 6px);" title="' . $porcSi . '% Sí"></div>
    </div>
    <div class="leyenda ' . $etiquetaColor . '">Evaluación: ' . ucfirst($etiquetaColor) . ' (' . $porcNo . '% No)</div>';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Resultados de Evaluación</title>
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
    <div class="page">
        <h1>Resultado de Evaluación de Riesgos</h1>

        <!-- Tabla de totales por riesgo -->
        <table>
            <thead>
                <tr>
                    <th>Riesgo</th>
                    <th>✓ S</th>
                    <th>✗ N</th>
                    <th>NA</th>
                    <th>Evaluación</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $niveles = [];
                foreach (['C', 'I', 'D'] as $tipo):
                    $valores = $resultadoRiesgos[$tipo];
                    $color   = colorRiesgo($valores);
                    $niveles[] = $color;
                ?>
                    <tr>
                        <td><?php echo $labels[$tipo]; ?></td>
                        <td><?php echo $valores['S']; ?></td>
                        <td><?php echo $valores['N']; ?></td>
                        <td><?php echo $valores['NA']; ?></td>
                        <td><span class="<?php echo $color; ?>"><?php echo ucfirst($color); ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php
        // Indicador global (color por tus reglas)
        $riesgoGlobal = in_array('rojo', $niveles, true) ? 'ALTO' : (in_array('amarillo', $niveles, true) ? 'MEDIO' : 'BAJO');
        $colorGlobal  = $riesgoGlobal === 'ALTO' ? 'rojo' : ($riesgoGlobal === 'MEDIO' ? 'amarillo' : 'verde');

        // Promedios globales %Sí y %No (ignorando NA)
        $promSi = 0;
        $promNo = 0;
        $cuenta = 0;
        foreach (['C', 'I', 'D'] as $tipo) {
            $promSi += porcentajeS($resultadoRiesgos[$tipo]);
            $promNo += porcentajeN($resultadoRiesgos[$tipo]);
            $cuenta++;
        }
        $promSi = $cuenta ? round($promSi / $cuenta, 1) : 0;
        $promNo = $cuenta ? round($promNo / $cuenta, 1) : 0;
        ?>

        <h2 class="section-title">Indicador Global de Riesgo</h2>
        <?php echo barraHTML($promSi, $colorGlobal, $promNo); ?>

        <!-- Barras por Actividad y por Riesgo -->
        <h2 class="section-title">Indicadores por Actividad</h2>
        <div class="grid">
            <?php if (empty($porTA)): ?>
                <p class="muted">No se recibieron respuestas.</p>
            <?php else: ?>
                <?php foreach ($porTA as $taId => $riesgosTA): ?>
                    <h3><?php echo htmlspecialchars($mapTA[$taId] ?? ("Actividad #" . $taId)); ?></h3>
                    <?php foreach ($riesgosTA as $tipo => $datos):
                        $color = colorRiesgo($datos);
                        $porcSi  = porcentajeS($datos);
                        $porcNo  = porcentajeN($datos);
                    ?>
                        <div class="risk-title"><?php echo $labels[$tipo]; ?></div>
                        <?php echo barraHTML($porcSi, $color, $porcNo); ?>
                    <?php endforeach; ?>
                    <hr>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Botón para volver al formulario -->
        <a href="evaluacion.php" class="btn-volver">⬅ Volver al formulario</a>
    </div>
</body>

</html>