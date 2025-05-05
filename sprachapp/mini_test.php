<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
require_once 'learning.php';
requireLogin();

// Benutzer-ID abrufen
$userId = $_SESSION['user_id'];

// Unit-IDs aus der URL abrufen (als Array für mehrere Units)
$unitIds = isset($_GET['unit_id']) ? (is_array($_GET['unit_id']) ? $_GET['unit_id'] : [$_GET['unit_id']]) : [];
$unitIds = array_map('intval', $unitIds); // Alle IDs in Integer umwandeln

// Richtung aus der URL abrufen
$direction = isset($_GET['direction']) ? $_GET['direction'] : 'de_en';
$fromGerman = ($direction === 'de_en');

// Testergebnisse (wird am Ende des Tests angezeigt)
$testResults = [];
$correctAnswers = 0;
$totalQuestions = 0;
$testSubmitted = false;

// Datenbankverbindung herstellen
$conn = connectDB();

// Units mit allen möglichen IDs (für die Unit-Auswahl)
$stmt = $conn->prepare("SELECT unit_id, unit_name FROM units WHERE is_public = 1 ORDER BY unit_name ASC");
$stmt->execute();
$allUnitsResult = $stmt->get_result();
$allUnits = [];

while ($unit = $allUnitsResult->fetch_assoc()) {
    $allUnits[] = $unit;
}

// Wenn keine Unit-IDs ausgewählt wurden, zeige die Auswahlseite an
if (empty($unitIds) && !isset($_POST['submit_test'])) {
    $showUnitSelection = true;
} else {
    $showUnitSelection = false;
    
    // Vokabeln für die ausgewählten Units abrufen
    $vocabularies = [];
    
    if (!empty($unitIds)) {
        // Platzhalter für die SQL-Query erstellen (?, ?, ?, ...)
        $placeholders = implode(',', array_fill(0, count($unitIds), '?'));
        
        // SQL-Query vorbereiten
        $sql = "SELECT v.*, u.unit_name 
                FROM vocabulary v 
                JOIN units u ON v.unit_id = u.unit_id 
                WHERE v.unit_id IN ($placeholders)";
        
        $stmt = $conn->prepare($sql);
        
        // Parameter binden (alle Unit-IDs)
        $stmt->bind_param(str_repeat('i', count($unitIds)), ...$unitIds);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($vocab = $result->fetch_assoc()) {
            $vocabularies[] = $vocab;
        }
        
        // Zufällige Reihenfolge für die Vokabeln
        shuffle($vocabularies);
    }
    
    // Unit-Namen für den Titel abrufen
    $unitNames = [];
    if (!empty($unitIds)) {
        $placeholders = implode(',', array_fill(0, count($unitIds), '?'));
        $sql = "SELECT unit_name FROM units WHERE unit_id IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(str_repeat('i', count($unitIds)), ...$unitIds);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($unit = $result->fetch_assoc()) {
            $unitNames[] = $unit['unit_name'];
        }
    }
    
    // Test wurde abgeschickt
    if (isset($_POST['submit_test'])) {
        $testSubmitted = true;
        $answers = $_POST['answers'] ?? [];
        $vocabData = $_POST['vocab_data'] ?? [];
        
        foreach ($vocabData as $vocabId => $data) {
            $userAnswer = $answers[$vocabId] ?? '';
            $correctAnswer = $data['answer'];
            $question = $data['question'];
            $unitName = $data['unit_name'];
            
            // Antwort überprüfen
            $isCorrect = strtolower(trim($userAnswer)) === strtolower(trim($correctAnswer));
            
            // Testergebnis speichern
            $testResults[] = [
                'vocab_id' => $vocabId,
                'question' => $question,
                'user_answer' => $userAnswer,
                'correct_answer' => $correctAnswer,
                'is_correct' => $isCorrect,
                'unit_name' => $unitName
            ];
            
            // Statistik aktualisieren
            if ($isCorrect) {
                $correctAnswers++;
            } else {
                // In die wrong_answers Tabelle eintragen
                $stmt = $conn->prepare("
                    INSERT INTO wrong_answers (user_id, vocab_id, last_wrong_date) 
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE last_wrong_date = NOW()
                ");
                $stmt->bind_param("ii", $userId, $vocabId);
                $stmt->execute();
            }
            
            $totalQuestions++;
        }
        
        // Prozentsatz berechnen
        $percentCorrect = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100) : 0;
    }
}

$conn->close();

