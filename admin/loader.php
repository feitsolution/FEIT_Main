<!-- HitZak Style Full-Screen Loader -->
<style>
    .loader-bg {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: #ffffff;
        z-index: 999999;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        align-items: center;
        transition: opacity 0.5s ease, visibility 0.5s ease;
    }

    .loader-track {
        height: 5px;
        width: 100%;
        background: #f1f5f9;
        position: relative;
        overflow: hidden;
    }

    .loader-fill {
        width: 300px;
        height: 100%;
        background: #4a6cf7;
        position: absolute;
        top: 0;
        left: 0;
        animation: hitZak 0.8s ease-in-out infinite alternate;
        box-shadow: 0 0 15px rgba(74, 108, 247, 0.4);
    }

    @keyframes hitZak {
        0% {
            left: 0;
            transform: translateX(-1%);
        }
        100% {
            left: 100%;
            transform: translateX(-99%);
        }
    }

    /* Dark Mode support if needed */
    [data-theme-mode="dark"] .loader-bg {
        background: #1a1a1a;
    }
    [data-theme-mode="dark"] .loader-track {
        background: #2d2d2d;
    }

    /* Prevent interaction while loading */
    body.loading {
        overflow: hidden;
    }
</style>

<div class="loader-bg" id="site-loader">
    <div class="loader-track">
        <div class="loader-fill"></div>
    </div>
</div>

<script>
    (function() {
        const loader = document.getElementById('site-loader');
        
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
