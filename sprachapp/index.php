<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';
requireLogin(); // Umleitung zur Login-Seite, wenn nicht eingeloggt

// Benutzerdaten abrufen
$userId = $_SESSION['user_id'];

// Datenbankverbindung herstellen
$conn = connectDB();

// Benutzerdaten abrufen
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();

// Favorisierte Einheiten abrufen
$favorites = [];
$stmt = $conn->prepare("
    SELECT uf.*, u.* 
    FROM user_favorites uf 
    JOIN units u ON uf.unit_id = u.unit_id 
    WHERE uf.user_id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$favsResult = $stmt->get_result();

while ($fav = $favsResult->fetch_assoc()) {
    // Struktur anpassen, um mit dem alten Code kompatibel zu sein
    $fav['units'] = [
        'unit_id' => $fav['unit_id'],
        'unit_name' => $fav['unit_name'],
        'description' => $fav['description'],
        'created_by' => $fav['created_by'],
        'is_public' => $fav['is_public'],
        'created_at' => $fav['created_at']
    ];
    $favorites[] = $fav;
}

// Bestenliste abrufen
$leaderboard = [];
$stmt = $conn->prepare("
    SELECT l.*, u.username 
    FROM leaderboard l 
    JOIN users u ON l.user_id = u.user_id 
    ORDER BY l.score DESC 
    LIMIT 5
");
$stmt->execute();
$leaderboardResult = $stmt->get_result();

while ($entry = $leaderboardResult->fetch_assoc()) {
    // Struktur anpassen, um mit dem alten Code kompatibel zu sein
    $entry['users'] = [
        'username' => $entry['username']
    ];
    $leaderboard[] = $entry;
}

// Falsch beantwortete Vokabeln abrufen
$wrongAnswers = [];
$stmt = $conn->prepare("
    SELECT w.*, v.*, u.unit_name
    FROM wrong_answers w 
    JOIN vocabulary v ON w.vocab_id = v.vocab_id 
    JOIN units u ON v.unit_id = u.unit_id 
    WHERE w.user_id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$wrongResult = $stmt->get_result();

while ($wrong = $wrongResult->fetch_assoc()) {
    // Struktur anpassen, um mit dem alten Code kompatibel zu sein
    $wrong['vocabulary'] = [
        'german_word' => $wrong['german_word'],
        'english_word' => $wrong['english_word'],
        'units' => [
            'unit_name' => $wrong['unit_name']
        ]
    ];
    $wrongAnswers[] = $wrong;
}

$conn->close();

$pageTitle = "Startseite";
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
        /* Inline-Styles für Tabellen-Fix bei falsch beantworteten Vokabeln */
        .table td, .table th {
            color: var(--dark-text-primary) !important;
        }
        
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.08);
        }
        
        /* Animation für Karten */
        .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
        }
        
        /* Accordion Styling im dunklen Design */
        .accordion-item {
            background-color: var(--dark-surface);
            border: 1px solid var(--dark-border);
        }
        
        .accordion-button {
            background-color: var(--dark-surface-light);
            color: var(--dark-text-primary);
            box-shadow: none;
            border-radius: 0;
            padding: 1rem 1.25rem;
        }
        
        .accordion-button:not(.collapsed) {
            background-color: var(--dark-primary-dark);
            color: white;
        }
        
        .accordion-button:focus {
            box-shadow: 0 0 0 0.25rem rgba(98, 0, 238, 0.25);
            border-color: var(--dark-primary);
        }
        
        .accordion-button::after {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='white'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
        }
        
        .accordion-body {
            background-color: var(--dark-surface);
            color: var(--dark-text-primary);
            padding: 1.25rem;
        }
        
        /* Badge Styling */
        .badge-count {
            position: relative;
            top: -1px;
            margin-left: 8px;
            background-color: var(--dark-error);
            color: white;
            border-radius: 20px;
            padding: 2px 8px;
            font-size: 0.85rem;
            font-weight: normal;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4 animate__animated animate__fadeIn">
                    <div class="card-header bg-primary text-white">
                        <h2><i class="fas fa-fire"></i> Dein Fortschritt</h2>
                    </div>
                    <div class="card-body">
                        <div class="streak-info text-center mb-4">
                            <h3><span class="badge bg-warning"><?= $userData['streak_days'] ?> Tage Streak</span></h3>
                            <div class="progress">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?= min($userData['streak_days'] * 10, 100) ?>%"></div>
                            </div>
                        </div>
                        
                        <div class="row text-center">
                            <div class="col-md-6 mb-3">
                                <div class="stats-card pulse">
                                    <h4><?= $userData['total_words_learned'] ?></h4>
                                    <p>Gelernte Wörter</p>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="stats-card">
                                    <h4><?= $userData['total_units_learned'] ?></h4>
                                    <p>Absolvierte Einheiten</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4 animate__animated animate__fadeIn" style="animation-delay: 0.2s">
                    <div class="card-header bg-success text-white">
                        <h2><i class="fas fa-star"></i> Favorisierte Einheiten</h2>
                    </div>
                    <div class="card-body">
                        <?php if (empty($favorites)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-folder-open fa-4x mb-3 text-muted"></i>
                                <p class="text-center">Du hast noch keine Einheiten favorisiert.</p>
                                <a href="browse_units.php" class="btn btn-primary btn-lg mt-2">Einheiten durchsuchen</a>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($favorites as $fav): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="unit-card">
                                        <h5><?= h($fav['units']['unit_name']) ?></h5>
                                        <p><?= h(substr($fav['units']['description'], 0, 100)) ?>...</p>
                                        <div class="d-flex justify-content-between">
                                            <a href="flashcards.php?unit_id=<?= $fav['units']['unit_id'] ?>" class="btn btn-sm btn-primary">Lernen</a>
                                            <a href="unit_details.php?id=<?= $fav['units']['unit_id'] ?>" class="btn btn-sm btn-info">Details</a>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!empty($wrongAnswers)): ?>
                <div class="card mb-4 animate__animated animate__fadeIn" style="animation-delay: 0.4s">
                    <div class="card-header bg-danger text-white">
                        <h2>
                            <i class="fas fa-exclamation-triangle"></i> 
                            Falsch beantwortete Vokabeln
                            <span class="badge-count"><?= count($wrongAnswers) ?></span>
                        </h2>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="wrongAnswersAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingWrongAnswers">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                            data-bs-target="#collapseWrongAnswers" aria-expanded="false" 
                                            aria-controls="collapseWrongAnswers">
                                        <i class="fas fa-list me-2"></i> Zeige alle falsch beantworteten Vokabeln
                                    </button>
                                </h2>
                                <div id="collapseWrongAnswers" class="accordion-collapse collapse" 
                                     aria-labelledby="headingWrongAnswers" data-bs-parent="#wrongAnswersAccordion">
                                    <div class="accordion-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th style="color: var(--dark-text-primary) !important;">Deutsch</th>
                                                        <th style="color: var(--dark-text-primary) !important;">Englisch</th>
                                                        <th style="color: var(--dark-text-primary) !important;">Einheit</th>
                                                        <th style="color: var(--dark-text-primary) !important;">Aktion</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($wrongAnswers as $wrong): ?>
                                                    <tr>
                                                        <td style="color: var(--dark-text-primary) !important;"><?= h($wrong['vocabulary']['german_word']) ?></td>
                                                        <td style="color: var(--dark-text-primary) !important;"><?= h($wrong['vocabulary']['english_word']) ?></td>
                                                        <td style="color: var(--dark-text-primary) !important;"><?= h($wrong['vocabulary']['units']['unit_name']) ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-info" 
                                                                onclick="playAudio('<?= h($wrong['vocabulary']['german_word']) ?>', 'de')">
                                                                <i class="fas fa-volume-up"></i> DE
                                                            </button>
                                                            <button class="btn btn-sm btn-info"
                                                                onclick="playAudio('<?= h($wrong['vocabulary']['english_word']) ?>', 'en')">
                                                                <i class="fas fa-volume-up"></i> EN
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <a href="practice_wrong.php" class="btn btn-danger mt-3">
                                            <i class="fas fa-redo"></i> Alle falsch beantworteten üben
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-4">
                <div class="card mb-4 animate__animated animate__fadeIn" style="animation-delay: 0.3s">
                    <div class="card-header bg-info text-white">
                        <h2><i class="fas fa-trophy"></i> Bestenliste</h2>
                    </div>
                    <div class="card-body">
                        <ol class="list-group list-group-numbered">
                            <?php foreach ($leaderboard as $entry): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div class="ms-2 me-auto">
                                    <div class="fw-bold"><?= h($entry['users']['username']) ?></div>
                                    <span class="badge bg-warning rounded-pill"><?= $entry['streak_days'] ?> Tage Streak</span>
                                </div>
                                <span class="badge bg-primary rounded-pill"><?= $entry['score'] ?> Punkte</span>
                            </li>
                            <?php endforeach; ?>
                        </ol>
                        <a href="leaderboard.php" class="btn btn-info mt-3 d-block">Vollständige Bestenliste</a>
                    </div>
                </div>
                
                <div class="card mb-4 animate__animated animate__fadeIn" style="animation-delay: 0.5s">
                    <div class="card-header bg-warning text-dark">
                        <h2><i class="fas fa-gamepad"></i> Lernmodi</h2>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-3">
                            <a href="browse_units.php" class="btn btn-lg btn-outline-primary">
                                <i class="fas fa-book"></i> Karteikarten
                            </a>
                            <a href="browse_units.php?mode=test" class="btn btn-lg btn-outline-success">
                                <i class="fas fa-pencil-alt"></i> Mini-Test
                            </a>
                            <a href="browse_units.php?mode=kahoot" class="btn btn-lg btn-outline-danger">
                                <i class="fas fa-gamepad"></i> Mini-Kahoot
                            </a>
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
    </script>
    
    <script src="audio.js"></script>
</body>
</html>