<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
requireLogin();

// Benutzer-ID abrufen
$userId = $_SESSION['user_id'];

$errorMessage = '';
$successMessage = '';

// Formular zur Bestätigung der Kontolöschung wurde abgeschickt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $password = $_POST['password'] ?? '';
    
    if (empty($password)) {
        $errorMessage = 'Bitte gib dein Passwort ein, um die Löschung zu bestätigen.';
    } else {
        // Datenbankverbindung herstellen
        $conn = connectDB();
        
        // Benutzerpasswort überprüfen
        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $errorMessage = 'Benutzer nicht gefunden.';
        } else {
            $userData = $result->fetch_assoc();
            
            // Passwort überprüfen
            if (password_verify($password, $userData['password_hash'])) {
                // Beginne eine Transaktion, um sicherzustellen, dass alle Löschvorgänge erfolgreich sind
                $conn->begin_transaction();
                
                try {
                    // Benutzerfortschritt löschen
                    $stmt = $conn->prepare("DELETE FROM user_progress WHERE user_id = ?");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    
                    // Falsch beantwortete Vokabeln löschen
                    $stmt = $conn->prepare("DELETE FROM wrong_answers WHERE user_id = ?");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    
                    // Favoriten löschen
                    $stmt = $conn->prepare("DELETE FROM user_favorites WHERE user_id = ?");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    
                    // Benutzereinstellungen löschen
                    $stmt = $conn->prepare("DELETE FROM user_settings WHERE user_id = ?");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    
                    // Bestenliste-Einträge löschen
                    $stmt = $conn->prepare("DELETE FROM leaderboard WHERE user_id = ?");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    
                    // Von Benutzer erstellte Einheiten suchen
                    $stmt = $conn->prepare("SELECT unit_id FROM units WHERE created_by = ?");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    while ($unit = $result->fetch_assoc()) {
                        $unitId = $unit['unit_id'];
                        
                        // Vokabeln aus dieser Einheit löschen
                        $stmt = $conn->prepare("DELETE FROM vocabulary WHERE unit_id = ?");
                        $stmt->bind_param("i", $unitId);
                        $stmt->execute();
                    }
                    
                    // Vom Benutzer erstellte Einheiten löschen
                    $stmt = $conn->prepare("DELETE FROM units WHERE created_by = ?");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    
                    // Benutzer löschen
                    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    
                    // Transaktion bestätigen
                    $conn->commit();
                    
                    // Session zerstören
                    session_destroy();
                    
                    // Erfolgsmeldung setzen
                    $successMessage = 'Dein Konto wurde erfolgreich gelöscht. Du wirst in Kürze zur Startseite weitergeleitet.';
                } catch (Exception $e) {
                    // Bei einem Fehler die Transaktion zurückrollen
                    $conn->rollback();
                    $errorMessage = 'Fehler beim Löschen des Kontos: ' . $e->getMessage();
                }
            } else {
                $errorMessage = 'Das eingegebene Passwort ist falsch.';
            }
        }
        
        $conn->close();
    }
}

$pageTitle = "Konto löschen";
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
    <link rel="stylesheet" href="css/dark-theme.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-3 mt-md-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Startseite</a></li>
                        <li class="breadcrumb-item"><a href="settings.php">Einstellungen</a></li>
                        <li class="breadcrumb-item active">Konto löschen</li>
                    </ol>
                </nav>
                
                <?php if ($successMessage): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i> <?= h($successMessage) ?>
                </div>
                <div class="text-center mt-4">
                    <p>Du wirst in 5 Sekunden zur Startseite weitergeleitet.</p>
                    <a href="index.php" class="btn btn-primary">Sofort zur Startseite</a>
                </div>
                <script>
                    setTimeout(function() {
                        window.location.href = 'index.php';
                    }, 5000);
                </script>
                <?php else: ?>
                
                <div class="card mb-4 animate__animated animate__fadeIn">
                    <div class="card-header bg-danger text-white">
                        <h2><i class="fas fa-exclamation-triangle"></i> Konto löschen</h2>
                    </div>
                    <div class="card-body">
                        <?php if ($errorMessage): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle me-2"></i> <?= h($errorMessage) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
                        </div>
                        <?php endif; ?>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i> Warnung: Diese Aktion kann nicht rückgängig gemacht werden!
                        </div>
                        
                        <p>Du bist dabei, dein Konto zu löschen. Alle deine Daten werden dauerhaft gelöscht, einschließlich:</p>
                        
                        <ul>
                            <li>Dein Profil und deine Einstellungen</li>
                            <li>Dein Lernfortschritt und deine Statistiken</li>
                            <li>Deine favorisierten Einheiten</li>
                            <li>Alle von dir erstellten Einheiten und Vokabeln</li>
                        </ul>
                        
                        <p>Um fortzufahren, gib bitte dein Passwort ein und bestätige die Löschung:</p>
                        
                        <form method="post" action="delete_account.php">
                            <div class="mb-3">
                                <label for="password" class="form-label">Passwort</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="settings.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i> Zurück zu den Einstellungen
                                </a>
                                <button type="submit" name="confirm_delete" class="btn btn-danger">
                                    <i class="fas fa-trash-alt me-2"></i> Konto endgültig löschen
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
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
</body>
</html>