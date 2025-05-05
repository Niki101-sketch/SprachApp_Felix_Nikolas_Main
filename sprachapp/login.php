<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
require_once 'auth.php';

// Wenn bereits eingeloggt, zur Startseite umleiten
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Login-Formular verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $result = loginUser($username, $password);
    
    if ($result['success']) {
        header('Location: index.php');
        exit;
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="de" class="dark-mode">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="color-scheme" content="dark">
    <meta name="theme-color" content="#121212">
    <title>Login - SprachApp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap">
    <style>
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

        body, html {
            background-color: var(--dark-bg);
            color: var(--dark-text-primary);
            font-family: 'Roboto', sans-serif;
            height: 100%;
            margin: 0;
            padding: 0;
        }

        .dark-mode {
            background-color: var(--dark-bg);
            color: var(--dark-text-primary);
        }

        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-card {
            background-color: var(--dark-surface);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            width: 100%;
            max-width: 450px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
        }

        .login-header {
            background-color: var(--dark-primary);
            color: white;
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid var(--dark-border);
        }

        .login-header h1 {
            font-size: 28px;
            margin: 0;
            font-weight: 500;
        }

        .login-header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 16px;
        }

        .login-body {
            padding: 30px;
        }

        .logo-icon {
            font-size: 36px;
            margin-bottom: 10px;
            display: block;
        }

        .form-control {
            background-color: var(--dark-surface-light);
            border: 1px solid var(--dark-border);
            color: var(--dark-text-primary);
            padding: 12px;
            height: auto;
            border-radius: 8px;
        }

        .form-control:focus {
            background-color: var(--dark-surface-lighter);
            color: var(--dark-text-primary);
            border-color: var(--dark-primary-light);
            box-shadow: 0 0 0 0.25rem rgba(124, 77, 255, 0.25);
        }

        .form-control::placeholder {
            color: var(--dark-text-hint);
        }

        .input-group-text {
            background-color: var(--dark-surface-light);
            border: 1px solid var(--dark-border);
            color: var(--dark-text-secondary);
        }

        .btn-primary {
            background-color: var(--dark-primary);
            border-color: var(--dark-primary);
            padding: 12px;
            font-weight: 500;
            letter-spacing: 0.3px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover, .btn-primary:focus {
            background-color: var(--dark-primary-light);
            border-color: var(--dark-primary-light);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(124, 77, 255, 0.3);
        }

        .btn-outline-secondary {
            color: var(--dark-text-primary);
            border-color: var(--dark-border);
            padding: 12px;
            transition: all 0.3s ease;
        }

        .btn-outline-secondary:hover, .btn-outline-secondary:focus {
            background-color: var(--dark-surface-light);
            color: var(--dark-primary-light);
            border-color: var(--dark-primary-light);
        }

        .alert-danger {
            background-color: rgba(207, 102, 121, 0.15);
            border: none;
            color: #e57373;
        }

        .alert-success {
            background-color: rgba(76, 175, 80, 0.15);
            border: none;
            color: #81c784;
        }

        .form-text {
            color: var(--dark-text-secondary);
        }

        .text-muted {
            color: var(--dark-text-secondary) !important;
        }

        .forgot-password {
            color: var(--dark-primary-light);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .forgot-password:hover {
            color: var(--dark-secondary);
            text-decoration: underline;
        }

        .register-link {
            display: inline-block;
            margin-top: 15px;
            color: var(--dark-primary-light);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
            padding: 8px 16px;
            border-radius: 8px;
            background-color: rgba(124, 77, 255, 0.1);
        }

        .register-link:hover {
            background-color: rgba(124, 77, 255, 0.2);
            color: var(--dark-secondary);
        }

        .footer {
            margin-top: 30px;
            text-align: center;
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-card {
            animation: fadeIn 0.6s ease-out forwards;
        }

        /* Mobile Anpassungen */
        @media (max-width: 480px) {
            .login-body {
                padding: 20px;
            }

            .btn-lg {
                font-size: 16px;
                padding: 10px;
            }
        }
    </style>
    <script>
        // Force dark mode
        document.documentElement.style.backgroundColor = "#121212";
        document.documentElement.classList.add('dark-mode');
    </script>
</head>
<body class="dark-mode">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-language logo-icon"></i>
                <h1>SprachApp</h1>
                <p>Dein persönlicher Vokabeltrainer</p>
            </div>
            <div class="login-body">
                <h2 class="text-center mb-4">Anmelden</h2>
                
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?= h($error) ?>
                </div>
                <?php endif; ?>
                
                <form method="post" action="login.php">
                    <div class="mb-3">
                        <label for="username" class="form-label">Benutzername</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" required autofocus>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">Passwort</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Anmelden
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <a href="reset_password.php" class="forgot-password">Passwort vergessen?</a>
                </div>
                
                <div class="text-center mt-4">
                    <p>Noch kein Konto?</p>
                    <a href="register.php" class="register-link">
                        <i class="fas fa-user-plus me-2"></i>Jetzt registrieren
                    </a>
                </div>
                
                <div class="footer">
                    <small class="text-muted">&copy; <?= date('Y') ?> SprachApp. Alle Rechte vorbehalten.</small>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Fixes für spezielle Browser
        document.addEventListener('DOMContentLoaded', function() {
            // Safari Fix
            if (/^((?!chrome|android).)*safari/i.test(navigator.userAgent)) {
                document.body.style.backgroundColor = "#121212";
                document.documentElement.style.backgroundColor = "#121212";
                document.querySelector('.login-card').style.backgroundColor = "#1e1e1e";
            }
            
            // Firefox Fix
            if (navigator.userAgent.indexOf("Firefox") > -1) {
                document.body.style.backgroundColor = "#121212";
                document.documentElement.style.backgroundColor = "#121212";
                document.querySelector('.login-card').style.backgroundColor = "#1e1e1e";
            }
        });
    </script>
</body>
</html>