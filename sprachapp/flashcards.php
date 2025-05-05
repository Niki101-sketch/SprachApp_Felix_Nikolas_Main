<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
require_once 'learning.php';
requireLogin();

// Einheiten-ID aus der URL-Anfrage
$unitId = isset($_GET['unit_id']) ? (int)$_GET['unit_id'] : 0;
$direction = isset($_GET['direction']) ? $_GET['direction'] : 'de_en';
$fromGerman = ($direction === 'de_en');

if ($unitId <= 0) {
    header('Location: browse_units.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Einheit abrufen
$conn = connectDB();
$stmt = $conn->prepare("SELECT * FROM units WHERE unit_id = ?");
$stmt->bind_param("i", $unitId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $conn->close();
    header('Location: browse_units.php?error=unit_not_found');
    exit;
}

$unit = $result->fetch_assoc();
$conn->close();

// Karteikarten abrufen
$flashcards = getFlashcardsForUser($userId, $unitId);

// AJAX-Anfrage für die Überprüfung der Antwort verarbeiten
if (isset($_POST['check']) && isset($_POST['vocab_id']) && isset($_POST['answer'])) {
    $vocabId = (int)$_POST['vocab_id'];
    $answer = $_POST['answer'];
    
    $result = checkFlashcard($userId, $vocabId, $answer, $fromGerman);
    
    // Als JSON zurückgeben
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

$pageTitle = "Karteikarten: " . h($unit['unit_name']);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - SprachApp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="css/dark-theme.css">
    <style>
        /* Inline-Styles für Formular-Fixes */
        .form-control {
            background-color: var(--dark-surface-light) !important;
            color: var(--dark-text-primary) !important;
            border: 1px solid var(--dark-border);
        }
        
        .form-control:focus {
            background-color: var(--dark-surface-lighter) !important;
            color: var(--dark-text-primary) !important;
        }
        
        .form-control:disabled, 
        .form-control[readonly] {
            background-color: var(--dark-surface) !important;
            color: var(--dark-text-secondary) !important;
            opacity: 0.7;
        }
        
        /* Tabellenstile für falsch beantwortete Vokabeln */
        .table td, .table th {
            color: var(--dark-text-primary) !important;
        }
        
        /* Animation für Karten */
        .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Startseite</a></li>
                        <li class="breadcrumb-item"><a href="browse_units.php">Einheiten</a></li>
                        <li class="breadcrumb-item active"><?= h($unit['unit_name']) ?></li>
                    </ol>
                </nav>
                
                <div class="card mb-4 animate__animated animate__fadeIn">
                    <div class="card-header bg-primary text-white">
                        <h2><i class="fas fa-clone"></i> Karteikarten: <?= h($unit['unit_name']) ?></h2>
                    </div>
                    <div class="card-body">
                        <div class="direction-toggle text-center">
                            <div class="btn-group" role="group">
                                <a href="?unit_id=<?= $unitId ?>&direction=de_en" class="btn btn-outline-primary <?= $fromGerman ? 'active' : '' ?>">
                                    Deutsch → Englisch
                                </a>
                                <a href="?unit_id=<?= $unitId ?>&direction=en_de" class="btn btn-outline-primary <?= !$fromGerman ? 'active' : '' ?>">
                                    Englisch → Deutsch
                                </a>
                            </div>
                        </div>
                        
                        <?php if (empty($flashcards)): ?>
                        <div class="alert alert-info mt-4">
                            <p>Großartig! Du hast alle Karteikarten in dieser Einheit gelernt.</p>
                            <p>Versuche es später noch einmal oder wähle eine andere Einheit.</p>
                            <a href="browse_units.php" class="btn btn-primary">Andere Einheiten anzeigen</a>
                        </div>
                        <?php else: ?>
                        
                        <div id="flashcard-container" class="mt-4">
                            <?php 
                            // Nur die erste Karteikarte anzeigen
                            $vocab = $flashcards[0];
                            $question = $fromGerman ? $vocab['german_word'] : $vocab['english_word'];
                            $answer = $fromGerman ? $vocab['english_word'] : $vocab['german_word'];
                            $questionLang = $fromGerman ? 'Deutsch' : 'Englisch';
                            $answerLang = $fromGerman ? 'Englisch' : 'Deutsch';
                            ?>
                            
                            <div class="flashcard" id="card-<?= $vocab['vocab_id'] ?>">
                                <div class="flashcard-inner">
                                    <div class="flashcard-front">
                                        <div class="language-indicator"><?= $questionLang ?></div>
                                        <div class="word"><?= h($question) ?></div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary audio-btn" 
                                                onclick="event.stopPropagation(); playAudio('<?= h($question) ?>', '<?= $fromGerman ? 'de' : 'en' ?>');">
                                            <i class="fas fa-volume-up"></i> Aussprache
                                        </button>
                                    </div>
                                    <div class="flashcard-back">
                                        <div class="language-indicator"><?= $answerLang ?></div>
                                        <div class="word"><?= h($answer) ?></div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary audio-btn" 
                                                onclick="event.stopPropagation(); playAudio('<?= h($answer) ?>', '<?= $fromGerman ? 'en' : 'de' ?>');">
                                            <i class="fas fa-volume-up"></i> Aussprache
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <form id="answer-form" class="text-center">
                                <input type="hidden" id="vocab_id" value="<?= $vocab['vocab_id'] ?>">
                                <div class="mb-3">
                                    <label for="user_answer" class="form-label">Deine Antwort:</label>
                                    <input type="text" class="form-control form-control-lg" id="user_answer" autocomplete="off">
                                </div>
                                <button type="submit" class="btn btn-primary btn-lg">Überprüfen</button>
                                <button type="button" id="show-answer" class="btn btn-outline-secondary btn-lg">Antwort zeigen</button>
                            </form>
                            
                            <div id="feedback" class="feedback alert">
                                <div id="feedback-message"></div>
                                <div class="mt-3">
                                    <button id="next-card" class="btn btn-success">Nächste Karte</button>
                                </div>
                            </div>
                        </div>
                        
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h3><i class="fas fa-info-circle"></i> Weitere Lernmodi</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h5><i class="fas fa-pencil-alt"></i> Mini-Test</h5>
                                        <p>Teste dein Wissen mit einem kurzen Test.</p>
                                        <a href="mini_test.php?unit_id=<?= $unitId ?>" class="btn btn-outline-primary">Mini-Test starten</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <h5><i class="fas fa-gamepad"></i> Mini-Kahoot</h5>
                                        <p>Spiele ein Quiz mit Multiple-Choice-Fragen.</p>
                                        <a href="mini_kahoot.php?unit_id=<?= $unitId ?>" class="btn btn-outline-danger">Mini-Kahoot starten</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($flashcards)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h3><i class="fas fa-lightbulb"></i> Lern-Tipps</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item">Regelmäßiges Üben ist besser als stundenlanges Lernen auf einmal.</li>
                                    <li class="list-group-item">Versuche, die Wörter in einem Satz zu verwenden, um sie besser zu merken.</li>
                                    <li class="list-group-item">Nutze die Aussprache-Funktion, um die korrekte Aussprache zu üben.</li>
                                    <li class="list-group-item">Erstelle eigene Assoziationen zu den Wörtern, die dir beim Merken helfen.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <footer class="mt-5 py-4" style="background-color: var(--dark-surface); border-top: 1px solid var(--dark-border);">
        <div class="container">
            <div class="text-center">
                <p>&copy; <?= date('Y') ?> SprachApp. Alle Rechte vorbehalten.</p>
            </div>
        </div>
    </footer>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.3/js/bootstrap.bundle.min.js"></script>
    <script>
        // Audio-Funktion
        function playAudio(text, lang) {
            if (!text || !lang) {
                console.error('Text und Sprache müssen angegeben werden');
                return;
            }
            
            // Beschränke den Text auf 100 Zeichen (API-Limit)
            const limitedText = text.length > 100 ? text.substring(0, 100) : text;
            
            // Erstelle einen sicheren URL-Parameter
            const encodedText = encodeURIComponent(limitedText);
            
            // Erstelle die Audio-URL für Google Translate TTS
            const audioUrl = `https://translate.google.com/translate_tts?ie=UTF-8&q=${encodedText}&tl=${lang}&client=tw-ob`;
            
            // Erstelle ein neues Audio-Element
            const audio = new Audio(audioUrl);
            
            // Spiele den Ton ab
            audio.play().catch(error => {
                console.error('Fehler beim Abspielen des Audios:', error);
                alert('Die Aussprache konnte nicht abgespielt werden. Bitte versuche es später erneut.');
            });
        }
        
        // Karteikarten-Logik
        document.addEventListener('DOMContentLoaded', function() {
            // Überprüfen, ob die Seite eine Karteikarte enthält
            const flashcard = document.querySelector('.flashcard');
            if (!flashcard) return;
            
            const flashcardFront = document.querySelector('.flashcard-front');
            const answerForm = document.getElementById('answer-form');
            const userAnswerInput = document.getElementById('user_answer');
            const showAnswerBtn = document.getElementById('show-answer');
            const feedback = document.getElementById('feedback');
            const feedbackMessage = document.getElementById('feedback-message');
            const nextCardBtn = document.getElementById('next-card');
            
            // Prüfen, ob alle Elemente existieren
            if (!flashcardFront || !answerForm || !userAnswerInput || !showAnswerBtn || !feedback || !feedbackMessage || !nextCardBtn) {
                console.error('Einige erforderliche Elemente wurden nicht gefunden');
                return;
            }
            
            // Karteikarte umdrehen wenn auf die Karte geklickt wird
            flashcard.addEventListener('click', function(e) {
                // Nur umdrehen, wenn nicht auf einen Button geklickt wurde
                if (!e.target.closest('button')) {
                    flashcard.classList.toggle('flipped');
                }
            });
            
            // Karteikarte umdrehen mit dem Button
            showAnswerBtn.addEventListener('click', function() {
                flashcard.classList.add('flipped');
            });
            
            // Antwort überprüfen
            answerForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const vocabId = document.getElementById('vocab_id').value;
                const userAnswer = userAnswerInput.value.trim();
                
                if (userAnswer === '') {
                    return;
                }
                
                // AJAX-Anfrage zum Überprüfen der Antwort
                const xhr = new XMLHttpRequest();
                xhr.open('POST', window.location.href, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        try {
                            const response = xhr.responseText;
                            const data = JSON.parse(response);
                            
                            // Bei falscher Antwort die Karte rot aufleuchten lassen
                            if (!data.correct) {
                                // Entfernen der Klasse, falls sie bereits vorhanden ist
                                flashcardFront.classList.remove('red-flash');
                                
                                // Erzwinge einen Browser-Reflow
                                void flashcardFront.offsetWidth;
                                
                                // Füge die Klasse hinzu
                                flashcardFront.classList.add('red-flash');
                                
                                // Warte kurz, bis die Animation abgeschlossen ist
                                setTimeout(function() {
                                    flashcardFront.classList.remove('red-flash');
                                    showResult(data);
                                }, 600);
                            } else {
                                showResult(data);
                            }
                        } catch (e) {
                            console.error('Fehler beim Parsen der JSON-Antwort:', e, xhr.responseText);
                            alert('Es ist ein Fehler bei der Verarbeitung der Antwort aufgetreten. Bitte versuche es erneut.');
                        }
                    } else {
                        console.error('Fehler bei der AJAX-Anfrage. Status:', xhr.status);
                        alert('Es ist ein Fehler bei der Anfrage aufgetreten. Bitte versuche es erneut.');
                    }
                };
                
                xhr.onerror = function() {
                    console.error('Netzwerkfehler bei der AJAX-Anfrage.');
                    alert('Es ist ein Netzwerkfehler aufgetreten. Bitte überprüfe deine Internetverbindung.');
                };
                
                // Funktion zum Anzeigen des Ergebnisses
                function showResult(data) {
                    // Feedback anzeigen
                    feedback.style.display = 'block';
                    feedback.className = data.correct ? 'feedback alert alert-success' : 'feedback alert alert-danger';
                    feedbackMessage.innerHTML = data.message;
                    
                    // Karteikarte umdrehen
                    flashcard.classList.add('flipped');
                    
                    // WICHTIG: Formular-Styles beibehalten durch zusätzliche Klassen
                    userAnswerInput.classList.add('dark-input');
                    
                    // Formulareingaben deaktivieren
                    userAnswerInput.disabled = true;
                    // Das Textfeld dunkler Hintergrund auch im disabled-Zustand
                    userAnswerInput.style.backgroundColor = 'var(--dark-surface)';
                    userAnswerInput.style.color = 'var(--dark-text-secondary)';
                    userAnswerInput.style.opacity = '0.7';
                    
                    answerForm.querySelector('button[type="submit"]').disabled = true;
                    showAnswerBtn.disabled = true;
                }
                
                xhr.send('check=1&vocab_id=' + encodeURIComponent(vocabId) + '&answer=' + encodeURIComponent(userAnswer));
            });
            
            // Nächste Karte
            nextCardBtn.addEventListener('click', function() {
                // Seite neu laden für die nächste Karte
                window.location.reload();
            });
            
            // Setzt den Fokus auf das Antwortfeld, sobald die Seite geladen ist
            userAnswerInput.focus();
        });
    </script>
    <script src="audio.js"></script>
</body>
</html>