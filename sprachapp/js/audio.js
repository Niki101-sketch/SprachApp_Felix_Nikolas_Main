/**
 * SprachApp - Optimierte Audio-Funktionalität
 * Unterstützt mehrere Fallback-Methoden für verschiedene Browser und Geräte
 */

document.addEventListener('DOMContentLoaded', function() {
    // Globale Variablen
    let audioContext;
    let isMobileDevice = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    const audioCache = {};
    
    // AudioContext initialisieren wenn möglich
    try {
      window.AudioContext = window.AudioContext || window.webkitAudioContext;
      audioContext = new AudioContext();
      
      // Auf mobilen Geräten AudioContext erst bei Benutzerinteraktion starten
      if (isMobileDevice && audioContext.state === 'suspended') {
        const resumeAudio = function() {
          audioContext.resume();
          document.removeEventListener('touchstart', resumeAudio);
          document.removeEventListener('click', resumeAudio);
        };
        document.addEventListener('touchstart', resumeAudio);
        document.addEventListener('click', resumeAudio);
      }
    } catch (e) {
      console.warn('WebAudio API nicht unterstützt, nutze HTML5 Audio');
    }
    
    // Audio für mobile Geräte vorbereiten
    if (isMobileDevice) {
      prepareAudioForMobile();
    }
    
    // Bestehende Audio-Buttons initialisieren
    document.querySelectorAll('[onclick*="playAudio"]').forEach(button => {
      const onclickText = button.getAttribute('onclick');
      const match = onclickText.match(/playAudio\(['"]([^'"]+)['"]\s*,\s*['"]([^'"]+)['"]\)/);
      
      if (match && match.length >= 3) {
        const text = match[1];
        const lang = match[2];
        
        button.removeAttribute('onclick');
        button.setAttribute('data-text', text);
        button.setAttribute('data-lang', lang);
        button.setAttribute('type', 'button');
        
        // Event-Listener für Touch und Klick
        if (isMobileDevice) {
          button.addEventListener('touchstart', function(e) {
            e.preventDefault();
            playAudio(text, lang, button);
          }, { passive: false });
        }
        
        button.addEventListener('click', function(e) {
          e.preventDefault();
          playAudio(text, lang, button);
        });
        
        if (!button.classList.contains('btn-audio')) {
          button.classList.add('btn-audio');
        }
        
        if (isMobileDevice) {
          button.classList.add('btn-audio-mobile');
        }
      }
    });
    
    // Audio-Buttons zu Vokabeltabellen hinzufügen
    document.querySelectorAll('.vocab-table, .table-striped, table:not(.table-borderless)').forEach(table => {
      addPronunciationButtonsToTable(table);
    });
    
    /**
     * Audio abspielen mit Fallback-Strategien
     */
    function playAudio(text, lang, buttonElement) {
      if (!text || !lang) return;
      
      // Audio-Kontext aktivieren wenn suspendiert
      if (audioContext && audioContext.state === 'suspended') {
        audioContext.resume();
      }
      
      // Text auf API-Limit beschränken
      const limitedText = text.length > 100 ? text.substring(0, 100) : text;
      const encodedText = encodeURIComponent(limitedText);
      
      // Button-Feedback
      let originalButtonHTML = '';
      if (buttonElement) {
        originalButtonHTML = buttonElement.innerHTML;
        buttonElement.innerHTML = '<span class="loading-indicator"></span>';
        buttonElement.disabled = true;
        buttonElement.classList.add('btn-audio-playing');
      }
      
      // Aus Cache abspielen wenn verfügbar
      if (audioCache[`${text}_${lang}`]) {
        const audioUrl = audioCache[`${text}_${lang}`];
        const audio = new Audio(audioUrl);
        
        audio.onended = function() {
          resetButton();
        };
        
        audio.onerror = function() {
          delete audioCache[`${text}_${lang}`];
          playAudioWithFallback();
        };
        
        audio.play().catch(() => playAudioWithFallback());
      } else {
        playAudioWithFallback();
      }
      
      // Fallback-Strategien für Audio-Wiedergabe
      function playAudioWithFallback() {
        // Mobile-optimierte Strategie
        if (isMobileDevice) {
          tryBrowserSpeechAPI(text, lang)
            .then(() => resetButton('success'))
            .catch(() => {
              return tryGoogleTTS(encodedText, lang);
            })
            .then(audioBlob => {
              if (audioBlob) {
                audioCache[`${text}_${lang}`] = audioBlob;
              }
              resetButton('success');
            })
            .catch(() => {
              showAudioError();
              resetButton('error');
            });
        } else {
          // Desktop-Strategie
          tryGoogleTTS(encodedText, lang)
            .then(audioBlob => {
              if (audioBlob) {
                audioCache[`${text}_${lang}`] = audioBlob;
              }
              resetButton('success');
            })
            .catch(() => {
              return tryBrowserSpeechAPI(text, lang);
            })
            .then(() => {
              resetButton('success');
            })
            .catch(() => {
              showAudioError();
              resetButton('error');
            });
        }
      }
      
      // Button zurücksetzen
      function resetButton(status) {
        if (buttonElement) {
          buttonElement.classList.remove('btn-audio-playing');
          
          if (status === 'error') {
            buttonElement.classList.add('btn-audio-error');
            setTimeout(() => {
              buttonElement.classList.remove('btn-audio-error');
              buttonElement.innerHTML = originalButtonHTML;
              buttonElement.disabled = false;
            }, 1000);
          } else {
            buttonElement.innerHTML = originalButtonHTML;
            buttonElement.disabled = false;
          }
        }
      }
      
      // Fehlermeldung anzeigen
      function showAudioError() {
        if (!navigator.onLine) {
          showOfflineNotice(true);
          return;
        }
        
        const errorMessage = document.createElement('div');
        errorMessage.className = 'alert alert-warning alert-dismissible fade show mt-2';
        errorMessage.innerHTML = `
          <strong>Audio-Problem:</strong> Die Aussprache konnte nicht abgespielt werden.
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        if (buttonElement) {
          const container = buttonElement.closest('.card') || buttonElement.closest('.container');
          if (container) {
            container.prepend(errorMessage);
          } else {
            document.querySelector('.container')?.prepend(errorMessage);
          }
        } else {
          document.querySelector('.container')?.prepend(errorMessage);
        }
        
        setTimeout(() => {
          errorMessage.remove();
        }, 5000);
      }
    }
    
    /**
     * Google TTS verwenden
     */
    function tryGoogleTTS(encodedText, lang) {
      return new Promise((resolve, reject) => {
        const audioUrl = `https://translate.google.com/translate_tts?ie=UTF-8&q=${encodedText}&tl=${lang}&client=tw-ob`;
        
        fetch(audioUrl)
          .then(response => {
            if (!response.ok) {
              throw new Error(`HTTP-Fehler: ${response.status}`);
            }
            return response.blob();
          })
          .then(audioBlob => {
            const blobUrl = URL.createObjectURL(audioBlob);
            const audio = new Audio(blobUrl);
            
            // Mobile-spezifische Attribute
            if (isMobileDevice) {
              audio.preload = 'auto';
              audio.playsinline = true;
            }
            
            audio.onended = function() {
              URL.revokeObjectURL(blobUrl);
              resolve(blobUrl);
            };
            
            audio.onerror = function() {
              URL.revokeObjectURL(blobUrl);
              reject();
            };
            
            audio.play().catch(reject);
          })
          .catch(error => {
            // Fallback bei CORS-Fehlern
            const audio = new Audio(audioUrl);
            
            audio.onended = function() {
              resolve(null);
            };
            
            audio.onerror = function() {
              reject();
            };
            
            audio.play().catch(reject);
          });
      });
    }
    
    /**
     * Browser Speech API verwenden
     */
    function tryBrowserSpeechAPI(text, lang) {
      return new Promise((resolve, reject) => {
        if (!('speechSynthesis' in window)) {
          return reject('Speech API nicht unterstützt');
        }
        
        const speechLang = lang === 'de' ? 'de-DE' : 'en-US';
        
        // Timeout für iOS-Probleme
        let speechTimeoutId = setTimeout(() => {
          window.speechSynthesis.cancel();
          reject('Speech Synthesis Timeout');
        }, 5000);
        
        // Stimmen abrufen
        const voices = window.speechSynthesis.getVoices();
        let voice = voices.find(v => v.lang === speechLang) || 
                   voices.find(v => v.lang.startsWith(speechLang.split('-')[0]));
        
        // Utterance erstellen
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = speechLang;
        
        if (voice) {
          utterance.voice = voice;
        }
        
        utterance.onend = function() {
          clearTimeout(speechTimeoutId);
          resolve();
        };
        
        utterance.onerror = function() {
          clearTimeout(speechTimeoutId);
          reject();
        };
        
        // Korrektur für iOS-Bug
        if (/iPhone|iPad|iPod/i.test(navigator.userAgent)) {
          const iosWorkaround = setInterval(() => {
            if (window.speechSynthesis.speaking) {
              window.speechSynthesis.pause();
              window.speechSynthesis.resume();
            } else {
              clearInterval(iosWorkaround);
            }
          }, 250);
        }
        
        window.speechSynthesis.speak(utterance);
      });
    }
    
    /**
     * Audio-Buttons zu Tabelle hinzufügen
     */
    function addPronunciationButtonsToTable(table) {
      if (!table) return;
      
      // Spaltenindizes ermitteln
      let germanColIndex = 1;
      let englishColIndex = 2;
      
      // Zeilen durchgehen
      table.querySelectorAll('tbody tr').forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length <= Math.max(germanColIndex, englishColIndex)) return;
        
        const germanCell = cells[germanColIndex];
        const englishCell = cells[englishColIndex];
        
        // Aktionszelle finden oder erstellen
        let actionCell = cells[cells.length - 1];
        if ((actionCell === germanCell || actionCell === englishCell) || 
            (!actionCell.querySelector('button'))) {
          actionCell = row.insertCell(-1);
          actionCell.className = 'actions text-center';
        }
        
        // Wörter extrahieren
        const germanWord = germanCell?.textContent.trim() || '';
        const englishWord = englishCell?.textContent.trim() || '';
        
        if (!germanWord && !englishWord) return;
        
        // Buttons erstellen wenn nicht vorhanden
        if (germanWord && !actionCell.querySelector(`[data-text="${germanWord}"][data-lang="de"]`)) {
          const germanButton = document.createElement('button');
          germanButton.className = 'btn btn-sm btn-info btn-audio me-1';
          germanButton.setAttribute('data-text', germanWord);
          germanButton.setAttribute('data-lang', 'de');
          germanButton.setAttribute('type', 'button');
          germanButton.innerHTML = '<i class="fas fa-volume-up"></i> DE';
          
          germanButton.addEventListener('click', e => {
            e.preventDefault();
            playAudio(germanWord, 'de', germanButton);
          });
          
          if (isMobileDevice) {
            germanButton.classList.add('btn-audio-mobile');
          }
          
          actionCell.appendChild(germanButton);
        }
        
        if (englishWord && !actionCell.querySelector(`[data-text="${englishWord}"][data-lang="en"]`)) {
          const englishButton = document.createElement('button');
          englishButton.className = 'btn btn-sm btn-info btn-audio';
          englishButton.setAttribute('data-text', englishWord);
          englishButton.setAttribute('data-lang', 'en');
          englishButton.setAttribute('type', 'button');
          englishButton.innerHTML = '<i class="fas fa-volume-up"></i> EN';
          
          englishButton.addEventListener('click', e => {
            e.preventDefault();
            playAudio(englishWord, 'en', englishButton);
          });
          
          if (isMobileDevice) {
            englishButton.classList.add('btn-audio-mobile');
          }
          
          actionCell.appendChild(document.createTextNode(' '));
          actionCell.appendChild(englishButton);
        }
      });
    }
    
    /**
     * Audio für mobile Geräte vorbereiten
     */
    function prepareAudioForMobile() {
      // Für iOS-Geräte: Audio-Kontext entsperren mit stiller Audio-Datei
      const silentAudio = new Audio('data:audio/mp3;base64,SUQzBAAAAAAAI1RTU0UAAAAPAAADTGF2ZjU4LjEwLjEwMAAAAAAAAAAAAAAA//tQwAAAAAAAAAAAAAAAAAAAAAAASW5mbwAAAA8AAAACAAABIADAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDV1dXV1dXV1dXV1dXV1dXV1dXV1dXV1dXV6urq6urq6urq6urq6urq6urq6urq6urq6v////////////////////////////////8AAAAATGF2YzU4LjEzAAAAAAAAAAAAAAAAJAYAAAAAAAAAASAFTTdmAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA');
      
      if (/iPhone|iPad|iPod/i.test(navigator.userAgent)) {
        const unlockAudio = function() {
          silentAudio.play().catch(() => {});
          
          if ('speechSynthesis' in window) {
            window.speechSynthesis.speak(new SpeechSynthesisUtterance(''));
          }
          
          document.removeEventListener('touchstart', unlockAudio);
          document.removeEventListener('touchend', unlockAudio);
          document.removeEventListener('click', unlockAudio);
        };
        
        document.addEventListener('touchstart', unlockAudio, {once: true});
        document.addEventListener('touchend', unlockAudio, {once: true});
        document.addEventListener('click', unlockAudio, {once: true});
      }
    }
    
    /**
     * Offline-Hinweis anzeigen
     */
    function showOfflineNotice(show) {
      let offlineNotice = document.getElementById('offline-notice');
      
      if (show) {
        if (!offlineNotice) {
          offlineNotice = document.createElement('div');
          offlineNotice.id = 'offline-notice';
          offlineNotice.className = 'alert alert-warning';
          offlineNotice.innerHTML = `
            <strong>Offline-Modus:</strong> Einige Funktionen sind nicht verfügbar.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          `;
          document.body.appendChild(offlineNotice);
        }
      } else if (offlineNotice) {
        offlineNotice.remove();
      }
    }
    
    // Offline-Status überwachen
    window.addEventListener('online', () => showOfflineNotice(false));
    window.addEventListener('offline', () => showOfflineNotice(true));
    
    // Initialen Offline-Status prüfen
    if (!navigator.onLine) {
      showOfflineNotice(true);
    }
  });