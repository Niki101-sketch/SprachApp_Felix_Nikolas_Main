<?php
// Fehlerberichterstattung aktivieren
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

// Benutzer ausloggen - vereinfachte Version ohne auth.php-Abhängigkeit
function simpleLogout() {
    // Session-Variablen löschen
    $_SESSION = array();
    
    // Session-Cookie löschen
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Session zerstören
    session_destroy();
    
    return true;
}

// Diese Funktion verwenden, um ein einfaches Logout durchzuführen
simpleLogout();

// Zur Login-Seite umleiten
header('Location: login.php');
exit;