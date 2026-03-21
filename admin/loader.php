<!-- Premium Page Loading Styles -->
<style>
    /* Top Progress Bar */
    #loading-bar {
        position: fixed;
        top: 0;
        left: 0;
        width: 0%;
        height: 3px;
        background: linear-gradient(to right, #4a6cf7, #6366f1, #a855f7);
        z-index: 99999;
        opacity: 1;
        transition: width 0.4s cubic-bezier(0.1, 0.7, 1.0, 0.1), opacity 0.4s ease;
        box-shadow: 0 0 10px rgba(74, 108, 247, 0.5);
    }
    
    #loading-bar.complete {
        width: 100% !important;
        opacity: 0;
    }
</style>

<div id="loading-bar"></div>

<!-- Smooth Page Loading Script -->
<script>
    (function() {
        const bar = document.getElementById('loading-bar');
        
        // Start progress
        setTimeout(() => {
            if (bar) bar.style.width = '30%';
        }, 10);

        // Simulated progress
        const progressInterval = setInterval(() => {
            if (!bar) return;
            let currentWidth = parseFloat(bar.style.width);
            if (currentWidth < 90) {
                bar.style.width = (currentWidth + (90 - currentWidth) * 0.1) + '%';
            }
        }, 200);

        // Handle Page Load
        function completeLoading() {
            if (document.body.classList.contains('loaded')) return;
            
            clearInterval(progressInterval);
            if (bar) {
                bar.classList.add('complete');
                setTimeout(() => {
                    bar.style.width = '0%';
                    bar.classList.remove('complete');
                }, 500);
            }
            document.body.classList.add('loaded');
        }

        // Trigger on DOMContentLoaded for faster visibility
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            completeLoading();
        } else {
            document.addEventListener('DOMContentLoaded', completeLoading);
        }

        // Also trigger on full window load as a fallback
        window.addEventListener('load', completeLoading);

        // Ultimate failsafe: ensure page is visible after 2 seconds
        setTimeout(completeLoading, 2000);

        // Show loading bar on any navigation link click
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

            // Show loading bar for the next page
            if (bar) {
                bar.style.opacity = '1';
                bar.style.width = '40%';
                
                // Immediate fade out of current body for transition
                document.body.style.opacity = '0.7';
            }
        });

        // Handle browser back/forward (reset bar)
        window.addEventListener('pageshow', function(e) {
            if (e.persisted) {
                if (bar) {
                    bar.style.width = '0%';
                    bar.style.opacity = '0';
                }
                document.body.classList.add('loaded');
                document.body.style.opacity = '1';
            }
        });
    })();
</script>