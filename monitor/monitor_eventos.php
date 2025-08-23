<?php
require_once __DIR__ . '/modelo_monitor.php';
$rows = $pdo->query("SELECT fecha, hora, proceso, usuario, consumo_pct, LEFT(sql_text,200) AS sql_text
                     FROM consumo_critico ORDER BY fecha DESC, hora DESC LIMIT 200")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Eventos críticos</title>
    <link rel="stylesheet" href="../css/styles.css" />
</head>

<body>
    <main class="page">
        <h1>Eventos críticos</h1>
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Proceso</th>
                    <th>Usuario</th>
                    <th>% Consumo</th>
                    <th>SQL</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['fecha']); ?></td>
                        <td><?= htmlspecialchars($r['hora']); ?></td>
                        <td><?= htmlspecialchars($r['proceso']); ?></td>
                        <td><?= htmlspecialchars($r['usuario']); ?></td>
                        <td><?= htmlspecialchars(number_format($r['consumo_pct'], 2)); ?></td>
                        <td style="text-align:left;max-width:520px"><?= htmlspecialchars($r['sql_text']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a class="btn-volver" href="monitor.php">Volver al monitor</a>
    </main>
</body>

</html>