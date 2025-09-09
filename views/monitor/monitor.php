<?php
session_start();

// Conexión a Oracle
if (!isset($_SESSION['username']) || !isset($_SESSION['password'])) {
    header("Location: ./index.php");
    exit;
}

$conn = oci_connect($_SESSION['username'], $_SESSION['password'], 'localhost/XEPDB1');
if (!$conn) {
    $e = oci_error();
    die("Connection failed: " . $e['message']);
}

$sql = "
    SELECT db_link, username, host, created, owner
    FROM dba_db_links
    WHERE db_link NOT LIKE 'DBMS%'
      AND (owner = 'PUBLIC' OR owner = :current_user)
    ORDER BY db_link
";

$upper_username = strtoupper($_SESSION['username']);
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":current_user", $upper_username);
oci_execute($stid);


$pageTitle = "Monitor de Bases de Datos";
include '../fragments/index/header.php';
?>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert success"><?= $_SESSION['success_message']; ?></div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert error"><?= $_SESSION['error_message']; ?></div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<a href="../../model/monitor/api/logout.php" class="btn-volver" style="margin-bottom:16px;">← Volver al Monitor</a>

<main class="page">
    <h2 style="text-align:center;">Public Database Links</h2>

    <!-- 🔘 Botón para ir a monitores.php -->
    <div style="text-align:center; margin:16px 0;">
        <a href="./monitores.php" class="btn-volver" style="padding:8px 16px; display:inline-block;">
            📊 Ir a Monitores de Consumo
        </a>
    </div>

    <!-- Button to open modal -->
    <button id="openModalBtn" class="btn-volver" style="margin-bottom:16px;">
        ➕ Crear un nuevo DB Link
    </button>

    <!-- Modal overlay -->
    <div id="dblinkModal" class="modal" style="
        display:none;
        position: fixed;
        top:0;
        left:0;
        width:100%;
        height:100%;
        background-color: rgba(0,0,0,0.5);
        justify-content: center;
        align-items: center;
        z-index: 1000;
      ">
        <div class="modal-content card" style="
          background: white;
          padding: 20px;
          max-width: 500px;
          width: 90%;
          position: relative;
          border-radius: 8px;
      ">
      <span class="close" style="
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
        ">&times;</span>
            <h2 class="home-section-title" style="text-align:center;">Crear un nuevo DB Link</h2>
            <form action="create_dblink.php" method="post" style="text-align:left; margin-top: 16px;">
                <div style="margin-bottom:12px;">
                    <label for="dblink_name">Nombre del DB Link:</label>
                    <input type="text" id="dblink_name" name="dblink_name" required style="width:100%; padding:8px; box-sizing:border-box;">
                </div>
                <div style="margin-bottom:12px;">
                    <label for="username">Usuario remoto:</label>
                    <input type="text" id="username" name="username" required style="width:100%; padding:8px; box-sizing:border-box;">
                </div>
                <div style="margin-bottom:12px;">
                    <label for="password">Contraseña remota:</label>
                    <input type="password" id="password" name="password" required style="width:100%; padding:8px; box-sizing:border-box;">
                </div>
                <div style="margin-bottom:12px;">
                    <label for="host">Host/IP:</label>
                    <input type="text" id="host" name="host" required style="width:100%; padding:8px; box-sizing:border-box;">
                </div>
                <div style="margin-bottom:12px;">
                    <label for="port">Puerto:</label>
                    <input type="number" id="port" name="port" value="1521" required style="width:100%; padding:8px; box-sizing:border-box;">
                </div>
                <div style="margin-bottom:20px;">
                    <label for="service">Service Name:</label>
                    <input type="text" id="service" name="service" required style="width:100%; padding:8px; box-sizing:border-box;">
                </div>
                <div style="margin-bottom:20px;">
                    <label for="public">Public:</label>
                    <input type="checkbox" id="public" name="public" style="width:100%; padding:8px; transform: scale(5);">
                </div>
                <button type="submit" class="btn-volver" style="width:100%;">Crear DB Link</button>
            </form>
        </div>
    </div>

    <!-- Existing DB Links Table -->
    <h3>Existing Public DB Links</h3>
    <table>
        <thead>
        <tr>
            <th>DB Link</th>
            <th>Remote User</th>
            <th>Host / Connection String</th>
            <th>Created</th>
            <th>Owner</th>
            <th>Status</th>
        </tr>
        </thead>
        <tbody>
        <?php while (($row = oci_fetch_assoc($stid)) != false): ?>
            <?php
            $dblink = htmlspecialchars($row['DB_LINK']);
            $remote_user = htmlspecialchars($row['USERNAME']);
            $host = htmlspecialchars($row['HOST']);
            $created = htmlspecialchars($row['CREATED']);
            $owner = htmlspecialchars($row['OWNER']);
            ?>
            <tr>
                <td><?= $dblink ?></td>
                <td><?= $remote_user ?></td>
                <td><?= $host ?></td>
                <td><?= $created ?></td>
                <td><?= $owner ?></td>
                <td id="status-<?= $dblink ?>"><span class="gris">Checking...</span></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

</main>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const modal = document.getElementById("dblinkModal");
        const openBtn = document.getElementById("openModalBtn");
        const closeBtn = modal.querySelector(".close");

        // Open modal
        openBtn.addEventListener("click", () => {
            modal.style.display = "flex";
        });

        // Close modal
        closeBtn.addEventListener("click", () => {
            modal.style.display = "none";
        });

        // Close if click outside content
        window.addEventListener("click", (event) => {
            if (event.target === modal) {
                modal.style.display = "none";
            }
        });

        // Function to refresh status of all DB Links
        let abortController;

        function refreshDbLinkStatus() {
            document.querySelectorAll("td[id^='status-']").forEach(cell => {
                const dblink = cell.id.replace("status-", "");
                fetch("check_dblink.php?dblink=" + encodeURIComponent(dblink))
                    .then(res => res.json())
                    .then(data => {
                        cell.innerHTML = data.online ? "<span class='verde'>ONLINE</span>" : "<span class='gris'>OFFLINE</span>";
                    })
                    .catch(() => {
                        cell.innerHTML = "<span class='gris'>ERROR</span>";
                    });
            });
        }



        // Stop interval on page unload
        // Initial refresh on page load
        refreshDbLinkStatus();

        // Refresh every 5 seconds
        const intervalId = setInterval(refreshDbLinkStatus, 50000);

        // Abort ongoing fetches and stop interval on page unload
        window.addEventListener('beforeunload', () => {
            clearInterval(intervalId);
            if (abortController) abortController.abort();
        });

    });

</script>

<?php
oci_free_statement($stid);
oci_close($conn);
include '../fragments/index/footer.php';
?>
