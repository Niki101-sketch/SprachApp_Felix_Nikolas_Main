/**
 * Force Dark Mode sur tous les navigateurs et appareils
 * Ce script s'exécute avant le chargement du DOM pour éviter le flash blanc
 */

// Script à exécuter immédiatement avant le chargement du reste de la page
(function() {
    // Fonction pour forcer le mode sombre
    function forceDarkMode() {
      // 1. CSS Variables via HTML
      document.documentElement.style.setProperty('--dark-bg', '#121212');
      document.documentElement.style.setProperty('--dark-surface', '#1e1e1e');
      document.documentElement.style.setProperty('--dark-surface-light', '#2d2d2d');
      document.documentElement.style.setProperty('--dark-surface-lighter', '#363636');
      document.documentElement.style.setProperty('--dark-text-primary', 'rgba(255, 255, 255, 0.87)');
      document.documentElement.style.setProperty('--dark-text-secondary', 'rgba(255, 255, 255, 0.6)');
      document.documentElement.style.setProperty('--dark-text-hint', 'rgba(255, 255, 255, 0.38)');
      document.documentElement.style.setProperty('--dark-border', 'rgba(255, 255, 255, 0.12)');
      
      // 2. Couleur de fond forcée sur HTML et Body
      document.documentElement.style.backgroundColor = '#121212';
      document.documentElement.style.color = 'rgba(255, 255, 255, 0.87)';
      document.documentElement.style.colorScheme = 'dark';
      
      // 3. Ajouter la classe dark-mode
      document.documentElement.classList.add('dark-mode');
      
      // 4. Meta tags pour le mode sombre
      let metaThemeColor = document.querySelector('meta[name="theme-color"]');
      if (!metaThemeColor) {
        metaThemeColor = document.createElement('meta');
        metaThemeColor.setAttribute('name', 'theme-color');
        document.head.appendChild(metaThemeColor);
      }
      metaThemeColor.setAttribute('content', '#121212');
      
      let metaColorScheme = document.querySelector('meta[name="color-scheme"]');
      if (!metaColorScheme) {
        metaColorScheme = document.createElement('meta');
        metaColorScheme.setAttribute('name', 'color-scheme');
        document.head.appendChild(metaColorScheme);
      }
      metaColorScheme.setAttribute('content', 'dark');
      
      // 5. Ajouter une feuille de style d'urgence injectée directement
      const emergencyStyle = document.createElement('style');
      emergencyStyle.id = 'emergency-dark-mode';
      emergencyStyle.textContent = `
        html, body { 
          background-color: #121212 !important; 
          color: rgba(255, 255, 255, 0.87) !important;
          color-scheme: dark !important;
        }
        
        .card, .card-body, .modal-content {
          background-color: #1e1e1e !important;
        }
        
        .card-header, .card-footer, .modal-header, .modal-footer {
          background-color: #2d2d2d !important;
          border-color: rgba(255, 255, 255, 0.12) !important;
        }
        
        .form-control {
          background-color: #2d2d2d !important;
          color: rgba(255, 255, 255, 0.87) !important;
          border-color: rgba(255, 255, 255, 0.12) !important;
        }
        
        .table, .table th, .table td {
          color: rgba(255, 255, 255, 0.87) !important;
        }
        
        /* Korrektur für Safari und Firefox */
        @supports (-webkit-touch-callout: none) {
          html, body { 
            background-color: #121212 !important; 
          }
        }
        
        @-moz-document url-prefix() {
          html, body { 
            background-color: #121212 !important; 
          }
        }
      `;
      document.head.appendChild(emergencyStyle);
    }
    
    // Appliquer immédiatement
    forceDarkMode();
    
    // Réappliquer après chargement du DOM pour Firefox et Safari
    document.addEventListener('DOMContentLoaded', function() {
      forceDarkMode();
      
      // Appliquer le mode sombre au body aussi
      document.body.style.backgroundColor = '#121212';
      document.body.style.color = 'rgba(255, 255, 255, 0.87)';
      document.body.classList.add('dark-mode');
      
      // Correction spécifique pour Safari mobile
      if (/iPhone|iPad|iPod/i.test(navigator.userAgent)) {
        document.querySelectorAll('.card, .modal-content, .navbar, .list-group-item').forEach(el => {
          el.style.backgroundColor = '#1e1e1e';
        });
        
        document.querySelectorAll('.card-header, .card-footer, .modal-header, .modal-footer').forEach(el => {
          el.style.backgroundColor = '#2d2d2d';
        });
      }
      
      // Correction spécifique pour Firefox
      if (navigator.userAgent.indexOf("Firefox") > -1) {
        document.querySelectorAll('body *').forEach(el => {
          const currentBg = window.getComputedStyle(el).backgroundColor;
          if (currentBg === 'rgb(255, 255, 255)' || currentBg === '#ffffff') {
            el.style.backgroundColor = '#1e1e1e';
          }
        });
      }
    });
    
    // Observer le body pour les changements dynamiques
    const observeBody = function() {
      if (!document.body) return;
      
      const observer = new MutationObserver(function(mutations) {
        document.body.style.backgroundColor = '#121212';
        document.body.style.color = 'rgba(255, 255, 255, 0.87)';
        document.body.classList.add('dark-mode');
      });
      
      observer.observe(document.body, { 
        attributes: true, 
        attributeFilter: ['style', 'class'] 
      });
    };
    
    if (document.body) {
      observeBody();
    } else {
      document.addEventListener('DOMContentLoaded', observeBody);
    }
  })();