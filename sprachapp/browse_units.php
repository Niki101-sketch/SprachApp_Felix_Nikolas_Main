<?php
include 'includes/dark-header.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
require_once 'vocabulary.php';
requireLogin();

$mode = $_GET['mode'] ?? 'flashcards';
$userId = $_SESSION['user_id'];

// Diese Funktionen wurden bereits umgestellt und verwenden direkte SQL-Abfragen
// Statt supabaseQuery(), was wir auch in vocabulary.php korrigiert haben
$publicUnits = getAllPublicUnits();
$favorites = getUserFavorites($userId);

// Erstelle ein Array mit nur den unit_ids der favorisierten Einheiten
$favoriteIds = array_map(function($fav) {
    return $fav['unit_id'];
}, $favorites);

// Favorisieren oder Entfernen verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['unit_id'])) {
    $unitId = (int)$_POST['unit_id'];
    
    if ($_POST['action'] === 'favorite') {
        // Favorisieren
        $conn = connectDB();
        $stmt = $conn->prepare("INSERT IGNORE INTO user_favorites (user_id, unit_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $userId, $unitId);
        $stmt->execute();
        $stmt->close();
        $conn->close();
    } else if ($_POST['action'] === 'unfavorite') {
        // Favorisieren entfernen
        $conn = connectDB();
        $stmt = $conn->prepare("DELETE FROM user_favorites WHERE user_id = ? AND unit_id = ?");
        $stmt->bind_param("ii", $userId, $unitId);
        $stmt->execute();
        $stmt->close();
        $conn->close();
    }
    
    // Umleiten, um Formular-Neuübermittlung zu verhindern
    header('Location: browse_units.php' . ($mode !== 'flashcards' ? '?mode=' . $mode : ''));
    exit;
}

