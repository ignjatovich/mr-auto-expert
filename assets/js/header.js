// ============================================
// HAMBURGER MENU FUNCTIONALITY
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.getElementById('hamburger-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    const menuOverlay = document.getElementById('menu-overlay');
    const body = document.body;

    if (!hamburger || !mobileMenu || !menuOverlay) {
        console.warn('Hamburger menu elementi nisu pronađeni');
        return;
    }

    // Funkcija za otvaranje menija
    function openMenu() {
        hamburger.classList.add('active');
        mobileMenu.classList.add('active');
        menuOverlay.classList.add('active');
        body.classList.add('menu-open');
    }

    // Funkcija za zatvaranje menija
    function closeMenu() {
        hamburger.classList.remove('active');
        mobileMenu.classList.remove('active');
        menuOverlay.classList.remove('active');
        body.classList.remove('menu-open');
    }

    // Toggle menu - klik na hamburger
    hamburger.addEventListener('click', function(e) {
        e.stopPropagation();

        if (mobileMenu.classList.contains('active')) {
            closeMenu();
        } else {
            openMenu();
        }
    });

    // Zatvori meni - klik na overlay
    menuOverlay.addEventListener('click', function() {
        closeMenu();
    });

    // Zatvori meni - klik na link u meniju
    const mobileLinks = mobileMenu.querySelectorAll('.mobile-link');
    mobileLinks.forEach(link => {
        link.addEventListener('click', function() {
            closeMenu();
        });
    });

    // Zatvori meni - ESC taster
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && mobileMenu.classList.contains('active')) {
            closeMenu();
        }
    });

    // Zatvori meni ako se promeni veličina prozora (prelazak na desktop)
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth > 768 && mobileMenu.classList.contains('active')) {
                closeMenu();
            }
        }, 250);
    });

    // Prevent scroll propagation u meniju
    mobileMenu.addEventListener('wheel', function(e) {
        e.stopPropagation();
    }, { passive: false });

    // Touch gestures - swipe desno za zatvaranje
    let touchStartX = 0;
    let touchEndX = 0;

    mobileMenu.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });

    mobileMenu.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    }, { passive: true });

    function handleSwipe() {
        // Swipe desno (više od 100px) zatvara meni
        if (touchEndX > touchStartX + 100) {
            closeMenu();
        }
    }
});