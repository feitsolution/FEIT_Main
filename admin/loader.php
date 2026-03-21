<!-- Premium Page Loading Styles -->
<style>
    /* Top Progress Bar */
    #loading-bar {
        position: fixed;
        top: 0;
        left: 0;
        width: 5%;
        height: 4px;
        background: linear-gradient(to right, #4a6cf7, #6366f1);
        z-index: 99999;
        opacity: 1;
        transition: width 0.4s ease, opacity 0.4s ease;
        box-shadow: 0 0 10px rgba(74, 108, 247, 0.5);
    }
    
    body.loaded #loading-bar {
        opacity: 0;
    }
</style>

<div id="loading-bar"></div>

<!-- Smooth Page Loading Script -->
<script>
    // Start progress bar as soon as script runs
    (function() {
        var bar = document.getElementById('loading-bar');
        bar.style.width = '40%';
    })();

    // Complete loading bar when page is fully loaded
    window.addEventListener('load', function() {
        const bar = document.getElementById('loading-bar');
        bar.style.width = '100%';
        setTimeout(() => {
            bar.style.opacity = '0';
            setTimeout(() => { bar.style.width = '0%'; }, 500);
        }, 500);
    });

    // Show loading bar on any link click
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a');
        if (!link) return;

        const href = link.getAttribute('href');
        const target = link.getAttribute('target');

        // Skip non-navigation links
        if (!href || href === '#' || href === '#!' || href.startsWith('javascript:') || 
            target === '_blank' || link.hasAttribute('data-bs-toggle') || 
            e.ctrlKey || e.metaKey || e.shiftKey) {
            return;
        }

        // Show loading bar
        const bar = document.getElementById('loading-bar');
        bar.style.opacity = '1';
        bar.style.width = '30%';
        
        // Simulating progressive loading
        setTimeout(() => { bar.style.width = '60%'; }, 200);
        setTimeout(() => { bar.style.width = '80%'; }, 500);
    });

    // Handle browser back/forward (reset bar)
    window.addEventListener('pageshow', function(e) {
        if (e.persisted) {
            const bar = document.getElementById('loading-bar');
            bar.style.width = '0%';
            bar.style.opacity = '0';
        }
    });
</script>
