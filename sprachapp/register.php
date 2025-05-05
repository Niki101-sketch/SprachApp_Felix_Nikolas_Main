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

// Registrierungsformular verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Passwörter überprüfen
    if ($password !== $confirmPassword) {
        $error = 'Die Passwörter stimmen nicht überein';
    } else {
        $result = registerUser($username, $email, $password);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
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
    <title>Registrieren - SprachApp</title>
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

        .register-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .register-card {
            background-color: var(--dark-surface);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            width: 100%;
            max-width: 500px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .register-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
        }

        .register-header {
            background-color: var(--dark-primary);
            color: white;
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid var(--dark-border);
        }

        .register-header h1 {
            font-size: 28px;
            margin: 0;
            font-weight: 500;
        }

        .register-header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 16px;
        }

        .register-body {
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

        .login-link {
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

        .login-link:hover {
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

        .register-card {
            animation: fadeIn 0.6s ease-out forwards;
        }

        /* Password Strength Indicator */
        .password-strength {
            height: 5px;
            margin-top: 5px;
            border-radius: 3px;
            transition: all 0.3s ease;
        }

        .strength-weak {
            background-color: var(--dark-error);
            width: 33%;
        }

        .strength-medium {
            background-color: var(--dark-warning);
            width: 66%;
        }

        .strength-strong {
            background-color: var(--dark-success);
            width: 100%;
        }

        /* Mobile Anpassungen */
        @media (max-width: 480px) {
            .register-body {
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
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <i class="fas fa-language logo-icon"></i>
                <h1>SprachApp</h1>
                <p>Dein persönlicher Vokabeltrainer</p>
            </div>
            <div class="register-body">
                <h2 class="text-center mb-4">Neues Konto erstellen</h2>
                
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?= h($error) ?>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?= h($success) ?>
                    <div class="mt-3">
                        <a href="login.php" class="btn btn-success btn-sm">
                            <i class="fas fa-sign-in-alt me-2"></i>Jetzt anmelden
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <form method="post" action="register.php">
                    <div class="mb-3">
                        <label for="username" class="form-label">Benutzername</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" required autofocus>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">E-Mail-Adresse</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Passwort</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required minlength="8" oninput="checkPasswordStrength()">
                        </div>
                        <div class="password-strength" id="password-strength"></div>
                        <div class="form-text">Mindestens 8 Zeichen lang</div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Passwort bestätigen</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8" oninput="checkPasswordMatch()">
                        </div>
                        <div class="form-text" id="password-match-hint"></div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg" id="register-btn">
                            <i class="fas fa-user-plus me-2"></i>Registrieren
                        </button>
                        <a href="login.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Zurück zum Login
                        </a>
                    </div>
                </form>
                <?php endif; ?>
                
                <?php if (!$success): ?>
                <div class="footer">
                    <small class="text-muted">&copy; <?= date('Y') ?> SprachApp. Alle Rechte vorbehalten.</small>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Passwort-Stärke prüfen
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthBar = document.getElementById('password-strength');
            
            if (password.length === 0) {
                strengthBar.className = 'password-strength';
                strengthBar.style.width = '0';
                return;
            }
            
            // Stärke berechnen
            let strength = 0;
            
            // Länge prüfen
            if (password.length >= 8) strength += 1;
            if (password.length >= 12) strength += 1;
            
            // Zeichen prüfen
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[a-z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;
            
            // Stärke-Klasse setzen
            strengthBar.className = 'password-strength';
            if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
            } else if (strength <= 4) {
                strengthBar.classList.add('strength-medium');
            } else {
                strengthBar.classList.add('strength-strong');
            }
        }
        
        // Passwort-Übereinstimmung prüfen
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const hint = document.getElementById('password-match-hint');
            
            if (confirmPassword.length === 0) {
                hint.textContent = '';
                return;
            }
            
            if (password === confirmPassword) {
                hint.textContent = 'Passwörter stimmen überein';
                hint.style.color = '#81c784';
            } else {
                hint.textContent = 'Passwörter stimmen nicht überein';
                hint.style.color = '#e57373';
            }
        }
        
        // Fixes für spezielle Browser
        document.addEventListener('DOMContentLoaded', function() {
            // Safari Fix
            if (/^((?!chrome|android).)*safari/i.test(navigator.userAgent)) {
                document.body.style.backgroundColor = "#121212";
                document.documentElement.style.backgroundColor = "#121212";
                document.querySelector('.register-card').style.backgroundColor = "#1e1e1e";
            }
            
            // Firefox Fix
            if (navigator.userAgent.indexOf("Firefox") > -1) {
                document.body.style.backgroundColor = "#121212";
                document.documentElement.style.backgroundColor = "#121212";
                document.querySelector('.register-card').style.backgroundColor = "#1e1e1e";
            }
        });
    </script>
</body>
</html>