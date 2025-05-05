<?php
// Datenbank-Konfiguration für InfinityFree
$db_host = 'sql307.infinityfree.com';
$db_user = 'if0_38879012';
$db_pass = 'eyMxuhFdMmvfw';
$db_name = 'if0_38879012_sprachapp_database';

// Konstanten
define('BASE_URL', 'sprachapp.infinityfreeapp.com');
define('SECONDS_IN_DAY', 86400);
define('APP_NAME', 'SprachApp');

// Session starten
session_start();

// Funktion für Datenbankverbindung
function connectDB() {
    global $db_host, $db_user, $db_pass, $db_name;
    
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        die("Verbindung fehlgeschlagen: " . $conn->connect_error);
    }
    
    // Zeichensatz auf UTF-8 setzen
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

// Hilfsfunktion für sicheres HTML
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Prüft, ob ein Benutzer eingeloggt ist
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Prüft, ob ein Benutzer Admin ist
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

// Umleitung, wenn nicht eingeloggt
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Umleitung, wenn nicht Admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: index.php?error=not_authorized');
        exit;
    }
}

/**
 * Datenbankabfrage-Funktion (verbesserte Version für MySQL)
 * Korrigiert mit korrekter Referenzübergabe für bind_param
 */
function dbQuery($sql, $params = [], $fetchAll = false) {
    $conn = connectDB();
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die('SQL-Fehler: ' . $conn->error);
    }
    
    if (!empty($params)) {
        $types = '';
        $bindParams = [];
        
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 'b';
            }
        }
        
        $bindParams[0] = $types;
        
        // Parameter als Referenzen übergeben
        for ($i = 0; $i < count($params); $i++) {
            $bindParams[$i+1] = &$params[$i];
        }
        
        // Nutze call_user_func_array für dynamische Parameter
        call_user_func_array([$stmt, 'bind_param'], $bindParams);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($fetchAll) {
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    } else {
        $data = $result->fetch_assoc();
    }
    
    $stmt->close();
    $conn->close();
    
    return $data;
}

// Lade die Supabase-Kompatibilitätsfunktionen, wenn vorhanden
if (file_exists('supabase_helper.php')) {
    require_once 'supabase_helper.php';
}