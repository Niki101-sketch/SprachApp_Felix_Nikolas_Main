<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SprachApp-Startseite</title>
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
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0.5rem;
        }
        .nav-item {
            margin: 0.5rem 0;
        }
        .nav-link {
            border-radius: 0.5rem;
            transition: all 0.3s;
        }
        .nav-link:hover {
            background-color: #0d6efd;
            color: white !important;
            transform: translateY(-3px);
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
    <!-- Navigation Bar für Login/Registrieren -->
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
            <p class="lead">Hier können Sie Ihre Sprachkenntnisse testen und verbessern.</p>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="text-center mb-4">Wählen Sie eine Option</h2>
                        <nav>
                            <ul class="list-unstyled">
                                <li class="nav-item">
                                    <a href="einheiten.php" class="nav-link d-block text-center p-3 mb-3 border text-dark text-decoration-none shadow-sm">
                                        <i class="bi bi-book me-2"></i>Einheiten üben
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="miniTest.php" class="nav-link d-block text-center p-3 mb-3 border text-dark text-decoration-none shadow-sm">
                                        <i class="bi bi-pencil me-2"></i>Grammatiktrainer
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="konjugationstrainer.php" class="nav-link d-block text-center p-3 mb-3 border text-dark text-decoration-none shadow-sm">
                                        <i class="bi bi-check2-circle me-2"></i>MultiChoice
                                    </a>
                                </li>
                            </ul>
                        </nav>
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