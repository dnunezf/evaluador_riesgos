<?php
$pageTitle = "Información General";
include '../fragments/index/header.php';
?>

<main class="home-container home-main">

    <!-- ACERCA (unifica desarrolladores + créditos) -->
    <section id="acerca" class="home-section">
        <h2 class="home-section-title">Acerca de nosotros</h2>

        <div class="card" style="margin-bottom:16px;">
            <p style="margin:0;">
                En este espacio podrá encontrar información actualizada, canales de contacto y novedades de
                nuestros proyectos. Queremos que este sea un punto de encuentro confiable y accesible para todas
                sus necesidades de consultoría en tecnología... Y poder pasar con 100 :) 
            </p>
        </div>

        <div class="home-grid">
            <!-- Desarrolladores -->
            <div class="home-col-6">
                <h3 id="integrantes" style="margin:0 0 8px;">Desarrolladores</h3>
                <ul class="home-list">
                    <li>María José Araya Campos</li>
                    <li>Raquel Hernández Campos</li>
                    <li>David Núñez Franco</li>
                    <li>Ignacio Rodríguez Ovares</li>
                </ul>
            </div>

            <!-- Créditos institucionales -->
            <div class="home-col-6">
                <h3 id="institucional" style="margin:0 0 8px;">Créditos institucionales</h3>
                <div class="card">
                    <p style="margin:0.2rem 0;">Universidad Nacional de Costa Rica</p>
                    <p style="margin:0.2rem 0;">Facultad de Ciencias Exactas</p>
                    <p style="margin:0.2rem 0;">Escuela de Informática</p>
                    <p style="margin:0.2rem 0;">Administración de Bases de Datos</p>
                    <p style="margin:0.2rem 0;">Dr. Johnny Villalobos Murillo</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CONTACTO -->
    <section id="contacto" class="home-section">
        <h2 class="home-section-title">Contacto</h2>
        <div class="card">
            <p style="margin:.2rem 0;">📧 soporte@sistema.com</p>
            <p style="margin:.2rem 0;">☎️ +506 2222-2222</p>
            <p style="margin:.2rem 0;">📍 Dirección: San José, Costa Rica</p>
        </div>
    </section>

</main>

<?php
include '../fragments/index/footer.php';
?>
