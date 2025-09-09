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