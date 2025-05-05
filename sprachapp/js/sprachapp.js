/**
 * SprachApp - Hauptfunktionalität
 * Optimierte Version für alle Geräte und Browser
 */

document.addEventListener('DOMContentLoaded', function() {
    // Globale Initialisierung
    initAudioFunctions();
    initFlashcardFunctions();
    initSearchFunctions();
    initMultiTestFunctions();
    enforceDarkMode();
  });
  
  /**
   * Dark Mode auf allen Seiten erzwingen
   */
  function enforceDarkMode() {
    // Meta-Tags für Dark Mode setzen
    let metaColor = document.querySelector('meta[name="theme-color"]');
    if (!metaColor) {
      metaColor = document.createElement('meta');
      metaColor.setAttribute('name', 'theme-color');
      document.head.appendChild(metaColor);
    }
    metaColor.setAttribute('content', '#121212');
    
    let metaColorScheme = document.querySelector('meta[name="color-scheme"]');
    if (!metaColorScheme) {
      metaColorScheme = document.createElement('meta');
      metaColorScheme.setAttribute('name', 'color-scheme');
      document.head.appendChild(metaColorScheme);
    }
    metaColorScheme.setAttribute('content', 'dark');
    
    // Force Dark Mode auf HTML Element
    document.documentElement.style.backgroundColor = "#121212";
    document.documentElement.style.colorScheme = "dark";
    document.documentElement.classList.add('dark-mode');
    document.body.classList.add('dark-mode');
    
    // Fixes für Safari und Firefox
    if (/^((?!chrome|android).)*safari/i.test(navigator.userAgent) || 
        navigator.userAgent.indexOf("Firefox") > -1) {
      document.body.style.backgroundColor = "#121212";
      document.documentElement.style.backgroundColor = "#121212";
      
      // Dark Theme für alle modalen Dialoge und Cards erzwingen
      document.querySelectorAll('.card, .modal-content').forEach(el => {
        el.style.backgroundColor = "#1e1e1e";
      });
    }
    
    // Dunkles Design für alle Cards, Formulare und Tabellen erzwingen
    document.querySelectorAll('.card-footer.bg-white').forEach(footer => {
      footer.classList.remove('bg-white');
      footer.style.backgroundColor = 'var(--dark-surface-light)';
    });
    
    document.querySelectorAll('.card-header.bg-light').forEach(header => {
      header.classList.remove('bg-light');
      header.classList.add('bg-dark');
      header.style.backgroundColor = 'var(--dark-surface-light)';
      header.style.color = 'var(--dark-text-primary)';
    });
    
    // Formular-Eingabefelder immer dunkel halten
    document.querySelectorAll('.form-control').forEach(input => {
      input.style.backgroundColor = 'var(--dark-surface-light)';
      input.style.color = 'var(--dark-text-primary)';
      input.style.borderColor = 'var(--dark-border)';
    });
  }
  
  /**
   * Audio-Funktionen initialisieren
   */
  function initAudioFunctions() {
    // Audio-Funktion für alle Seiten bereitstellen
    window.playAudio = function(text, lang) {
      if (!text || !lang) return;
      
      // Beschränke Text auf API-Limit
      const limitedText = text.length > 100 ? text.substring(0, 100) : text;
      const encodedText = encodeURIComponent(limitedText);
      
      // Audio-URL für Google Translate TTS
      const audioUrl = `https://translate.google.com/translate_tts?ie=UTF-8&q=${encodedText}&tl=${lang}&client=tw-ob`;
      
      // Audio Element erstellen und abspielen
      const audio = new Audio(audioUrl);
      
      // Visuelles Feedback für Buttons
      const buttons = document.querySelectorAll(`button[data-text="${text}"][data-lang="${lang}"], button.btn-audio:has(i.fa-volume-up)`);
      
      buttons.forEach(button => {
        // Ursprüngliches Button-HTML speichern
        const originalHTML = button.innerHTML;
        
        // Lade-Animation anzeigen
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;
        
        // Button zurücksetzen
        const resetButton = () => {
          button.innerHTML = originalHTML;
          button.disabled = false;
        };
        
        audio.onended = resetButton;
        audio.onerror = resetButton;
      });
      
      // Audio abspielen und Fehler abfangen
      audio.play().catch(error => {
        console.error('Fehler beim Abspielen:', error);
        
        // Buttons zurücksetzen
        buttons.forEach(button => {
          button.innerHTML = button.innerHTML.replace('<i class="fas fa-spinner fa-spin"></i>', '<i class="fas fa-volume-up"></i>');
          button.disabled = false;
        });
        
        // Fehlermeldung anzeigen
        showNotification('Die Aussprache konnte nicht abgespielt werden.', 'warning');
      });
    };
    
    // Audio-Buttons zu Vokabeltabellen hinzufügen
    const vocabTables = document.querySelectorAll('.vocab-table, .table-striped');
    vocabTables.forEach(table => {
      addPronunciationButtonsToTable(table);
    });
  }
  
  /**
   * Hilfsfunktion: Hinzufügen von Audio-Buttons zu Tabellen
   */
  function addPronunciationButtonsToTable(table) {
    if (!table) return;
    
    // Spaltenindizes für deutsche und englische Wörter
    let germanColIndex = 1;
    let englishColIndex = 2;
    
    // Prüfen, ob Tabelle spezielle Spaltenstruktur hat
    const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim().toLowerCase());
    const germanIndex = headers.findIndex(h => h.includes('deutsch'));
    const englishIndex = headers.findIndex(h => h.includes('englisch'));
    
    if (germanIndex !== -1 && englishIndex !== -1) {
      germanColIndex = germanIndex;
      englishColIndex = englishIndex;
    }
    
    // Zeilen durchgehen
    table.querySelectorAll('tbody tr').forEach(row => {
      const cells = row.querySelectorAll('td');
      
      if (cells.length <= Math.max(germanColIndex, englishColIndex)) return;
      
      const germanCell = cells[germanColIndex];
      const englishCell = cells[englishColIndex];
      
      // Aktionszelle finden oder erstellen
      let actionCell = cells[cells.length - 1];
      
      if ((actionCell === germanCell || actionCell === englishCell) || 
          (!actionCell.querySelector('button') && !actionCell.classList.contains('actions'))) {
        actionCell = row.insertCell(-1);
        actionCell.className = 'actions';
      }
      
      // Wörter aus Zellen extrahieren
      const germanWord = germanCell ? germanCell.textContent.trim() : '';
      const englishWord = englishCell ? englishCell.textContent.trim() : '';
      
      // Buttons erstellen, wenn sie noch nicht existieren
      if (germanWord && !actionCell.querySelector(`.btn-audio[data-text="${germanWord}"][data-lang="de"]`)) {
        const germanButton = document.createElement('button');
        germanButton.className = 'btn btn-sm btn-info btn-audio me-1';
        germanButton.setAttribute('data-text', germanWord);
        germanButton.setAttribute('data-lang', 'de');
        germanButton.setAttribute('type', 'button');
        germanButton.innerHTML = '<i class="fas fa-volume-up"></i> DE';
        germanButton.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          playAudio(germanWord, 'de');
        });
        
        actionCell.appendChild(germanButton);
      }
      
      if (englishWord && !actionCell.querySelector(`.btn-audio[data-text="${englishWord}"][data-lang="en"]`)) {
        const englishButton = document.createElement('button');
        englishButton.className = 'btn btn-sm btn-info btn-audio';
        englishButton.setAttribute('data-text', englishWord);
        englishButton.setAttribute('data-lang', 'en');
        englishButton.setAttribute('type', 'button');
        englishButton.innerHTML = '<i class="fas fa-volume-up"></i> EN';
        englishButton.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          playAudio(englishWord, 'en');
        });
        
        if (germanWord) {
          actionCell.appendChild(document.createTextNode(' '));
        }
        actionCell.appendChild(englishButton);
      }
    });
  }
  
  /**
   * Benachrichtigung anzeigen
   */
  function showNotification(message, type = 'info', duration = 5000) {
    // Container für Benachrichtigungen erstellen/finden
    let container = document.getElementById('notification-container');
    
    if (!container) {
      container = document.createElement('div');
      container.id = 'notification-container';
      container.style.position = 'fixed';
      container.style.top = '20px';
      container.style.right = '20px';
      container.style.zIndex = '9999';
      document.body.appendChild(container);
    }
    
    // Benachrichtigung erstellen
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show`;
    notification.innerHTML = `
      ${message}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Hinzufügen und automatisch ausblenden
    container.appendChild(notification);
    
    setTimeout(() => {
      notification.classList.remove('show');
      setTimeout(() => notification.remove(), 300);
    }, duration);
  }
  
  /**
   * Karteikarten-Funktionen
   */
  function initFlashcardFunctions() {
    const flashcard = document.querySelector('.flashcard');
    if (!flashcard) return;
    
    const answerForm = document.getElementById('answer-form');
    const userAnswerInput = document.getElementById('user_answer');
    const showAnswerBtn = document.getElementById('show-answer');
    const feedback = document.getElementById('feedback');
    const nextCardBtn = document.getElementById('next-card');
    
    // Karteikarte umdrehen bei Klick
    flashcard.addEventListener('click', function(e) {
      // Nicht bei Klick auf Buttons
      if (!e.target.closest('button')) {
        flashcard.classList.toggle('flipped');
      }
    });
    
    // Antwort anzeigen mit Button
    if (showAnswerBtn) {
      showAnswerBtn.addEventListener('click', function() {
        flashcard.classList.add('flipped');
      });
    }
    
    // Antwort prüfen
    if (answerForm && userAnswerInput) {
      answerForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const vocabId = document.getElementById('vocab_id').value;
        const userAnswer = userAnswerInput.value.trim();
        
        if (!userAnswer) return;
        
        // AJAX-Anfrage zum Überprüfen der Antwort
        const xhr = new XMLHttpRequest();
        xhr.open('POST', window.location.href, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onload = function() {
          if (xhr.status === 200) {
            try {
              const data = JSON.parse(xhr.responseText);
              
              // Feedback anzeigen
              if (feedback) {
                feedback.style.display = 'block';
                feedback.className = data.correct ? 'feedback alert alert-success' : 'feedback alert alert-danger';
                
                const feedbackMessage = document.getElementById('feedback-message');
                if (feedbackMessage) {
                  feedbackMessage.innerHTML = data.message;
                }
              }
              
              // Karteikarte umdrehen
              flashcard.classList.add('flipped');
              
              // Formular deaktivieren
              userAnswerInput.disabled = true;
              userAnswerInput.style.backgroundColor = 'var(--dark-surface)';
              userAnswerInput.style.color = 'var(--dark-text-secondary)';
              
              // Submit-Button und Show-Answer-Button deaktivieren
              const submitBtn = answerForm.querySelector('button[type="submit"]');
              if (submitBtn) submitBtn.disabled = true;
              if (showAnswerBtn) showAnswerBtn.disabled = true;
              
            } catch (e) {
              console.error('JSON-Fehler:', e);
              showNotification('Fehler bei der Antwortverarbeitung', 'danger');
            }
          }
        };
        
        xhr.send('check=1&vocab_id=' + encodeURIComponent(vocabId) + '&answer=' + encodeURIComponent(userAnswer));
      });
      
      // Tastatursteuerung
      userAnswerInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
          // Enter = Prüfen
          e.preventDefault();
          answerForm.querySelector('button[type="submit"]').click();
        } else if (e.key === 'Escape') {
          // Esc = Antwort zeigen
          if (showAnswerBtn) showAnswerBtn.click();
        }
      });
    }
    
    // Nächste Karte
    if (nextCardBtn) {
      nextCardBtn.addEventListener('click', function() {
        window.location.reload();
      });
    }
    
    // Fokus auf Eingabefeld setzen
    if (userAnswerInput) {
      userAnswerInput.focus();
    }
  }
  
  /**
   * Suchfunktionen für Listen
   */
  function initSearchFunctions() {
    // Vokabelsuche in der Unit-Details-Ansicht
    const searchInput = document.getElementById('vocab-search');
    if (searchInput) {
      const vocabList = document.getElementById('vocab-list');
      const vocabRows = vocabList ? Array.from(vocabList.getElementsByClassName('vocab-row')) : [];
      
      searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        let visibleCount = 0;
        
        vocabRows.forEach(function(row) {
          const germanWord = row.querySelector('.german-word').textContent.toLowerCase();
          const englishWord = row.querySelector('.english-word').textContent.toLowerCase();
          
          if (germanWord.includes(searchTerm) || englishWord.includes(searchTerm)) {
            row.style.display = '';
            visibleCount++;
          } else {
            row.style.display = 'none';
          }
        });
        
        // Meldung wenn keine Ergebnisse
        let noResultsElement = document.getElementById('no-results-message');
        if (visibleCount === 0 && searchTerm !== '') {
          if (!noResultsElement) {
            noResultsElement = document.createElement('tr');
            noResultsElement.id = 'no-results-message';
            noResultsElement.innerHTML = '<td colspan="4" class="text-center">Keine Vokabeln gefunden, die zu deiner Suche passen.</td>';
            vocabList.appendChild(noResultsElement);
          }
        } else if (noResultsElement) {
          noResultsElement.remove();
        }
      });
    }
    
    // Einheitensuche in der Browse-Units-Ansicht
    const unitSearchInput = document.getElementById('unit-search');
    if (unitSearchInput) {
      const unitsContainer = document.getElementById('units-container');
      const unitItems = unitsContainer ? Array.from(unitsContainer.getElementsByClassName('unit-item')) : [];
      
      unitSearchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        
        unitItems.forEach(function(item) {
          const title = item.querySelector('.card-header h5').textContent.toLowerCase();
          const description = item.querySelector('.card-text').textContent.toLowerCase();
          
          item.style.display = (title.includes(searchTerm) || description.includes(searchTerm)) ? '' : 'none';
        });
      });
    }
  }
  
  /**
   * Multi-Test Funktionen
   */
  function initMultiTestFunctions() {
    const multiTestToggle = document.getElementById('multi-test-toggle');
    if (!multiTestToggle) return;
    
    const multiTestBar = document.getElementById('multi-test-bar');
    const startMultiTestBtn = document.getElementById('start-multi-test');
    const testDirectionSelect = document.getElementById('test-direction');
    const selectButtons = document.querySelectorAll('.select-for-test');
    const unitItems = document.querySelectorAll('.unit-item');
    const selectedUnitsContainer = document.getElementById('selected-units-container');
    const noUnitsSelected = document.getElementById('no-units-selected');
    
    // Ausgewählte Units speichern
    const selectedUnits = new Map();
    
    // Multi-Test Modus umschalten
    multiTestToggle.addEventListener('click', function() {
      const isActive = this.classList.toggle('active');
      
      if (isActive) {
        // Zeige Multi-Test-Leiste und Auswahlbuttons
        multiTestBar.style.display = 'block';
        selectButtons.forEach(btn => {
          btn.style.display = 'flex';
        });
      } else {
        // Verstecke Multi-Test-Leiste und Auswahlbuttons
        multiTestBar.style.display = 'none';
        selectButtons.forEach(btn => {
          btn.style.display = 'none';
        });
        
        // Zurücksetzen der Auswahl
        selectedUnits.clear();
        updateSelectedUnitsDisplay();
        selectButtons.forEach(btn => {
          btn.classList.remove('selected');
          btn.innerHTML = '<i class="fas fa-plus"></i>';
        });
        unitItems.forEach(item => {
          item.classList.remove('selected-for-test');
        });
      }
    });
    
    // Unit für Test auswählen/abwählen
    selectButtons.forEach(btn => {
      const unitId = btn.dataset.unitId;
      const unitItem = document.querySelector(`.unit-item[data-unit-id="${unitId}"]`);
      
      if (unitItem) {
        const unitName = unitItem.dataset.unitName;
        
        btn.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          
          if (selectedUnits.has(unitId)) {
            // Unit abwählen
            selectedUnits.delete(unitId);
            this.classList.remove('selected');
            this.innerHTML = '<i class="fas fa-plus"></i>';
            unitItem.classList.remove('selected-for-test');
          } else {
            // Unit auswählen
            selectedUnits.set(unitId, unitName);
            this.classList.add('selected');
            this.innerHTML = '<i class="fas fa-check"></i>';
            unitItem.classList.add('selected-for-test');
          }
          
          // Anzeige aktualisieren
          updateSelectedUnitsDisplay();
        });
      }
    });
    
    // Ausgewählte Units anzeigen
    function updateSelectedUnitsDisplay() {
      // Start-Button aktivieren/deaktivieren
      if (startMultiTestBtn) {
        startMultiTestBtn.disabled = selectedUnits.size === 0;
      }
      
      // "Keine Units ausgewählt" Meldung anzeigen/ausblenden
      if (selectedUnits.size === 0) {
        if (noUnitsSelected) {
          noUnitsSelected.style.display = 'block';
        }
        if (selectedUnitsContainer) {
          selectedUnitsContainer.querySelectorAll('.multi-test-badge').forEach(badge => {
            badge.remove();
          });
        }
        return;
      }
      
      // "Keine Units ausgewählt" Meldung ausblenden
      if (noUnitsSelected) {
        noUnitsSelected.style.display = 'none';
      }
      
      // Badges aktualisieren
      if (selectedUnitsContainer) {
        // Alle bestehenden Badges entfernen
        selectedUnitsContainer.querySelectorAll('.multi-test-badge').forEach(badge => {
          badge.remove();
        });
        
        // Neue Badges für jede ausgewählte Unit erstellen
        selectedUnits.forEach((unitName, unitId) => {
          const badge = document.createElement('span');
          badge.className = 'multi-test-badge';
          badge.dataset.unitId = unitId;
          badge.innerHTML = `${unitName} <span class="remove-unit" data-unit-id="${unitId}"><i class="fas fa-times"></i></span>`;
          selectedUnitsContainer.appendChild(badge);
          
          // Event-Listener für das Entfernen
          badge.querySelector('.remove-unit').addEventListener('click', function() {
            const unitId = this.dataset.unitId;
            selectedUnits.delete(unitId);
            
            // Zugehörigen Auswahlbutton zurücksetzen
            const selectBtn = document.querySelector(`.select-for-test[data-unit-id="${unitId}"]`);
            if (selectBtn) {
              selectBtn.classList.remove('selected');
              selectBtn.innerHTML = '<i class="fas fa-plus"></i>';
            }
            
            const unitItem = document.querySelector(`.unit-item[data-unit-id="${unitId}"]`);
            if (unitItem) {
              unitItem.classList.remove('selected-for-test');
            }
            
            updateSelectedUnitsDisplay();
          });
        });
      }
    }
    
    // Multi-Test starten
    if (startMultiTestBtn) {
      startMultiTestBtn.addEventListener('click', function() {
        if (selectedUnits.size === 0) return;
        
        // URL mit ausgewählten Unit-IDs und Richtung erstellen
        const unitIds = Array.from(selectedUnits.keys());
        const direction = testDirectionSelect ? testDirectionSelect.value : 'de_en';
        
        let url = 'mini_test.php?';
        unitIds.forEach(id => {
          url += `unit_id[]=${id}&`;
        });
        url += `direction=${direction}`;
        
        // Zu Mini-Test-Seite navigieren
        window.location.href = url;
      });
    }
  }