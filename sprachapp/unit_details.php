<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
require_once 'vocabulary.php';
requireLogin();

// Unit-ID aus der URL abrufen
$unitId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($unitId <= 0) {
    header('Location: browse_units.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Datenbankverbindung herstellen
$conn = connectDB();

// Unit-Details abrufen
$stmt = $conn->prepare("
    SELECT u.*, us.username 
    FROM units u 
    JOIN users us ON u.created_by = us.user_id 
    WHERE u.unit_id = ?
");
$stmt->bind_param("i", $unitId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $conn->close();
    header('Location: browse_units.php?error=unit_not_found');
    exit;
}

$unit = $result->fetch_assoc();

// Vokabeln der Unit abrufen
$stmt = $conn->prepare("
    SELECT * FROM vocabulary 
    WHERE unit_id = ? 
    ORDER BY german_word ASC
");
$stmt->bind_param("i", $unitId);
$stmt->execute();
$vocabularyResult = $stmt->get_result();
$vocabularies = [];

while ($vocab = $vocabularyResult->fetch_assoc()) {
    $vocabularies[] = $vocab;
}

// Prüfen, ob der Benutzer diese Unit favorisiert hat
$stmt = $conn->prepare("
    SELECT * FROM user_favorites 
    WHERE user_id = ? AND unit_id = ?
");
$stmt->bind_param("ii", $userId, $unitId);
$stmt->execute();
$favResult = $stmt->get_result();
$isFavorite = ($favResult->num_rows > 0);

// Favorisieren oder Entfernen verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'favorite') {
        // Favorisieren
        $stmt = $conn->prepare("INSERT IGNORE INTO user_favorites (user_id, unit_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $userId, $unitId);
        $stmt->execute();
        $isFavorite = true;
    } else if ($_POST['action'] === 'unfavorite') {
        // Favorisieren entfernen
        $stmt = $conn->prepare("DELETE FROM user_favorites WHERE user_id = ? AND unit_id = ?");
        $stmt->bind_param("ii", $userId, $unitId);
        $stmt->execute();
        $isFavorite = false;
    }
    
    // Nach der Verarbeitung neu laden
    header('Location: unit_details.php?id=' . $unitId);
    exit;
}

$conn->close();

$pageTitle = "Unit: " . h($unit['unit_name']);
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
        /* Inline-Styles für Tabellen */
        .table {
            color: var(--dark-text-primary) !important;
        }
        
        .table thead th {
            color: var(--dark-primary-light) !important;
            border-bottom: 2px solid var(--dark-border);
            font-weight: 600;
        }
        
        .table td, .table th {
            color: var(--dark-text-primary) !important;
            border-top: 1px solid var(--dark-border);
            vertical-align: middle;
        }
        
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.08);
        }
        
        /* Vokabel-Tabelle */
        .vocab-table .german-word {
            font-weight: 500;
        }
        
        .vocab-table .english-word {
            color: var(--dark-secondary) !important;
            font-weight: 400;
        }
        
        /* Animation für Tabellenzeilen */
        @keyframes fadeInUp {
            0% { opacity: 0; transform: translateY(20px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        
        .vocab-row {
            animation: fadeInUp 0.5s ease forwards;
            opacity: 0;
        }
        
        /* Verzögerung für Animation */
        .vocab-row:nth-child(1) { animation-delay: 0.1s; }
        .vocab-row:nth-child(2) { animation-delay: 0.15s; }
        .vocab-row:nth-child(3) { animation-delay: 0.2s; }
        .vocab-row:nth-child(4) { animation-delay: 0.25s; }
        .vocab-row:nth-child(5) { animation-delay: 0.3s; }
        .vocab-row:nth-child(6) { animation-delay: 0.35s; }
        .vocab-row:nth-child(7) { animation-delay: 0.4s; }
        .vocab-row:nth-child(8) { animation-delay: 0.45s; }
        .vocab-row:nth-child(9) { animation-delay: 0.5s; }
        .vocab-row:nth-child(10) { animation-delay: 0.55s; }
        
        /* Star-Animation */
        @keyframes starPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        
        .btn-warning .fa-star, .btn-outline-warning .fa-star {
            animation: starPulse 2s infinite;
        }
        
        /* Unit-Info Box */
        .unit-info {
            background-color: var(--dark-surface-light);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 4px solid var(--dark-primary);
        }
        
        .unit-info h3 {
            color: var(--dark-primary-light);
            margin-bottom: 1rem;
        }
        
        .unit-info p {
            color: var(--dark-text-primary);
            margin-bottom: 0.5rem;
        }
        
        .unit-info .meta {
            color: var(--dark-text-secondary);
            font-size: 0.9rem;
            margin-top: 1rem;
        }
        
        /* Suchfeld */
        #vocab-search {
            background-color: var(--dark-surface-light);
            color: var(--dark-text-primary);
            border: 1px solid var(--dark-border);
        }
        
        #vocab-search:focus {
            background-color: var(--dark-surface-lighter);
            color: var(--dark-text-primary);
            border-color: var(--dark-primary);
            box-shadow: 0 0 0 0.2rem rgba(98, 0, 238, 0.25);
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
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h2><i class="fas fa-book"></i> <?= h($unit['unit_name']) ?></h2>
                        <form method="post" action="unit_details.php?id=<?= $unitId ?>">
                            <?php if ($isFavorite): ?>
                            <input type="hidden" name="action" value="unfavorite">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-star"></i> Favorisiert
                            </button>
                            <?php else: ?>
                            <input type="hidden" name="action" value="favorite">
                            <button type="submit" class="btn btn-outline-warning">
                                <i class="far fa-star"></i> Favorisieren
                            </button>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="card-body">
                        <div class="unit-info">
                            <h3><i class="fas fa-info-circle"></i> Unit-Informationen</h3>
                            <p><strong>Beschreibung:</strong> <?= h($unit['description']) ?></p>
                            <p><strong>Anzahl Vokabeln:</strong> <?= count($vocabularies) ?></p>
                            <div class="meta">
                                <p><i class="fas fa-user"></i> Erstellt von: <?= h($unit['username']) ?></p>
                                <p><i class="fas fa-calendar-alt"></i> Erstellt am: <?= date('d.m.Y', strtotime($unit['created_at'])) ?></p>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-4">
                            <h3><i class="fas fa-list"></i> Vokabelliste</h3>
                            <div class="d-flex">
                                <div class="input-group" style="width: 300px;">
                                    <input type="text" id="vocab-search" class="form-control" placeholder="Vokabeln durchsuchen...">
                                    <button class="btn btn-outline-secondary" type="button">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                                <div class="ms-2">
                                    <a href="flashcards.php?unit_id=<?= $unitId ?>" class="btn btn-primary">
                                        <i class="fas fa-play"></i> Lernen
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (empty($vocabularies)): ?>
                        <div class="alert alert-info">
                            Diese Einheit enthält noch keine Vokabeln.
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover vocab-table">
                                <thead>
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="40%">Deutsch</th>
                                        <th width="40%">Englisch</th>
                                        <th width="15%">Aktionen</th>
                                    </tr>
                                </thead>
                                <tbody id="vocab-list">
                                    <?php foreach ($vocabularies as $index => $vocab): ?>
                                    <tr class="vocab-row">
                                        <td><?= $index + 1 ?></td>
                                        <td class="german-word"><?= h($vocab['german_word']) ?></td>
                                        <td class="english-word"><?= h($vocab['english_word']) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="playAudio('<?= h($vocab['german_word']) ?>', 'de')">
                                                <i class="fas fa-volume-up"></i> DE
                                            </button>
                                            <button class="btn btn-sm btn-info" onclick="playAudio('<?= h($vocab['english_word']) ?>', 'en')">
                                                <i class="fas fa-volume-up"></i> EN
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <h4>Lernmodi für diese Einheit</h4>
                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <h5><i class="fas fa-clone"></i> Karteikarten</h5>
                                            <p>Lerne mit Karteikarten und wiederhole regelmäßig.</p>
                                            <a href="flashcards.php?unit_id=<?= $unitId ?>" class="btn btn-primary">Karteikarten starten</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <h5><i class="fas fa-pencil-alt"></i> Mini-Test</h5>
                                            <p>Teste dein Wissen mit einem kurzen Test.</p>
                                            <a href="mini_test.php?unit_id=<?= $unitId ?>" class="btn btn-success">Mini-Test starten</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <h5><i class="fas fa-gamepad"></i> Mini-Kahoot</h5>
                                            <p>Spiele ein Quiz mit Multiple-Choice-Fragen.</p>
                                            <a href="mini_kahoot.php?unit_id=<?= $unitId ?>" class="btn btn-danger">Mini-Kahoot starten</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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
        
        // Suchfunktion für Vokabeln
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('vocab-search');
            const vocabList = document.getElementById('vocab-list');
            const vocabRows = vocabList ? Array.from(vocabList.getElementsByClassName('vocab-row')) : [];
            
            if (searchInput && vocabRows.length > 0) {
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
                    
                    // Zeige Meldung wenn keine Ergebnisse
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
        });
    </script>
</body>
</html>