$pageTitle = !empty($unitNames) ? "Mini-Test: " . implode(", ", $unitNames) : "Mini-Test";
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
        /* Inline-Styles für den Mini-Test */
        .question-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 4px solid var(--dark-primary);
            margin-bottom: 1.5rem;
            background-color: var(--dark-surface-light);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .question-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
        }
        
        .question-header {
            background-color: var(--dark-surface);
            color: var(--dark-text-primary);
            padding: 1rem;
            border-bottom: 1px solid var(--dark-border);
        }
        
        .question-body {
            padding: 1.5rem;
        }
        
        .question-text {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: var(--dark-text-primary);
        }
        
        .form-control {
            background-color: var(--dark-surface-light) !important;
            color: var(--dark-text-primary) !important;
            border: 1px solid var(--dark-border);
        }
        
        .form-control:focus {
            background-color: var(--dark-surface-lighter) !important;
            color: var(--dark-text-primary) !important;
            border-color: var(--dark-primary);
            box-shadow: 0 0 0 0.2rem rgba(98, 0, 238, 0.25);
        }
        
        .unit-badge {
            background-color: var(--dark-primary);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-left: 0.5rem;
        }
        
        /* Animation für den Test */
        .question-card {
            animation: fadeInUp 0.5s ease forwards;
        }
        
        @keyframes fadeInUp {
            0% { opacity: 0; transform: translateY(20px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        
        /* Verzögerung für Fragen */
        .question-card:nth-child(1) { animation-delay: 0.1s; }
        .question-card:nth-child(2) { animation-delay: 0.15s; }
        .question-card:nth-child(3) { animation-delay: 0.2s; }
        .question-card:nth-child(4) { animation-delay: 0.25s; }
        .question-card:nth-child(5) { animation-delay: 0.3s; }
        
        /* Ergebnisse */
        .result-container {
            background-color: var(--dark-surface-light);
            border-radius: 10px;
            padding: 2rem;
            margin-top: 2rem;
            text-align: center;
        }
        
        .result-score {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--dark-primary-light);
        }
        
        .result-text {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            color: var(--dark-text-primary);
        }
        
        .result-details {
            margin-top: 2rem;
        }
        
        .correct-answer {
            color: var(--dark-success);
            font-weight: 500;
        }
        
        .wrong-answer {
            color: var(--dark-error);
            font-weight: 500;
            text-decoration: line-through;
        }
        
        /* Progress bar */
        .progress {
            height: 1.5rem;
            background-color: var(--dark-surface);
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .progress-bar-success {
            background-color: var(--dark-success);
        }
        
        .progress-bar-danger {
            background-color: var(--dark-error);
        }
        
        /* Unit-Auswahlseite */
        .unit-selection-card {
            border-left: 4px solid var(--dark-primary);
            transition: all 0.3s ease;
            cursor: pointer;
            margin-bottom: 0.75rem;
        }
        
        .unit-selection-card:hover {
            transform: translateX(5px);
            background-color: var(--dark-surface-lighter);
        }
        
        .unit-selection-card.selected {
            background-color: rgba(98, 0, 238, 0.15);
            border-left: 4px solid var(--dark-secondary);
        }
        
        /* Checkbox Styling */
        .custom-checkbox .custom-control-input:checked ~ .custom-control-label::before {
            background-color: var(--dark-primary);
            border-color: var(--dark-primary);
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
                        <li class="breadcrumb-item active">Mini-Test</li>
                    </ol>
                </nav>
                
                <div class="card mb-4 animate__animated animate__fadeIn">
                    <div class="card-header bg-primary text-white">
                        <h2><i class="fas fa-pencil-alt"></i> Mini-Test</h2>
                    </div>
                    <div class="card-body">
                        <?php if ($showUnitSelection): ?>
                        <!-- Unit-Auswahlseite -->
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Wähle eine oder mehrere Einheiten aus, die du testen möchtest.
                        </div>
                        
                        <form id="unit-selection-form" action="mini_test.php" method="get">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="direction">Richtung:</label>
                                        <select name="direction" id="direction" class="form-control">
                                            <option value="de_en">Deutsch → Englisch</option>
                                            <option value="en_de">Englisch → Deutsch</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 text-end align-self-end">
                                    <button type="submit" class="btn btn-primary" id="start-test-btn" disabled>
                                        <i class="fas fa-play"></i> Test starten
                                    </button>
                                </div>
                            </div>
                            
                            <div class="unit-selection">
                                <h4 class="mb-3"><i class="fas fa-list"></i> Verfügbare Einheiten</h4>
                                
                                <div class="row">
                                    <?php foreach ($allUnits as $unit): ?>
                                    <div class="col-md-6">
                                        <div class="card unit-selection-card" data-unit-id="<?= $unit['unit_id'] ?>">
                                            <div class="card-body d-flex align-items-center">
                                                <div class="form-check">
                                                    <input class="form-check-input unit-checkbox" type="checkbox" name="unit_id[]" value="<?= $unit['unit_id'] ?>" id="unit-<?= $unit['unit_id'] ?>">
                                                </div>
                                                <label class="ms-2 mb-0 flex-grow-1" for="unit-<?= $unit['unit_id'] ?>">
                                                    <?= h($unit['unit_name']) ?>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </form>
                        
                        <?php elseif ($testSubmitted): ?>
                        <!-- Testergebnisse -->
                        <div class="result-container">
                            <h3>Test abgeschlossen!</h3>
                            
                            <div class="result-score">
                                <?= $percentCorrect ?>%
                            </div>
                            
                            <div class="result-text">
                                Du hast <?= $correctAnswers ?> von <?= $totalQuestions ?> Fragen richtig beantwortet.
                            </div>
                            
                            <div class="progress">
                                <div class="progress-bar progress-bar-success" role="progressbar" style="width: <?= $percentCorrect ?>%"></div>
                                <div class="progress-bar progress-bar-danger" role="progressbar" style="width: <?= 100 - $percentCorrect ?>%"></div>
                            </div>
                            
                            <?php
                            // Text basierend auf dem Ergebnis anzeigen
                            if ($percentCorrect >= 90) {
                                echo '<div class="alert alert-success">Hervorragend! Du beherrschst diese Vokabeln bereits sehr gut.</div>';
                            } elseif ($percentCorrect >= 70) {
                                echo '<div class="alert alert-success">Gut gemacht! Du hast die meisten Vokabeln richtig.</div>';
                            } elseif ($percentCorrect >= 50) {
                                echo '<div class="alert alert-warning">Nicht schlecht, aber da ist noch Raum für Verbesserung.</div>';
                            } else {
                                echo '<div class="alert alert-danger">Du solltest diese Vokabeln noch mehr üben.</div>';
                            }
                            ?>
                            
                            <div class="d-flex justify-content-center mt-4 mb-5">
                                <a href="browse_units.php" class="btn btn-secondary me-2">
                                    <i class="fas fa-arrow-left"></i> Zurück zu Einheiten
                                </a>
                                <a href="mini_test.php" class="btn btn-primary me-2">
                                    <i class="fas fa-redo"></i> Neuer Test
                                </a>
                                <?php if (!empty($unitIds)): ?>
                                <a href="flashcards.php?unit_id=<?= $unitIds[0] ?>" class="btn btn-success">
                                    <i class="fas fa-clone"></i> Karteikarten
                                </a>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Falsche Antworten anzeigen -->
                            <?php
                            $wrongAnswers = array_filter($testResults, function($result) {
                                return !$result['is_correct'];
                            });
                            
                            if (!empty($wrongAnswers)):
                            ?>
                            <div class="result-details">
                                <h4>Falsche Antworten</h4>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Frage</th>
                                                <th>Deine Antwort</th>
                                                <th>Richtige Antwort</th>
                                                <th>Einheit</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($wrongAnswers as $wrong): ?>
                                            <tr>
                                                <td><?= h($wrong['question']) ?></td>
                                                <td class="wrong-answer"><?= h($wrong['user_answer']) ?></td>
                                                <td class="correct-answer"><?= h($wrong['correct_answer']) ?></td>
                                                <td><?= h($wrong['unit_name']) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Alle Antworten anzeigen -->
                            <div class="result-details">
                                <h4 class="mb-3">Alle Antworten</h4>
                                <div class="accordion" id="accordionResults">
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingAll">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAll" aria-expanded="false" aria-controls="collapseAll">
                                                Alle Antworten anzeigen
                                            </button>
                                        </h2>
                                        <div id="collapseAll" class="accordion-collapse collapse" aria-labelledby="headingAll" data-bs-parent="#accordionResults">
                                            <div class="accordion-body">
                                                <div class="table-responsive">
                                                    <table class="table table-striped">
                                                        <thead>
                                                            <tr>
                                                                <th>Frage</th>
                                                                <th>Deine Antwort</th>
                                                                <th>Richtige Antwort</th>
                                                                <th>Ergebnis</th>
                                                                <th>Einheit</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($testResults as $result): ?>
                                                            <tr>
                                                                <td><?= h($result['question']) ?></td>
                                                                <td class="<?= $result['is_correct'] ? 'correct-answer' : 'wrong-answer' ?>"><?= h($result['user_answer']) ?></td>
                                                                <td class="correct-answer"><?= h($result['correct_answer']) ?></td>
                                                                <td>
                                                                    <?php if ($result['is_correct']): ?>
                                                                    <span class="badge bg-success"><i class="fas fa-check"></i> Richtig</span>
                                                                    <?php else: ?>
                                                                    <span class="badge bg-danger"><i class="fas fa-times"></i> Falsch</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td><?= h($result['unit_name']) ?></td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php else: ?>
                        <!-- Test-Formular -->
                        <div class="alert alert-info mb-4">
                            <i class="fas fa-info-circle"></i> Übersetze die folgenden Wörter. Der Test enthält <?= count($vocabularies) ?> Vokabeln.
                        </div>
                        
                        <form id="test-form" method="post" action="mini_test.php">
                            <?php foreach ($unitIds as $id): ?>
                            <input type="hidden" name="unit_id[]" value="<?= $id ?>">
                            <?php endforeach; ?>
                            
                            <?php foreach ($vocabularies as $index => $vocab): 
                            // Frage und Antwort basierend auf der Richtung festlegen
                            $question = $fromGerman ? $vocab['german_word'] : $vocab['english_word'];
                            $answer = $fromGerman ? $vocab['english_word'] : $vocab['german_word'];
                            $questionLang = $fromGerman ? 'Deutsch' : 'Englisch';
                            $answerLang = $fromGerman ? 'Englisch' : 'Deutsch';
                            ?>
                            
                            <div class="question-card" style="animation-delay: <?= 0.1 + ($index * 0.05) ?>s">
                                <div class="question-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-primary"><?= $index + 1 ?>/<?= count($vocabularies) ?></span>
                                        <span class="ms-2"><?= $questionLang ?> → <?= $answerLang ?></span>
                                    </div>
                                    <div class="unit-badge"><?= h($vocab['unit_name']) ?></div>
                                </div>
                                <div class="question-body">
                                    <div class="question-text">
                                        <?= h($question) ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="playAudio('<?= h($question) ?>', '<?= $fromGerman ? 'de' : 'en' ?>')">
                                            <i class="fas fa-volume-up"></i>
                                        </button>
                                    </div>
                                    <div class="answer-input">
                                        <input type="text" name="answers[<?= $vocab['vocab_id'] ?>]" class="form-control" placeholder="Deine Antwort hier..." required>
                                        <input type="hidden" name="vocab_data[<?= $vocab['vocab_id'] ?>][question]" value="<?= h($question) ?>">
                                        <input type="hidden" name="vocab_data[<?= $vocab['vocab_id'] ?>][answer]" value="<?= h($answer) ?>">
                                        <input type="hidden" name="vocab_data[<?= $vocab['vocab_id'] ?>][unit_name]" value="<?= h($vocab['unit_name']) ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <?php endforeach; ?>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <a href="mini_test.php" class="btn btn-secondary">Zurück zur Auswahl</a>
                                <button type="submit" name="submit_test" class="btn btn-primary">
                                    <i class="fas fa-check"></i> Test abschließen
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
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
        
        document.addEventListener('DOMContentLoaded', function() {
            // Unit-Auswahl-Funktionalität
            const unitSelectionCards = document.querySelectorAll('.unit-selection-card');
            const unitCheckboxes = document.querySelectorAll('.unit-checkbox');
            const startTestBtn = document.getElementById('start-test-btn');
            
            if (unitSelectionCards.length > 0) {
                // Klick auf Card = Checkbox an/aus
                unitSelectionCards.forEach(card => {
                    card.addEventListener('click', function(e) {
                        // Nicht auslösen, wenn direkt auf die Checkbox geklickt wurde
                        if (e.target.type !== 'checkbox') {
                            const unitId = this.dataset.unitId;
                            const checkbox = document.getElementById('unit-' + unitId);
                            checkbox.checked = !checkbox.checked;
                            updateCardSelection(this, checkbox.checked);
                            updateStartButton();
                        }
                    });
                });
                
                // Checkbox-Änderungen
                unitCheckboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        const unitId = this.value;
                        const card = document.querySelector(`.unit-selection-card[data-unit-id="${unitId}"]`);
                        updateCardSelection(card, this.checked);
                        updateStartButton();
                    });
                });
                
                // Card-Auswahl aktualisieren
                function updateCardSelection(card, isSelected) {
                    if (isSelected) {
                        card.classList.add('selected');
                    } else {
                        card.classList.remove('selected');
                    }
                }
                
                // Start-Button aktivieren/deaktivieren
                function updateStartButton() {
                    const checkedCount = document.querySelectorAll('.unit-checkbox:checked').length;
                    startTestBtn.disabled = checkedCount === 0;
                }
            }
            
            // Ergebnisseite - Farbige Progressbar
            const progressBar = document.querySelector('.progress-bar-success');
            if (progressBar) {
                const percent = parseFloat(progressBar.style.width);
                let color;
                
                if (percent >= 80) {
                    color = '#4caf50'; // Grün
                } else if (percent >= 60) {
                    color = '#8bc34a'; // Hellgrün
                } else if (percent >= 40) {
                    color = '#ffeb3b'; // Gelb
                } else if (percent >= 20) {
                    color = '#ff9800'; // Orange
                } else {
                    color = '#f44336'; // Rot
                }
                
                progressBar.style.backgroundColor = color;
            }
        });
    </script>
</body>
</html>