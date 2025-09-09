<?php


session_start(); 

if (isset($_SESSION['username'])) {
    header("Location: ./monitor.php");
    exit;
}


if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    echo "<script>alert('$error');</script>";
    unset($_SESSION['error_message']);
}

$pageTitle = "Monitor de Bases de Datos";
include '../fragments/index/header.php';
?>
<main class="home-container home-main">
    <section id="funcionalidades" class="home-section" style="text-align:center;">
        <h2 class="home-section-title">Iniciar Sesión en Oracle Express</h2>
        <article class="home-feature-block">
            <h3 class="home-feature-title">Ingresa tus credenciales para accesar a la base de datos</h3>
            <form action="../../model/monitor/api/login.php" method="post" class="card" style="max-width:400px; margin-top:16px; margin: auto;">
                <div style="margin-bottom:8px;">
                    <label for="username">Usuario:</label>
                    <input type="text" id="username" name="username" required style="width:100%; padding:8px; box-sizing:border-box;">
                </div>
                <div style="margin-bottom:16px;">
                    <label for="password">Contraseña:</label>
                    <input type="password" id="password" name="password" required style="width:100%; padding:8px; box-sizing:border-box;">
                </div>
                <button type="submit" class="btn-volver" style="width:100%;">Iniciar Sesión</button>
        </article>
    </section>
</main>

<?php include '../fragments/index/footer.php'; ?>