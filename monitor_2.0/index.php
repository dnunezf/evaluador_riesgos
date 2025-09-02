<?php
$mysqlHost = 'localhost';
$mysqlDb   = 'test_db';
$mysqlUser = 'root';
$mysqlPass = 'root';

if (isset($_GET['action']) && $_GET['action'] === 'get_memory') {
    try {
        $pdo = new PDO(
            "mysql:host=$mysqlHost;dbname=$mysqlDb;charset=utf8",
            $mysqlUser,
            $mysqlPass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    } catch (PDOException $e) {
        echo json_encode(['time'=>date('H:i:s'),'percent'=>0,'connections'=>0]);
        exit;
    }

    // Active connections
    $stmt = $pdo->query("SHOW STATUS LIKE 'Threads_connected'");
    $threadsConnected = 0;
    if ($stmt) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $threadsConnected = (int)($row['Value'] ?? 0);
    }

    // Buffer sizes
    $vars = [
        'innodb_buffer_pool_size',
        'key_buffer_size',
        'sort_buffer_size',
        'read_buffer_size',
        'join_buffer_size',
        'tmp_table_size',
        'max_connections'
    ];
    $memory = [];
    foreach ($vars as $var) {
        $stmt = $pdo->query("SELECT @@$var AS val");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $memory[$var] = (int)($row['val'] ?? 0);
    }

    $perConnection = $memory['sort_buffer_size'] + $memory['read_buffer_size'] + $memory['join_buffer_size'] + $memory['tmp_table_size'];
    $usedMemory = $memory['innodb_buffer_pool_size'] + $memory['key_buffer_size'] + ($perConnection * $threadsConnected);
    $maxMemory  = $memory['innodb_buffer_pool_size'] + $memory['key_buffer_size'] + ($perConnection * $memory['max_connections']);

    $percentUsed = $maxMemory>0 ? round(($usedMemory / $maxMemory) * 100, 2) : 0;

    echo json_encode([
        'time' => date('H:i:s'),
        'percent' => $percentUsed,
        'connections' => $threadsConnected
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>MySQL Memory Monitor</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body { font-family: Arial, sans-serif; background: #f4f4f4; text-align: center; }
canvas { background: #fff; border: 1px solid #ccc; margin-top: 20px; }
</style>
</head>
<body>

<h2>MySQL Memory Usage Monitor</h2>
<canvas id="memoryChart" width="800" height="400"></canvas>
<p id="stats"></p>

<script>
const ctx = document.getElementById('memoryChart').getContext('2d');
const data = { labels: [], datasets: [{
    label: 'Memory Usage (%)',
    data: [],
    borderColor: 'rgba(75,192,192,1)',
    backgroundColor: 'rgba(75,192,192,0.2)',
    tension: 0.2
}]};
const chart = new Chart(ctx, { type: 'line', data: data, options: { scales: { y: { beginAtZero:true, max:100 } } } });

let alertFired = false;

function fetchMemory() {
    fetch('?action=get_memory')
        .then(res => res.json())
        .then(result => {
            const time = result.time;
            const percent = Number(result.percent || 0);
            const connections = Number(result.connections || 0);

            data.labels.push(time);
            data.datasets[0].data.push(percent);

            if (data.labels.length > 20) {
                data.labels.shift();
                data.datasets[0].data.shift();
            }

            chart.update();
            document.getElementById('stats').innerText =
                `Connections: ${connections} | Approx. Memory Usage: ${percent}%`;

            if (percent > 59 && !alertFired) {
                alert(`⚠️ High Memory Usage: ${percent}%`);
                alertFired = true;
            } else if (percent <= 85) {
                alertFired = false;
            }
        })
        .catch(err => console.error(err));
}

setInterval(fetchMemory, 5000);
fetchMemory();
</script>

</body>
</html>
