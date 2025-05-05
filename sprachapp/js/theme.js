/**
 * SprachApp - Theme Switcher
 * Safari-kompatible Version
 * Sorgt dafür, dass das dunkle Design auf allen Geräten korrekt angewendet wird
 */

// Sofortige Ausführung für Safari
(function() {
    // Safari-Erkennung
    const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
    
    // Wenn Safari erkannt wird, direkt spezielle Anpassungen vornehmen
    if (isSafari) {
        console.log("Safari erkannt, wende spezielle Anpassungen an");
        // Füge Safari-spezifische Klasse hinzu
        document.documentElement.classList.add('safari');
        document.body.classList.add('safari');
        
        // Forciere bestimmte Styles für Safari
        const safariStyles = document.createElement('style');
        safariStyles.textContent = `
            /* Safari-spezifische Anpassungen */
            body, html {
                background-color: #121212 !important;
                color: rgba(255, 255, 255, 0.87) !important;
            }
            
            .card, .navbar, .breadcrumb, footer {
                background-color: #1e1e1e !important;
            }
            
            .form-control {
                background-color: #2d2d2d !important;
                color: rgba(255, 255, 255, 0.87) !important;
            }
            
            /* Verhindert weiße Blitze beim Laden */
            * {
                transition: none !important;
            }
        `;
        document.head.appendChild(safariStyles);
    }
})();

document.addEventListener('DOMContentLoaded', function() {
    // Standardmäßig dunkles Design aktivieren (force dark mode)
    forceDarkMode();
    
    // Auch beim Laden der Seite immer das Dark Theme anwenden
    applyDarkTheme();
    
    // Theme Switcher in den Einstellungen behandeln, falls vorhanden
    const darkModeToggle = document.getElementById('dark_mode_enabled');
    if (darkModeToggle) {
        darkModeToggle.addEventListener('change', function() {
            if (this.checked) {
                applyDarkTheme();
            } else {
                removeDarkTheme();
            }
        });
    }
    
    // Safari-spezifische Nachbehandlung
    const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
    if (isSafari) {
        safariSpecificFixes();
    }
});

/**
 * Safari-spezifische Anpassungen nach dem Laden der Seite
 */
function safariSpecificFixes() {
    // Nach Safari-Bug suchen, bei dem CSS-Variablen manchmal nicht funktionieren
    const bodyBgColor = getComputedStyle(document.body).backgroundColor;
    
    // Wenn der Hintergrund nicht dunkel ist, manuell setzen
    if (bodyBgColor === 'rgba(0, 0, 0, 0)' || bodyBgColor === 'rgb(255, 255, 255)') {
        console.log("Safari-Bug erkannt: Body hat falschen Hintergrund, korrigiere...");
        
        // Alle wichtigen Elemente direkt stylen
        document.body.style.backgroundColor = '#121212';
        document.body.style.color = 'rgba(255, 255, 255, 0.87)';
        
        // Alle Cards finden und direkt Hintergrund setzen
        document.querySelectorAll('.card').forEach(card => {
            card.style.backgroundColor = '#1e1e1e';
            card.style.borderColor = 'rgba(255, 255, 255, 0.12)';
        });
        
        // Alle Formulareingaben stylen
        document.querySelectorAll('.form-control').forEach(input => {
            input.style.backgroundColor = '#2d2d2d';
            input.style.color = 'rgba(255, 255, 255, 0.87)';
            input.style.borderColor = 'rgba(255, 255, 255, 0.12)';
        });
        
        // Navigationselemente stylen
        document.querySelectorAll('.navbar, .breadcrumb, footer').forEach(el => {
            el.style.backgroundColor = '#1e1e1e';
        });
    }
}

/**
 * Erzwingt das dunkle Design unabhängig von den Benutzereinstellungen
 */
function forceDarkMode() {
    localStorage.setItem('dark_mode', 'enabled');
    applyDarkTheme();
}

/**
 * Wendet das dunkle Design an
 */
