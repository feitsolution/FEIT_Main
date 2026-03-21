<!-- Meta tag for character encoding -->
<meta charset="utf-8" />

<!-- Meta tag for IE compatibility -->
<!-- <meta http-equiv="X-UA-Compatible" content="IE=edge" /> -->

<!-- Meta tag for page description (empty) -->
<meta name="description" content="" />

<!-- Meta tag for author information (empty) -->
<meta name="author" content="" />

<!-- Link to SimpleDataTables CSS stylesheet -->
<link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />

<!-- Link to local styles.css stylesheet -->
<link href="css/styles.css" rel="stylesheet" />

<!-- Link to Google Fonts for Inter font family -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<!-- Link to Font Awesome CSS from CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">


<style>
    /* Top Progress Bar */
    #loading-bar {
        position: fixed;
        top: 0;
        left: 0;
        width: 0%;
        height: 3px;
        background: linear-gradient(to right, #4a6cf7, #6366f1);
        z-index: 99999;
        transition: width 0.4s ease;
        box-shadow: 0 0 10px rgba(74, 108, 247, 0.5);
    }
    
    /* Preloader Overlay */
    #preloader {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: #ffffff;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        transition: opacity 0.5s ease, visibility 0.5s;
    }
    
    .preloader-logo {
        width: 80px;
        height: auto;
        margin-bottom: 20px;
        animation: pulse 1.5s infinite ease-in-out;
    }
    
    .preloader-spinner {
        width: 40px;
        height: 40px;
        border: 3px solid rgba(74, 108, 247, 0.1);
        border-top: 3px solid #4a6cf7;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    @keyframes pulse { 0% { transform: scale(0.95); opacity: 0.8; } 50% { transform: scale(1.05); opacity: 1; } 100% { transform: scale(0.95); opacity: 0.8; } }
    
    body.loaded #preloader {
        opacity: 0;
        visibility: hidden;
    }
</style>

<!-- Preloader HTML -->
<div id="preloader">
    <img src="img/system/FEIT.png" alt="FEIT" class="preloader-logo">
    <div class="preloader-spinner"></div>
</div>
<div id="loading-bar"></div>

<!-- Smooth Page Loading Script -->
<script>
    // Hide preloader when page is fully loaded
    window.addEventListener('load', function() {
        document.body.classList.add('loaded');
        document.getElementById('loading-bar').style.width = '100%';
        setTimeout(() => {
            document.getElementById('loading-bar').style.opacity = '0';
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
            document.body.classList.add('loaded');
            document.getElementById('loading-bar').style.width = '0%';
            document.getElementById('loading-bar').style.opacity = '0';
        }
    });
</script>