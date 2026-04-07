<div class="loader-bg" id="site-loader">
    <div class="loader-track">
        <div class="loader-fill"></div>
    </div>
</div>

<script>
    (function() {
        const loader = document.getElementById('site-loader');
        
        // Determine if we are on the homepage
        const path = window.location.pathname;
        const isHome = path.endsWith('index.php') || path.endsWith('/') || path === '';
        const hasSeenLoader = sessionStorage.getItem('hasSeenLoader');

        if (!isHome || hasSeenLoader) {
            if (loader) loader.remove();
            return;
        }

        // Mark as seen for this session
        sessionStorage.setItem('hasSeenLoader', 'true');

        function hideLoader() {
            if (!loader) return;
            
            loader.style.opacity = '0';
            loader.style.visibility = 'hidden';
            document.body.classList.remove('loading');
            
            setTimeout(() => {
                if (loader.parentNode) {
                    loader.remove();
                }
            }, 600);
        }

        // Add loading class to body
        document.body.classList.add('loading');

        // Trigger on DOMContentLoaded for snappier feel
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            hideLoader();
        } else {
            document.addEventListener('DOMContentLoaded', hideLoader);
        }

        // Fallback for slow resources
        window.addEventListener('load', hideLoader);

        // Ultimate failsafe
        setTimeout(hideLoader, 2000);

        // Re-show loader on navigation (Optional, for that "app" feel)
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a');
            if (!link) return;

            const href = link.getAttribute('href');
            const target = link.getAttribute('target');

            if (!href || href === '#' || href.startsWith('javascript:') || 
                target === '_blank' || link.hasAttribute('data-bs-toggle') || 
                e.ctrlKey || e.metaKey || e.shiftKey) {
                return;
            }

            // Optional: You can choose NOT to re-show the full-screen loader on click
            // to avoid being too intrusive. But if you want it:
            /*
            const newLoader = loader.cloneNode(true);
            newLoader.id = 'temp-loader';
            newLoader.style.opacity = '0';
            document.body.appendChild(newLoader);
            setTimeout(() => newLoader.style.opacity = '1', 10);
            */
        });
    })();
</script>
