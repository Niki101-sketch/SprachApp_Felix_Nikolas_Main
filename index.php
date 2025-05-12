<?php
// Start the session
session_start();

// Check if user is already logged in
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    // Redirect to index2.php if already logged in
    header("Location: index2.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SprachApp - Sprachlernen leicht gemacht</title>
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
        .hero-section {
            background-color: #e9ecef;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: 0.5rem;
        }
        .feature-box {
            padding: 1.5rem;
            border-radius: 0.5rem;
            transition: transform 0.3s;
            margin-bottom: 2rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .feature-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #0d6efd;
        }
        footer {
            margin-top: auto;
            padding: 1rem 0;
            background-color: #212529;
            color: white;
        }
        .auth-buttons .btn {
            margin-left: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="#">SprachApp</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto auth-buttons">
                    <li class="nav-item">
                        <a href="login.php" class="btn btn-outline-primary">Login</a>
                    </li>
                    <li class="nav-item">
                        <a href="registrieren.php" class="btn btn-primary">Registrieren</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container content py-4">
        <div class="hero-section text-center p-5">
            <h1 class="display-4 fw-bold">Willkommen zur SprachApp</h1>
            <p class="lead">Die umfassende Plattform zum Erlernen und Verbessern Ihrer Sprachkenntnisse</p>
            <div class="mt-4">
                <a href="registrieren.php" class="btn btn-primary btn-lg">Jetzt registrieren</a>
                <a href="login.php" class="btn btn-outline-secondary btn-lg ms-2">Anmelden</a>
            </div>
        </div>
        
        <div class="row mb-5">
            <div class="col-12 text-center mb-4">
                <h2>Unsere Funktionen</h2>
                <p class="text-muted">Melden Sie sich an, um alle Funktionen nutzen zu können!</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="feature-box bg-white">
                    <div class="feature-icon">
                        <i class="bi bi-book"></i>
                    </div>
                    <h3>Einheiten üben</h3>
                    <p>Lernen Sie strukturiert mit unseren thematisch organisierten Lerneinheiten. Jede Einheit konzentriert sich auf bestimmte Sprachaspekte und ermöglicht es Ihnen, gezielt zu üben.</p>
                    <ul>
                        <li>Themenbezogene Lektionen</li>
                        <li>Stufenweiser Lernfortschritt</li>
                        <li>Interaktive Übungen</li>
                    </ul>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="feature-box bg-white">
                    <div class="feature-icon">
                        <i class="bi bi-pencil"></i>
                    </div>
                    <h3>Grammatiktrainer</h3>
                    <p>Verbessern Sie Ihre Grammatikkenntnisse mit unserem spezialisierten Grammatiktrainer. Üben Sie Zeiten, Präpositionen, Artikel und vieles mehr.</p>
                    <ul>
                        <li>Gezielte Grammatikübungen</li>
                        <li>Sofortiges Feedback</li>
                        <li>Verschiedene Schwierigkeitsgrade</li>
                    </ul>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="feature-box bg-white">
                    <div class="feature-icon">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <h3>MultiChoice</h3>
                    <p>Testen Sie Ihr Wissen mit verschiedenen Multiple-Choice-Fragen zu Vokabeln, Phrasen und Sprachverständnis. Eine unterhaltsame Art, Ihr Sprachverständnis zu überprüfen.</p>
                    <ul>
                        <li>Vielfältige Fragetypen</li>
                        <li>Punktesystem für Motivation</li>
                        <li>Lernfortschrittsverfolgung</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="row mt-5">
            <div class="col-12 text-center">
                <div class="card p-4 bg-light">
                    <h3>Bereit zum Lernen?</h3>
                    <p>Erstellen Sie ein Konto oder melden Sie sich an, um alle Funktionen der SprachApp zu nutzen.</p>
                    <div>
                        <a href="registrieren.php" class="btn btn-primary">Jetzt registrieren</a>
                        <a href="login.php" class="btn btn-outline-secondary ms-2">Anmelden</a>
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
</body>
</html>