<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
requireLogin();

// Benutzer-ID abrufen
$userId = $_SESSION['user_id'];

// Nachricht für erfolgreiche Änderungen
$successMessage = '';
$errorMessage = '';

// Datenbankverbindung herstellen
$conn = connectDB();

// Prüfen, ob die Tabelle user_settings existiert, wenn nicht, erstellen
$tableCheckResult = $conn->query("SHOW TABLES LIKE 'user_settings'");
if ($tableCheckResult->num_rows == 0) {
    // Tabelle existiert nicht, also erstellen wir sie
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS user_settings (
            user_id INT NOT NULL PRIMARY KEY,
            daily_goal INT NOT NULL DEFAULT 10,
            default_direction VARCHAR(5) NOT NULL DEFAULT 'de_en',
            notifications_enabled TINYINT(1) NOT NULL DEFAULT 1,
            dark_mode_enabled TINYINT(1) NOT NULL DEFAULT 1,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )
    ";
    
    $conn->query($createTableSQL);
    
    // Standard-Eintrag für den aktuellen Benutzer erstellen
    $insertDefaultSettings = $conn->prepare("
        INSERT IGNORE INTO user_settings (user_id, daily_goal, default_direction, notifications_enabled, dark_mode_enabled)
        VALUES (?, 10, 'de_en', 1, 1)
    ");
    
    $insertDefaultSettings->bind_param("i", $userId);
    $insertDefaultSettings->execute();
}

// Benutzerdaten abrufen
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Benutzer nicht gefunden
    header('Location: logout.php');
    exit;
}

$userData = $result->fetch_assoc();

// Formular zur Bearbeitung des Profils wurde abgeschickt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Prüfe, ob Benutzername und E-Mail angegeben wurden
    if (empty($username) || empty($email)) {
        $errorMessage = 'Benutzername und E-Mail sind erforderlich.';
    } else {
        // Prüfe, ob Benutzername bereits verwendet wird (außer vom aktuellen Benutzer)
        $checkStmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
        $checkStmt->bind_param("si", $username, $userId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $errorMessage = 'Dieser Benutzername wird bereits verwendet.';
        } else {
            // Prüfe, ob E-Mail bereits verwendet wird (außer vom aktuellen Benutzer)
            $checkStmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $checkStmt->bind_param("si", $email, $userId);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                $errorMessage = 'Diese E-Mail-Adresse wird bereits verwendet.';
            } else {
                // Wenn alle Prüfungen bestanden wurden und kein neues Passwort gesetzt werden soll
                if (empty($newPassword)) {
                    // Nur Benutzername und E-Mail aktualisieren
                    $updateStmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE user_id = ?");
                    $updateStmt->bind_param("ssi", $username, $email, $userId);
                    
                    if ($updateStmt->execute()) {
                        $successMessage = 'Dein Profil wurde erfolgreich aktualisiert.';
                        // Session-Benutzername aktualisieren
                        $_SESSION['username'] = $username;
                        // Benutzerdaten neu laden
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $userData = $result->fetch_assoc();
                    } else {
                        $errorMessage = 'Fehler beim Aktualisieren des Profils: ' . $conn->error;
                    }
                } else {
                    // Prüfe, ob das aktuelle Passwort korrekt ist
                    if (empty($currentPassword)) {
                        $errorMessage = 'Das aktuelle Passwort muss angegeben werden, um das Passwort zu ändern.';
                    } else if ($newPassword !== $confirmPassword) {
                        $errorMessage = 'Die neuen Passwörter stimmen nicht überein.';
                    } else if (strlen($newPassword) < 8) {
                        $errorMessage = 'Das neue Passwort muss mindestens 8 Zeichen lang sein.';
                    } else {
                        // Prüfe, ob das aktuelle Passwort korrekt ist
                        if (password_verify($currentPassword, $userData['password_hash'])) {
                            // Passwort-Hash erstellen
                            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                            
                            // Benutzername, E-Mail und Passwort aktualisieren
                            $updateStmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password_hash = ? WHERE user_id = ?");
                            $updateStmt->bind_param("sssi", $username, $email, $newPasswordHash, $userId);
                            
                            if ($updateStmt->execute()) {
                                $successMessage = 'Dein Profil und Passwort wurden erfolgreich aktualisiert.';
                                // Session-Benutzername aktualisieren
                                $_SESSION['username'] = $username;
                                // Benutzerdaten neu laden
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $userData = $result->fetch_assoc();
                            } else {
                                $errorMessage = 'Fehler beim Aktualisieren des Profils: ' . $conn->error;
                            }
                        } else {
                            $errorMessage = 'Das aktuelle Passwort ist nicht korrekt.';
                        }
                    }
                }
            }
        }
    }
}