function applyDarkTheme() {
    // Dark mode CSS hinzufügen oder aktivieren
    const darkThemeLink = document.getElementById('dark-theme-css');
    
    // Wenn das Link-Element noch nicht existiert, erstellen
    if (!darkThemeLink) {
        const head = document.head;
        const link = document.createElement('link');
        link.id = 'dark-theme-css';
        link.rel = 'stylesheet';
        link.href = 'css/dark-theme.css';
        head.appendChild(link);
    } else {
        // Falls es deaktiviert war, aktivieren
        darkThemeLink.disabled = false;
    }
    
    // Meta-Theme-Color für mobile Geräte aktualisieren
    updateMetaThemeColor('#121212'); // Dunkle Farbe
    
    // Dark Mode in localStorage speichern
    localStorage.setItem('dark_mode', 'enabled');
    
    // Body-Klasse hinzufügen für zusätzliches Styling
    document.body.classList.add('dark-mode');
    document.documentElement.classList.add('dark-mode');
    
    // Sicherstellen, dass Farben explizit gesetzt sind (für Safari)
    document.body.style.backgroundColor = '#121212';
    document.documentElement.style.backgroundColor = '#121212';
    
    // Mobil-spezifische Anpassungen
    if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
        applyMobileDarkMode();
    }
}

/**
 * Entfernt das dunkle Design
 * (Wird aktuell nicht verwendet, da wir das dunkle Design erzwingen)
 */
function removeDarkTheme() {
    // Wir deaktivieren diese Funktion, um das dunkle Design zu erzwingen
    // Stattdessen setzen wir das dunkle Design wieder
    forceDarkMode();
    return;
    
    /* 
    // Code zum Entfernen des dunklen Designs (auskommentiert)
    const darkThemeLink = document.getElementById('dark-theme-css');
    if (darkThemeLink) {
        darkThemeLink.disabled = true;
    }
    
    // Meta-Theme-Color für mobile Geräte zurücksetzen
    updateMetaThemeColor('#ffffff'); // Helle Farbe
    
    // Dark Mode in localStorage entfernen
    localStorage.removeItem('dark_mode');
    
    // Body-Klasse entfernen
    document.body.classList.remove('dark-mode');
    */
}

/**
 * Aktualisiert die Farbe für die Statusleiste auf mobilen Geräten
 */
function updateMetaThemeColor(color) {
    let metaThemeColor = document.querySelector('meta[name="theme-color"]');
    
    if (!metaThemeColor) {
        metaThemeColor = document.createElement('meta');
        metaThemeColor.name = 'theme-color';
        document.head.appendChild(metaThemeColor);
    }
    
    metaThemeColor.content = color;
}

/**
 * Spezielles Styling für mobile Geräte im Dark Mode
 */
function applyMobileDarkMode() {
    // Statusleistenfarbe für iOS (Safari)
    const metaAppleStatusBar = document.querySelector('meta[name="apple-mobile-web-app-status-bar-style"]');
    if (!metaAppleStatusBar) {
        const meta = document.createElement('meta');
        meta.name = 'apple-mobile-web-app-status-bar-style';
        meta.content = 'black-translucent';
        document.head.appendChild(meta);
    } else {
        metaAppleStatusBar.content = 'black-translucent';
    }
    
    // App-fähig machen
    const metaAppleWebApp = document.querySelector('meta[name="apple-mobile-web-app-capable"]');
    if (!metaAppleWebApp) {
        const meta = document.createElement('meta');
        meta.name = 'apple-mobile-web-app-capable';
        meta.content = 'yes';
        document.head.appendChild(meta);
    }
    
    // CSS-Anpassungen für mobile Geräte
    const style = document.createElement('style');
    style.textContent = `
        /* Mobile Dark Mode Anpassungen */
        @media (max-width: 768px) {
            body {
                /* Verhindert Übersprung auf iOS beim Scrollen */
                -webkit-overflow-scrolling: touch;
                overscroll-behavior-y: none;
            }
            
            /* Größere Touch-Flächen für Buttons */
            .btn {
                padding: 0.5rem 1rem;
                min-height: 44px;
            }
            
            /* Bessere Lesbarkeit im Dunkeln */
            p, li, td, th {
                font-size: 1.05rem;
                line-height: 1.6;
            }
            
            /* Verbesserte Kontraste für kleine Bildschirme */
            .card {
                box-shadow: 0 6px 12px rgba(0,0,0,0.3);
            }
            
            /* Fixierte Navigation am unteren Bildschirmrand für bessere Bedienung */
            .mobile-nav-bottom {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background-color: #1e1e1e !important; /* Explizit für Safari */
                border-top: 1px solid rgba(255, 255, 255, 0.12);
                padding: 0.5rem;
                display: flex;
                justify-content: space-around;
                z-index: 1000;
            }
        }
    `;
    document.head.appendChild(style);
}