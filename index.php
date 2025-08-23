<?php /* index.php – Página de inicio basada 1:1 en el PDF "Índara Consultores" */ ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <title>ÍNDARA Consultores – Inicio</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="css/styles.css" />
</head>

<body>

    <!-- NAV (siempre visible con CSS sticky) -->
    <header class="home-navbar">
        <div class="home-container home-nav-inner">
            <div class="home-brand">
                <div class="home-logo">Í</div>
                <span>ÍNDARA <span style="font-weight:700;color:var(--muted)">Consultores</span></span>
            </div>
            <nav class="home-nav-links">
                <a href="#integrantes">Acerca de nosotros</a>
                <a href="#vision">Visión del producto</a>
                <a href="#ubicacion">Donde encontrar el producto</a>
                <a href="#producto">Información sobre el producto</a>
            </nav>
        </div>
    </header>

    <!-- HEADER estilo imagen -->
    <section class="home-hero-banner">
        <div class="home-container home-hero-wrap">
            <div class="home-hero-title">
                <span class="home-hero-line1">ÍNDARA</span>
                <span class="home-hero-line2">Consultores</span>
            </div>
            <div class="home-hero-logo small">
                <img src="imagenes/logo-indara-transparente.png" alt="ÍNDARA Consultores">
            </div>
        </div>
    </section>

    <main class="home-container home-main">
        <!-- ACERCA (unifica desarrolladores + créditos) -->
        <section id="acerca" class="home-section">
            <h2 class="home-section-title">Acerca de nosotros</h2>

            <div class="card" style="margin-bottom:16px;">
                <p style="margin:0;">
                    En este espacio podrá encontrar información actualizada, canales de contacto y novedades de
                    nuestros proyectos. Queremos que este sea un punto de encuentro confiable y accesible para todas
                    sus necesidades de consultoría en tecnología.
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

        <!-- Visión del producto -->
        <section id="vision" class="home-section">
            <h2 class="home-section-title">Visión del producto</h2>
            <div class="card">
                <p style="margin:0;">
                    Herramienta asistida por computadora para facilitar la evaluación del grado de exposición a los riesgos
                    de integridad, disponibilidad y confidencialidad inherente a los sistemas gestores de bases de datos,
                    basado en normas internacionales y las tareas o actividades de la administración de bases de datos,
                    en cumplimiento con la ley de control interno en Costa Rica, y la directriz de administración de riesgos.
                </p>
            </div>
        </section>

        <!-- Dónde encontrar el producto -->
        <section id="ubicacion" class="home-section">
            <h2 class="home-section-title">Dónde encontrar el producto</h2>
            <div class="card">
                <p style="margin:.2rem 0;">📍 Nuestro producto está disponible en la siguiente dirección:</p>
                <p style="margin:.2rem 0;">
                    👉 <a href="evaluacion_form/run.php">Abrir evaluación</a>
                </p>
            </div>
        </section>

        <!-- Información sobre el producto (descripción + imagen debajo, sin cuadros) -->
        <section id="producto" class="home-product home-section">
            <h2 class="home-section-title">Información sobre el producto</h2>

            <div class="card" style="margin-bottom:14px;">
                <p style="margin:0;">
                    Conoce de un vistazo lo que ofrece la herramienta y mira capturas de ejemplo del flujo de evaluación.
                </p>
            </div>

            <!-- Bloque 1 -->
            <article class="home-feature-block">
                <h3 class="home-feature-title">Filtrado dinámico</h3>
                <p class="home-feature-text">
                    Filtra preguntas por riesgos (C, I, D) y por norma, con actualización en tiempo real.
                </p>
                <img src="imagenes/demo-global.png" alt="Filtrado por riesgo y norma (demo)" class="home-feature-img">
            </article>

            <!-- Bloque 2 -->
            <article class="home-feature-block">
                <h3 class="home-feature-title">Navegación por actividades</h3>
                <p class="home-feature-text">
                    Recorre las actividades con botones Anterior / Siguiente sin perder el contexto.
                </p>
                <img src="imagenes/demo-actividad.png" alt="Indicadores por actividad (demo)" class="home-feature-img">
            </article>

            <!-- Bloque 3 -->
            <article class="home-feature-block">
                <h3 class="home-feature-title">Indicadores y resultados</h3>
                <p class="home-feature-text">
                    Indicador global tipo semáforo y barras por actividad/ riesgo, ignorando NA.
                </p>
                <img src="imagenes/demo-filtro.png" alt="Indicador global de riesgo (demo)" class="home-feature-img">
            </article>

            <!-- Bloque 4 -->
            <article class="home-feature-block">
                <h3 class="home-feature-title">Validación de respuestas</h3>
                <p class="home-feature-text">
                    Señala si faltó responder alguna pregunta y te lleva directo a completarla.
                </p>
                <img src="imagenes/demo-validacion.png" alt="Validación de respuestas incompletas (demo)" class="home-feature-img">
            </article>

            <!-- CTA -->
            <div class="card home-cta-card">
                <p style="margin:0;">¿Listo para probarlo?</p>
                <a class="btn-volver" href="evaluacion_form/run.php">Abrir evaluación</a>
            </div>
        </section>
    </main>

    <footer class="home-footer">
        <div class="home-container">© <?= date('Y'); ?> ÍNDARA Consultores — Todos los derechos reservados.</div>
    </footer>

    <script>
        // Scroll suave con COMPENSACIÓN por el header sticky
        document.querySelectorAll('a[href^="#"]').forEach(a => {
            a.addEventListener('click', e => {
                const id = a.getAttribute('href').slice(1);
                const el = document.getElementById(id);
                if (!el) return;
                e.preventDefault();
                const header = document.querySelector('.home-navbar');
                const offset = header ? header.offsetHeight + 8 : 0;
                const y = el.getBoundingClientRect().top + window.pageYOffset - offset;
                window.scrollTo({
                    top: y,
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>

</html>