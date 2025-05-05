<?php
require_once 'config.php';

// Fehlerberichterstattung aktivieren
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Vereinfachte Registrierungsfunktion
 */
function registerUser($username, $email, $password) {
    // Validierung
    if (empty($username) || empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Alle Felder müssen ausgefüllt sein'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Ungültige E-Mail-Adresse'];
    }
    
    if (strlen($password) < 8) {
        return ['success' => false, 'message' => 'Passwort muss mindestens 8 Zeichen lang sein'];
    }
    
    // Datenbankverbindung herstellen
    $conn = connectDB();
    
    // Prüfen, ob Benutzer bereits existiert
    $stmt = $conn->prepare("SELECT username, email FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $existingUser = $result->fetch_assoc();
        if ($existingUser['username'] === $username) {
            return ['success' => false, 'message' => 'Benutzername bereits vergeben'];
        }
        if ($existingUser['email'] === $email) {
            return ['success' => false, 'message' => 'E-Mail-Adresse bereits registriert'];
        }
    }
    
    // Passwort hashen
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Benutzer erstellen
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, is_admin, streak_days, total_words_learned, total_units_learned, last_login_date) VALUES (?, ?, ?, 0, 0, 0, 0, NOW())");
    $stmt->bind_param("sss", $username, $email, $hashedPassword);
    
    if ($stmt->execute()) {
        $userId = $conn->insert_id;
        
        // Eintrag in Bestenliste erstellen
        $stmt = $conn->prepare("INSERT INTO leaderboard (user_id, score, streak_days, words_learned) VALUES (?, 0, 0, 0)");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        $conn->close();
        return ['success' => true, 'message' => 'Registrierung erfolgreich'];
    } else {
        $conn->close();
        return ['success' => false, 'message' => 'Fehler bei der Registrierung: ' . $conn->error];
    }
}

/**
 * Vereinfachte Login-Funktion
 */
function loginUser($username, $password) {
    // Validierung
    if (empty($username) || empty($password)) {
        return ['success' => false, 'message' => 'Benutzername und Passwort müssen ausgefüllt sein'];
    }
    
    // Datenbankverbindung herstellen
    $conn = connectDB();
    
    // Benutzer suchen
    $stmt = $conn->prepare("SELECT user_id, username, password, is_admin, streak_days, last_login_date FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $conn->close();
        return ['success' => false, 'message' => 'Ungültiger Benutzername oder Passwort'];
    }
    
    $user = $result->fetch_assoc();
    
    // Passwort überprüfen
    if (!password_verify($password, $user['password'])) {
        $conn->close();
        return ['success' => false, 'message' => 'Ungültiger Benutzername oder Passwort'];
    }
    
    // Streak aktualisieren
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $newStreakDays = $user['streak_days'];
    
    if ($user['last_login_date'] === $yesterday) {
        // Streak fortsetzen
        $newStreakDays++;
    } else if ($user['last_login_date'] !== $today) {
        // Streak zurücksetzen, falls nicht gestern und nicht heute eingeloggt
        $newStreakDays = 1;
    }
    
    // Benutzer-Daten aktualisieren
    $stmt = $conn->prepare("UPDATE users SET streak_days = ?, last_login_date = ? WHERE user_id = ?");
    $stmt->bind_param("isi", $newStreakDays, $today, $user['user_id']);
    $stmt->execute();
    
    // Bestenliste aktualisieren
    $stmt = $conn->prepare("UPDATE leaderboard SET streak_days = ? WHERE user_id = ?");
    $stmt->bind_param("ii", $newStreakDays, $user['user_id']);
    $stmt->execute();
    
    // Session setzen
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['is_admin'] = $user['is_admin'] ? true : false;
    $_SESSION['streak_days'] = $newStreakDays;
    
    $conn->close();
    return ['success' => true, 'message' => 'Login erfolgreich'];
}

/**
 * Logout-Funktion
 */
function logoutUser() {
    // Session löschen
    session_unset();
    session_destroy();
    
    return ['success' => true, 'message' => 'Logout erfolgreich'];
}