// Formular für Lerneinstellungen wurde abgeschickt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_learning_settings'])) {
    $dailyGoal = (int)($_POST['daily_goal'] ?? 10);
    $defaultDirection = $_POST['default_direction'] ?? 'de_en';
    $notificationsEnabled = isset($_POST['notifications_enabled']) ? 1 : 0;
    
    // Prüfe, ob Benutzereinstellungen bereits existieren
    $checkSettingsStmt = $conn->prepare("SELECT user_id FROM user_settings WHERE user_id = ?");
    $checkSettingsStmt->bind_param("i", $userId);
    $checkSettingsStmt->execute();
    $settingsResult = $checkSettingsStmt->get_result();
    
    if ($settingsResult->num_rows > 0) {
        // Einstellungen aktualisieren
        $updateSettingsStmt = $conn->prepare("
            UPDATE user_settings 
            SET daily_goal = ?, default_direction = ?, notifications_enabled = ?
            WHERE user_id = ?
        ");
        $updateSettingsStmt->bind_param("isii", $dailyGoal, $defaultDirection, $notificationsEnabled, $userId);
        
        if ($updateSettingsStmt->execute()) {
            $successMessage = 'Deine Lerneinstellungen wurden erfolgreich aktualisiert.';
        } else {
            $errorMessage = 'Fehler beim Aktualisieren der Lerneinstellungen: ' . $conn->error;
        }
    } else {
        // Neue Einstellungen einfügen
        $insertSettingsStmt = $conn->prepare("
            INSERT INTO user_settings (user_id, daily_goal, default_direction, notifications_enabled)
            VALUES (?, ?, ?, ?)
        ");
        $insertSettingsStmt->bind_param("isii", $userId, $dailyGoal, $defaultDirection, $notificationsEnabled);
        
        if ($insertSettingsStmt->execute()) {
            $successMessage = 'Deine Lerneinstellungen wurden erfolgreich gespeichert.';
        } else {
            $errorMessage = 'Fehler beim Speichern der Lerneinstellungen: ' . $conn->error;
        }
    }
}

// Formular für Design-Einstellungen wurde abgeschickt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_design_settings'])) {
    $darkModeEnabled = isset($_POST['dark_mode_enabled']) ? 1 : 0;
    
    // Prüfe, ob Benutzereinstellungen bereits existieren
    $checkSettingsStmt = $conn->prepare("SELECT user_id FROM user_settings WHERE user_id = ?");
    $checkSettingsStmt->bind_param("i", $userId);
    $checkSettingsStmt->execute();
    $settingsResult = $checkSettingsStmt->get_result();
    
    if ($settingsResult->num_rows > 0) {
        // Einstellungen aktualisieren
        $updateSettingsStmt = $conn->prepare("
            UPDATE user_settings 
            SET dark_mode_enabled = ?
            WHERE user_id = ?
        ");
        $updateSettingsStmt->bind_param("ii", $darkModeEnabled, $userId);
        
        if ($updateSettingsStmt->execute()) {
            $successMessage = 'Deine Design-Einstellungen wurden erfolgreich aktualisiert.';
        } else {
            $errorMessage = 'Fehler beim Aktualisieren der Design-Einstellungen: ' . $conn->error;
        }
    } else {
        // Neue Einstellungen einfügen mit Standardwerten
        $insertSettingsStmt = $conn->prepare("
            INSERT INTO user_settings (user_id, dark_mode_enabled)
            VALUES (?, ?)
        ");
        $insertSettingsStmt->bind_param("ii", $userId, $darkModeEnabled);
        
        if ($insertSettingsStmt->execute()) {
            $successMessage = 'Deine Design-Einstellungen wurden erfolgreich gespeichert.';
        } else {
            $errorMessage = 'Fehler beim Speichern der Design-Einstellungen: ' . $conn->error;
        }
    }
}

// Benutzereinstellungen abrufen
$userSettings = [
    'daily_goal' => 10,
    'default_direction' => 'de_en',
    'notifications_enabled' => 1,
    'dark_mode_enabled' => 1
];

$settingsStmt = $conn->prepare("SELECT * FROM user_settings WHERE user_id = ?");
if ($settingsStmt) {
    $settingsStmt->bind_param("i", $userId);
    $settingsStmt->execute();
    $settingsResult = $settingsStmt->get_result();
    
    if ($settingsResult->num_rows > 0) {
        $settingsData = $settingsResult->fetch_assoc();
        $userSettings = array_merge($userSettings, $settingsData);
    }
}

$conn->close();

$pageTitle = "Einstellungen";
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title><?= $pageTitle ?> - SprachApp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="css/dark-theme.css">
    <style>
        /* Zusätzliche Stile für die Einstellungsseite */
        .settings-section {
            margin-bottom: 2rem;
        }
        
        .form-check-input:checked {
            background-color: var(--dark-primary);
            border-color: var(--dark-primary);
        }
        
        .nav-pills .nav-link {
            color: var(--dark-text-primary);
            background-color: var(--dark-surface-light);
            margin-bottom: 0.5rem;
        }
        
        .nav-pills .nav-link.active {
            background-color: var(--dark-primary);
            color: white;
        }
        
        .nav-pills .nav-link:hover:not(.active) {
            background-color: var(--dark-surface-lighter);
        }
        
        .tab-pane {
            padding: 1.5rem;
            background-color: var(--dark-surface);
            border-radius: 0.5rem;
        }
        
        @media (max-width: 767.98px) {
            .nav-pills {
                display: flex;
                overflow-x: auto;
                white-space: nowrap;
                margin-bottom: 1rem;
            }
            
            .nav-pills .nav-link {
                margin-right: 0.5rem;
                margin-bottom: 0;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-3 mt-md-4">
        <div class="row">
            <div class="col-md-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Startseite</a></li>
                        <li class="breadcrumb-item active">Einstellungen</li>
                    </ol>
                </nav>
                
                <?php if ($successMessage): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i> <?= h($successMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
                </div>
                <?php endif; ?>
                
                <?php if ($errorMessage): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i> <?= h($errorMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
                </div>
                <?php endif; ?>
                
                <div class="card mb-4 animate__animated animate__fadeIn">
                    <div class="card-header bg-primary text-white">
                        <h2><i class="fas fa-cog"></i> Einstellungen</h2>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <ul class="nav nav-pills flex-column" id="settingsTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="true">
                                            <i class="fas fa-user-circle me-2"></i> Profil
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="learning-tab" data-bs-toggle="tab" data-bs-target="#learning" type="button" role="tab" aria-controls="learning" aria-selected="false">
                                            <i class="fas fa-graduation-cap me-2"></i> Lerneinstellungen
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="design-tab" data-bs-toggle="tab" data-bs-target="#design" type="button" role="tab" aria-controls="design" aria-selected="false">
                                            <i class="fas fa-paint-brush me-2"></i> Design
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="data-tab" data-bs-toggle="tab" data-bs-target="#data" type="button" role="tab" aria-controls="data" aria-selected="false">
                                            <i class="fas fa-database me-2"></i> Daten & Datenschutz
                                        </button>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-9">
                                <div class="tab-content" id="settingsTabsContent">
                                    <!-- Profil Tab -->
                                    <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                                        <form method="post" action="settings.php">
                                            <div class="settings-section">
                                                <h3 class="mb-3">Benutzerprofil</h3>
                                                
                                                <div class="mb-3">
                                                    <label for="username" class="form-label">Benutzername</label>
                                                    <input type="text" class="form-control" id="username" name="username" value="<?= h($userData['username']) ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="email" class="form-label">E-Mail-Adresse</label>
                                                    <input type="email" class="form-control" id="email" name="email" value="<?= h($userData['email']) ?>" required>
                                                </div>
                                            </div>
                                            
                                            <div class="settings-section">
                                                <h3 class="mb-3">Passwort ändern</h3>
                                                <p class="text-muted mb-3">Lass die Passwortfelder leer, wenn du das Passwort nicht ändern möchtest.</p>
                                                
                                                <div class="mb-3">
                                                    <label for="current_password" class="form-label">Aktuelles Passwort</label>
                                                    <input type="password" class="form-control" id="current_password" name="current_password">
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="new_password" class="form-label">Neues Passwort</label>
                                                    <input type="password" class="form-control" id="new_password" name="new_password" minlength="8">
                                                    <div class="form-text">Mindestens 8 Zeichen lang</div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="confirm_password" class="form-label">Neues Passwort bestätigen</label>
                                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8">
                                                </div>
                                            </div>
                                            
                                            <button type="submit" name="update_profile" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i> Änderungen speichern
                                            </button>
                                        </form>
                                    </div>
                                    
                                    <!-- Lerneinstellungen Tab -->
                                    <div class="tab-pane fade" id="learning" role="tabpanel" aria-labelledby="learning-tab">
                                        <form method="post" action="settings.php">
                                            <div class="settings-section">
                                                <h3 class="mb-3">Lernziele</h3>
                                                
                                                <div class="mb-3">
                                                    <label for="daily_goal" class="form-label">Tägliches Lernziel (Anzahl Vokabeln)</label>
                                                    <input type="number" class="form-control" id="daily_goal" name="daily_goal" min="1" max="100" value="<?= $userSettings['daily_goal'] ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="settings-section">
                                                <h3 class="mb-3">Lernrichtung</h3>
                                                
                                                <div class="mb-3">
                                                    <label for="default_direction" class="form-label">Standardrichtung</label>
                                                    <select class="form-control" id="default_direction" name="default_direction">
                                                        <option value="de_en" <?= $userSettings['default_direction'] === 'de_en' ? 'selected' : '' ?>>
                                                            Deutsch → Englisch
                                                        </option>
                                                        <option value="en_de" <?= $userSettings['default_direction'] === 'en_de' ? 'selected' : '' ?>>
                                                            Englisch → Deutsch
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="settings-section">
                                                <h3 class="mb-3">Benachrichtigungen</h3>
                                                
                                                <div class="form-check form-switch mb-3">
                                                    <input class="form-check-input" type="checkbox" id="notifications_enabled" name="notifications_enabled" <?= $userSettings['notifications_enabled'] ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="notifications_enabled">
                                                        Erinnerungen aktivieren
                                                    </label>
                                                    <div class="form-text">Du erhältst Erinnerungen, wenn du dein tägliches Ziel noch nicht erreicht hast.</div>
                                                </div>
                                            </div>
                                            
                                            <button type="submit" name="update_learning_settings" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i> Einstellungen speichern
                                            </button>
                                        </form>
                                    </div>
                                    
                                    <!-- Design Tab -->
                                    <div class="tab-pane fade" id="design" role="tabpanel" aria-labelledby="design-tab">
                                        <form method="post" action="settings.php">
                                            <div class="settings-section">
                                                <h3 class="mb-3">Erscheinungsbild</h3>
                                                
                                                <div class="form-check form-switch mb-3">
                                                    <input class="form-check-input" type="checkbox" id="dark_mode_enabled" name="dark_mode_enabled" <?= $userSettings['dark_mode_enabled'] ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="dark_mode_enabled">
                                                        Dunkles Design aktivieren
                                                    </label>
                                                    <div class="form-text">Wechsle zwischen dunklem und hellem Design.</div>
                                                </div>
                                            </div>
                                            
                                            <button type="submit" name="update_design_settings" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i> Design-Einstellungen speichern
                                            </button>
                                        </form>
                                    </div>
                                    
                                    <!-- Daten & Datenschutz Tab -->
                                    <div class="tab-pane fade" id="data" role="tabpanel" aria-labelledby="data-tab">
                                        <div class="settings-section">
                                            <h3 class="mb-3">Deine Daten</h3>
                                            
                                            <p>Hier kannst du deine gespeicherten Daten verwalten.</p>
                                            
                                            <div class="d-grid gap-2 d-md-block">
                                                <a href="export_data.php" class="btn btn-outline-primary mb-2 mb-md-0">
                                                    <i class="fas fa-file-export me-2"></i> Daten exportieren
                                                </a>
                                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                                    <i class="fas fa-trash-alt me-2"></i> Konto löschen
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="settings-section">
                                            <h3 class="mb-3">Datenschutz</h3>
                                            
                                            <p>Wir respektieren deine Privatsphäre und schützen deine Daten gemäß unserer <a href="privacy_policy.php">Datenschutzerklärung</a>.</p>
                                            
                                            <p>Wenn du Fragen oder Anliegen hast, kontaktiere uns bitte unter <a href="mailto:datenschutz@sprachapp.de">datenschutz@sprachapp.de</a>.</p>
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
    
    <!-- Modal für Kontolöschung -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">Konto löschen</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                <div class="modal-body">
                    <p>Bist du sicher, dass du dein Konto löschen möchtest? Diese Aktion kann nicht rückgängig gemacht werden.</p>
                    <p>Alle deine Daten werden dauerhaft gelöscht, einschließlich:</p>
                    <ul>
                        <li>Profildaten und Einstellungen</li>
                        <li>Lernfortschritt und Statistiken</li>
                        <li>Favorisierte Einheiten</li>
                        <li>Selbst erstellte Einheiten und Vokabeln</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <a href="delete_account.php" class="btn btn-danger">Konto endgültig löschen</a>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="mt-4 py-3 py-md-4" style="background-color: var(--dark-surface); border-top: 1px solid var(--dark-border);">
        <div class="container">
            <div class="text-center">
                <p>&copy; <?= date('Y') ?> SprachApp. Alle Rechte vorbehalten.</p>
            </div>
        </div>
    </footer>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.3/js/bootstrap.bundle.min.js"></script>
    <script>
        // JavaScript für Einstellungsseite
        document.addEventListener('DOMContentLoaded', function() {
            // Passwortüberprüfung
            const newPasswordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            
            function checkPasswords() {
                if (confirmPasswordInput.value && newPasswordInput.value !== confirmPasswordInput.value) {
                    confirmPasswordInput.setCustomValidity('Die Passwörter stimmen nicht überein.');
                } else {
                    confirmPasswordInput.setCustomValidity('');
                }
            }
            
            if (newPasswordInput && confirmPasswordInput) {
                newPasswordInput.addEventListener('change', checkPasswords);
                confirmPasswordInput.addEventListener('keyup', checkPasswords);
            }
            
            // Tabs per URL anzeigen
            function activateTabFromHash() {
                const hash = window.location.hash;
                if (hash) {
                    const tabId = hash.substring(1);
                    const tabElement = document.getElementById(tabId + '-tab');
                    if (tabElement) {
                        const tab = new bootstrap.Tab(tabElement);
                        tab.show();
                    }
                }
            }
            
            // Hash im URL überwachen
            window.addEventListener('hashchange', activateTabFromHash);
            
            // Initialen Tab aktivieren
            activateTabFromHash();
            
            // Bei Klick auf Tab-Links auch URL-Hash aktualisieren
            document.querySelectorAll('.nav-link').forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabId = this.id.replace('-tab', '');
                    window.location.hash = tabId;
                });
            });
            
            // Formular-Validierung für Tägliches Lernziel
            const dailyGoalInput = document.getElementById('daily_goal');
            if (dailyGoalInput) {
                dailyGoalInput.addEventListener('input', function() {
                    const value = parseInt(this.value);
                    if (value < 1) {
                        this.value = 1;
                    } else if (value > 100) {
                        this.value = 100;
                    }
                });
            }
        });