$pageTitle = "Einheiten durchsuchen";
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
        /* Dark Theme Grundstile */
        :root {
            --dark-bg: #121212;
            --dark-surface: #1e1e1e;
            --dark-surface-light: #2d2d2d;
            --dark-surface-lighter: #363636;
            --dark-primary: #6200ee;
            --dark-primary-light: #7c4dff;
            --dark-primary-dark: #4b00ca;
            --dark-secondary: #03dac6;
            --dark-error: #cf6679;
            --dark-success: #4caf50;
            --dark-warning: #ff9800;
            --dark-info: #2196f3;
            --dark-text-primary: rgba(255, 255, 255, 0.87);
            --dark-text-secondary: rgba(255, 255, 255, 0.6);
            --dark-text-hint: rgba(255, 255, 255, 0.38);
            --dark-border: rgba(255, 255, 255, 0.12);
        }
        
        body {
            background-color: var(--dark-bg);
            color: var(--dark-text-primary);
            font-family: 'Roboto', sans-serif;
        }
        
        /* Fix für weiße Stellen */
        .card-footer.bg-white {
            background-color: var(--dark-surface-light) !important;
            border-top: 1px solid var(--dark-border);
        }
        
        .card-header.bg-light {
            background-color: var(--dark-surface-light) !important;
            color: var(--dark-text-primary);
        }
        
        /* Unit-Card Styling */
        .card {
            background-color: var(--dark-surface);
            border: 1px solid var(--dark-border);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card-body {
            background-color: var(--dark-surface);
        }
        
        /* Inline-Styles für Multi-Test-Funktionalität */
        .multi-test-bar {
            background-color: var(--dark-surface-light);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            display: none; /* Standardmäßig ausgeblendet */
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .multi-test-badge {
            display: inline-block;
            background-color: var(--dark-primary-light);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            margin: 5px;
            font-size: 0.9rem;
            animation: badgePop 0.3s ease;
        }
        
        @keyframes badgePop {
            0% { transform: scale(0.8); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .multi-test-badge .remove-unit {
            margin-left: 5px;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.2s;
        }
        
        .multi-test-badge .remove-unit:hover {
            opacity: 1;
        }
        
        .direction-select {
            background-color: var(--dark-surface);
            color: var(--dark-text-primary);
            border: 1px solid var(--dark-border);
            padding: 5px 10px;
            border-radius: 5px;
        }
        
        /* Auswahlbutton für Units (jetzt unten rechts) */
        .select-for-test {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background-color: var(--dark-primary-dark);
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            color: white;
            margin-left: 8px;
            display: none; /* Standardmäßig ausgeblendet */
        }
        
        .select-for-test:hover {
            background-color: var(--dark-primary);
            transform: scale(1.1);
        }
        
        .select-for-test.selected {
            background-color: var(--dark-secondary);
        }
        
        .select-for-test i {
            color: white;
            font-size: 14px;
        }
        
        /* Multi-Test-Button */
        .multi-test-mode-btn {
            background-color: var(--dark-primary-dark);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .multi-test-mode-btn:hover {
            background-color: var(--dark-primary-light);
            transform: translateY(-2px);
        }
        
        .multi-test-mode-btn.active {
            background-color: var(--dark-secondary);
        }
        
        /* Unit-Item Styling */
        .unit-item {
            position: relative;
            transition: all 0.3s ease;
        }
        
        .unit-item.selected-for-test {
            transform: translateY(-3px);
        }
        
        .unit-item.selected-for-test .card {
            box-shadow: 0 0 0 2px var(--dark-primary-light), 0 6px 12px rgba(0, 0, 0, 0.3);
        }
        
        /* Buttons im Dark Theme */
        .btn-light {
            background-color: var(--dark-surface-light);
            border-color: var(--dark-border);
            color: var(--dark-text-primary);
        }
        
        .btn-light:hover, .btn-light:focus, .btn-light.active {
            background-color: var(--dark-surface-lighter);
            border-color: var(--dark-border);
            color: var(--dark-primary-light);
        }
        
        .btn-outline-secondary {
            color: var(--dark-text-secondary);
            border-color: var(--dark-text-secondary);
        }
        
        .btn-outline-secondary:hover {
            background-color: var(--dark-surface-light);
            color: var(--dark-text-primary);
        }
        
        .card p, .card h5, .card small {
            color: var(--dark-text-primary);
        }
        
        .card .text-muted {
            color: var(--dark-text-secondary) !important;
        }
        
        /* Button-Gruppe in der Fußzeile der Karte */
        .card-footer .btn-group {
            display: flex;
            align-items: center;
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
                        <li class="breadcrumb-item active">Einheiten</li>
                    </ol>
                </nav>
                
                <div class="card mb-4 animate__animated animate__fadeIn">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2><i class="fas fa-book"></i> Einheiten</h2>
                            <div class="d-flex align-items-center">
                                <button id="multi-test-toggle" class="multi-test-mode-btn me-3">
                                    <i class="fas fa-tasks"></i> Multi-Test
                                </button>
                                <div class="btn-group" role="group">
                                    <a href="?mode=flashcards" class="btn btn-light <?= $mode === 'flashcards' ? 'active' : '' ?>">
                                        <i class="fas fa-clone"></i> Karteikarten
                                    </a>
                                    <a href="?mode=test" class="btn btn-light <?= $mode === 'test' ? 'active' : '' ?>">
                                        <i class="fas fa-pencil-alt"></i> Mini-Test
                                    </a>
                                    <a href="?mode=kahoot" class="btn btn-light <?= $mode === 'kahoot' ? 'active' : '' ?>">
                                        <i class="fas fa-gamepad"></i> Mini-Kahoot
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Multi-Test-Auswahl-Leiste -->
                        <div id="multi-test-bar" class="multi-test-bar">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Ausgewählte Units für den Multi-Test:</h5>
                                    <div id="selected-units-container">
                                        <p id="no-units-selected">Keine Units ausgewählt. Klicke auf + um Units hinzuzufügen.</p>
                                    </div>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <div class="mb-2">
                                        <label for="test-direction" class="me-2">Richtung:</label>
                                        <select id="test-direction" class="direction-select">
                                            <option value="de_en">Deutsch → Englisch</option>
                                            <option value="en_de">Englisch → Deutsch</option>
                                        </select>
                                    </div>
                                    <button id="start-multi-test" class="btn btn-success" disabled>
                                        <i class="fas fa-play"></i> Multi-Test starten
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="text" id="unit-search" class="form-control" placeholder="Einheiten durchsuchen...">
                                    <button class="btn btn-outline-secondary" type="button">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (empty($publicUnits)): ?>
                        <div class="alert alert-info">
                            <p>Es wurden keine öffentlichen Einheiten gefunden.</p>
                            <?php if (isAdmin()): ?>
                            <p>Als Administrator kannst du im <a href="admin.php">Admin-Bereich</a> neue Einheiten erstellen.</p>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="row" id="units-container">
                            <?php foreach ($publicUnits as $unit): ?>
                            <div class="col-md-4 mb-4 unit-item" data-unit-id="<?= $unit['unit_id'] ?>" data-unit-name="<?= h($unit['unit_name']) ?>">
                                <div class="card h-100">
                                    <div class="card-header bg-light">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0"><?= h($unit['unit_name']) ?></h5>
                                            <?php 
                                            $isFavorite = in_array($unit['unit_id'], $favoriteIds);
                                            ?>
                                            <form method="post" action="browse_units.php<?= $mode !== 'flashcards' ? '?mode=' . $mode : '' ?>">
                                                <input type="hidden" name="unit_id" value="<?= $unit['unit_id'] ?>">
                                                <?php if ($isFavorite): ?>
                                                <input type="hidden" name="action" value="unfavorite">
                                                <button type="submit" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-star"></i>
                                                </button>
                                                <?php else: ?>
                                                <input type="hidden" name="action" value="favorite">
                                                <button type="submit" class="btn btn-sm btn-outline-warning">
                                                    <i class="far fa-star"></i>
                                                </button>
                                                <?php endif; ?>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text"><?= h(substr($unit['description'], 0, 100)) ?>...</p>
                                        <p class="text-muted">
                                            <small>Erstellt von: <?= h($unit['username']) ?></small>
                                        </p>
                                    </div>
                                    <div class="card-footer bg-white">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <?php if ($mode === 'flashcards'): ?>
                                                <a href="flashcards.php?unit_id=<?= $unit['unit_id'] ?>" class="btn btn-primary">
                                                    <i class="fas fa-clone"></i> Karteikarten
                                                </a>
                                                <?php elseif ($mode === 'test'): ?>
                                                <a href="mini_test.php?unit_id=<?= $unit['unit_id'] ?>" class="btn btn-success">
                                                    <i class="fas fa-pencil-alt"></i> Mini-Test starten
                                                </a>
                                                <?php elseif ($mode === 'kahoot'): ?>
                                                <a href="mini_kahoot.php?unit_id=<?= $unit['unit_id'] ?>" class="btn btn-danger">
                                                    <i class="fas fa-gamepad"></i> Mini-Kahoot starten
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                            <div class="d-flex">
                                                <a href="unit_details.php?id=<?= $unit['unit_id'] ?>" class="btn btn-outline-secondary">
                                                    <i class="fas fa-info-circle"></i> Details
                                                </a>
                                                <button type="button" class="select-for-test" data-unit-id="<?= $unit['unit_id'] ?>">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
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
        // Multi-Test Funktionalität
        document.addEventListener('DOMContentLoaded', function() {
            const multiTestToggle = document.getElementById('multi-test-toggle');
            const multiTestBar = document.getElementById('multi-test-bar');
            const startMultiTestBtn = document.getElementById('start-multi-test');
            const testDirectionSelect = document.getElementById('test-direction');
            const selectButtons = document.querySelectorAll('.select-for-test');
            const unitItems = document.querySelectorAll('.unit-item');
            const selectedUnitsContainer = document.getElementById('selected-units-container');
            const noUnitsSelected = document.getElementById('no-units-selected');
            
            // Einheitliches dunkles Design erzwingen
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
            
            // Ausgewählte Units speichern
            const selectedUnits = new Map();
            
            // Multi-Test Modus umschalten
            if (multiTestToggle) {
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
            }
            
            // Unit für Test auswählen/abwählen
            selectButtons.forEach(btn => {
                const unitId = btn.dataset.unitId;
                const unitItem = document.querySelector(`.unit-item[data-unit-id="${unitId}"]`);
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
                    
                    // Aktualisiere Anzeige und Start-Button
                    updateSelectedUnitsDisplay();
                });
            });
            
            // Ausgewählte Units anzeigen
            function updateSelectedUnitsDisplay() {
                // Start-Button aktivieren/deaktivieren
                startMultiTestBtn.disabled = selectedUnits.size === 0;
                
                // "Keine Units ausgewählt" Meldung anzeigen/ausblenden
                if (selectedUnits.size === 0) {
                    noUnitsSelected.style.display = 'block';
                    selectedUnitsContainer.querySelectorAll('.multi-test-badge').forEach(badge => {
                        badge.remove();
                    });
                    return;
                }
                
                // "Keine Units ausgewählt" Meldung ausblenden
                noUnitsSelected.style.display = 'none';
                
                // Vorhandene Badges entfernen
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
            
            // Multi-Test starten
            if (startMultiTestBtn) {
                startMultiTestBtn.addEventListener('click', function() {
                    if (selectedUnits.size === 0) return;
                    
                    // URL mit ausgewählten Unit-IDs und Richtung erstellen
                    const unitIds = Array.from(selectedUnits.keys());
                    const direction = testDirectionSelect.value;
                    
                    let url = 'mini_test.php?';
                    unitIds.forEach(id => {
                        url += `unit_id[]=${id}&`;
                    });
                    url += `direction=${direction}`;
                    
                    // Zu Mini-Test-Seite navigieren
                    window.location.href = url;
                });
            }
            
            // Suchfunktion für Einheiten
            const searchInput = document.getElementById('unit-search');
            const unitsContainer = document.getElementById('units-container');
            const unitItemsForSearch = unitsContainer ? Array.from(unitsContainer.getElementsByClassName('unit-item')) : [];
            
            if (searchInput && unitItemsForSearch.length > 0) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase().trim();
                    
                    unitItemsForSearch.forEach(function(item) {
                        const title = item.querySelector('.card-header h5').textContent.toLowerCase();
                        const description = item.querySelector('.card-text').textContent.toLowerCase();
                        
                        if (title.includes(searchTerm) || description.includes(searchTerm)) {
                            item.style.display = '';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            }
        });
    </script>
    <script src="sprachapp.js"></script>
</body>
</html>