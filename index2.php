<?php
// Start the session
session_start();

// Check if user is logged in
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    // Redirect to login page if not logged in
    $_SESSION['err'] = "Bitte melden Sie sich an, um auf diese Seite zuzugreifen.";
    header("Location: login.php");
    exit();
}

// Get user info
$username = $_SESSION['username'];
$role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SprachApp - Übungsbereich</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .content {
            flex: 1;
        }
        .feature-card {
            border-radius: 0.5rem;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .feature-card .card-img-top {
            height: 160px;
            object-fit: cover;
        }
        .card-footer {
            background-color: transparent;
            border-top: none;
            padding-top: 0;
        }
        footer {
            margin-top: auto;
            padding: 1rem 0;
            background-color: #212529;
            color: white;
        }
        .admin-section, .teacher-section {
            display: none;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index2.php">SprachApp</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index2.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="einheiten.php">Einheiten</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="miniTest.php">Grammatiktrainer</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="konjugationstrainer.php">MultiChoice</a>
                    </li>
                    <li class="nav-item teacher-section">
                        <a class="nav-link" href="schueler_verwalten.php">Schüler verwalten</a>
                    </li>
                    <li class="nav-item admin-section">
                        <a class="nav-link" href="admin_panel.php">Admin-Panel</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="text-white me-3">Hallo, <?php echo htmlspecialchars($username); ?> (<?php echo htmlspecialchars($role); ?>)</span>
                    <a href="logout.php" class="btn btn-outline-light">Abmelden</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container content py-4">
        <div class="row mb-4">
            <div class="col">
                <h2>Willkommen zurück, <?php echo htmlspecialchars($username); ?>!</h2>
                <p>Wählen Sie unten eine der Übungsoptionen aus, um Ihre Sprachkenntnisse zu verbessern.</p>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card feature-card h-100">
                    <img src="https://via.placeholder.com/800x450?text=Einheiten+%C3%BCben" class="card-img-top" alt="Einheiten üben">
                    <div class="card-body">
                        <h5 class="card-title">Einheiten üben</h5>
                        <p class="card-text">Lernen Sie mit thematisch organisierten Lerneinheiten, die speziell auf Ihr Niveau zugeschnitten sind.</p>
                        <p class="card-text">
                            <ul>
                                <li>Themenbasierte Lektionen</li>
                                <li>Interaktive Übungen</li>
                                <li>Fortschrittsverfolgung</li>
                            </ul>
                        </p>
                    </div>
                    <div class="card-footer">
                        <a href="einheiten.php" class="btn btn-primary w-100">Zu den Einheiten</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card feature-card h-100">
                    <img src="https://via.placeholder.com/800x450?text=Grammatiktrainer" class="card-img-top" alt="Grammatiktrainer">
                    <div class="card-body">
                        <h5 class="card-title">Grammatiktrainer</h5>
                        <p class="card-text">Verbessern Sie Ihre Grammatikkenntnisse mit gezielten Übungen zu Zeiten, Präpositionen und mehr.</p>
                        <p class="card-text">
                            <ul>
                                <li>Personalisierte Übungen</li>
                                <li>Direktes Feedback</li>
                                <li>Verschiedene Schwierigkeitsgrade</li>
                            </ul>
                        </p>
                    </div>
                    <div class="card-footer">
                        <a href="miniTest.php" class="btn btn-primary w-100">Grammatik üben</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card feature-card h-100">
                    <img src="https://via.placeholder.com/800x450?text=MultiChoice" class="card-img-top" alt="MultiChoice">
                    <div class="card-body">
                        <h5 class="card-title">MultiChoice</h5>
                        <p class="card-text">Testen Sie Ihr Wissen mit unterhaltsamen Multiple-Choice-Fragen zu Vokabeln und Sprachverständnis.</p>
                        <p class="card-text">
                            <ul>
                                <li>Vielfältige Fragetypen</li>
                                <li>Punktesystem</li>
                                <li>Lernstatistiken</li>
                            </ul>
                        </p>
                    </div>
                    <div class="card-footer">
                        <a href="konjugationstrainer.php" class="btn btn-primary w-100">MultiChoice starten</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Bereich nur für Lehrer -->
        <div class="teacher-section mt-5">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0">Lehrer-Bereich</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Schülerverwaltung</h5>
                            <p>Verwalten Sie Ihre Schüler, sehen Sie deren Fortschritte ein und erstellen Sie personalisierte Übungen.</p>
                            <a href="schueler_verwalten.php" class="btn btn-info">Schüler verwalten</a>
                        </div>
                        <div class="col-md-6">
                            <h5>Übungen erstellen</h5>
                            <p>Erstellen Sie eigene Übungen und Tests für Ihre Kurse und Schüler.</p>
                            <a href="uebungen_erstellen.php" class="btn btn-info">Übungen erstellen</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Bereich nur für Admins -->
        <div class="admin-section mt-5">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">Administrator-Bereich</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h5>Benutzerverwaltung</h5>
                            <p>Verwalten Sie alle Benutzerkonten der Plattform.</p>
                            <a href="benutzer_verwalten.php" class="btn btn-danger">Benutzer verwalten</a>
                        </div>
                        <div class="col-md-4">
                            <h5>Inhalte verwalten</h5>
                            <p>Bearbeiten und verwalten Sie Lerneinheiten und Übungen.</p>
                            <a href="inhalte_verwalten.php" class="btn btn-danger">Inhalte verwalten</a>
                        </div>
                        <div class="col-md-4">
                            <h5>System-Einstellungen</h5>
                            <p>Konfigurieren Sie die Plattform und sehen Sie Systemstatistiken ein.</p>
                            <a href="system_einstellungen.php" class="btn btn-danger">Einstellungen</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="text-center py-3">
        <div class="container">
            <p class="mb-0">&copy; 2025 SprachApp. Alle Rechte vorbehalten.</p>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    
    <script>
        // Script zum Anzeigen der rollenspezifischen Bereiche
        document.addEventListener('DOMContentLoaded', function() {
            var role = "<?php echo $role; ?>";
            
            if (role === 'lehrer' || role === 'admin') {
                // Lehrer-Bereiche anzeigen
                var teacherSections = document.querySelectorAll('.teacher-section');
                for (var i = 0; i < teacherSections.length; i++) {
                    teacherSections[i].style.display = 'block';
                }
            }
            
            if (role === 'admin') {
                // Admin-Bereiche anzeigen
                var adminSections = document.querySelectorAll('.admin-section');
                for (var i = 0; i < adminSections.length; i++) {
                    adminSections[i].style.display = 'block';
                }
            }
        });
    </script>
</body>
</html>