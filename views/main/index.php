<?php
$pageTitle = "Inicio";
include '../fragments/index/header.php';

?>

    <main class="home-container home-main">

        <!-- ACERCA -->
        <section id="acerca" class="home-section">
            <h2 class="home-section-title">Acerca</h2>
            <div class="card">
                <p style="margin:0;">
                    Este sistema facilita la evaluación de riesgos y el análisis de cumplimiento de manera 
                    sencilla, accesible y confiable. Sirve como punto de entrada para usuarios, administradores 
                    y colaboradores que deseen explorar sus funciones.
                </p>
            </div>
        </section>

        <!-- FUNCIONALIDADES -->
        <section id="funcionalidades" class="home-section">
            <h2 class="home-section-title">Funcionalidades principales</h2>

            <article class="home-feature-block">
                <h3 class="home-feature-title">Evaluaciones dinámicas</h3>
                <p class="home-feature-text">
                    Crea, responde y valida evaluaciones en tiempo real, con soporte para filtros y navegación 
                    sencilla.
                </p>
            </article>

            <article class="home-feature-block">
                <h3 class="home-feature-title">Indicadores visuales</h3>
                <p class="home-feature-text">
                    Visualiza resultados mediante indicadores gráficos y reportes personalizados.
                </p>
            </article>

            <article class="home-feature-block">
                <h3 class="home-feature-title">Gestión centralizada</h3>
                <p class="home-feature-text">
                    Administra actividades, riesgos y resultados desde un mismo entorno.
                </p>
            </article>
        </section>

        <!-- ACCESO -->
        <section id="acceso" class="home-section">
            <h2 class="home-section-title">Acceso al sistema</h2>
            <div class="card home-cta-card">
                <p style="margin:0;">¿Listo para comenzar?</p>
                <a class="btn-volver" href="./run.php">Entrar al sistema</a>
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