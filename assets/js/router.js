const router = {
    routes: {
        'dashboard' : 'views/dashboard.php',
        'upload'    : 'views/upload.php',
        'formulario': 'views/formulario.php',
        'admin'     : 'views/admin.php',
        '404'       : 'views/404.php'
    },

    init() {
        window.addEventListener('hashchange', () => {
            this.loadPage();
            this.updateNav();
        });
        this.loadPage();
        this.updateNav();
    },

    updateNav() {
        let currentHash = window.location.hash || '#dashboard';
        
        const activeRoute = currentHash.split('?')[0];
        
        const links = document.querySelectorAll('.nav-link');

        links.forEach(link => {
            link.classList.remove('active');

            const href = link.getAttribute('href');

            if (href && href.endsWith(activeRoute)) {
                link.classList.add('active');
            }
        });
    },

    async loadPage() {
        const app = document.getElementById('app');
        
        let fullHash = window.location.hash.replace('#', '') || 'dashboard';

        const [route, queryString] = fullHash.split('?');

        const viewPath = this.routes[route];

        if (!viewPath) {
            return this.renderView(this.routes['404']);
        }

        const finalUrl = queryString ? `${viewPath}?${queryString}` : viewPath;

        this.renderView(finalUrl);
    },

    async renderView(url) {
        const app = document.getElementById('app');
        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error();
            
            const html = await response.text();
            app.innerHTML = html;
            this.executeScripts(app);

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            this.updateNav();
        } catch (e) {
            const err404 = await fetch(this.routes['404']);
            app.innerHTML = await err404.text();
        }
    },

    executeScripts(container) {
        const scripts = container.querySelectorAll('script');
        scripts.forEach(oldScript => {
            const newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
            
            try {
                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                oldScript.parentNode.replaceChild(newScript, oldScript);
            } catch (e) {
                console.error("Erro na execução do script da view:", e);
            }
        });
    }
};

router.init();
lucide.createIcons();