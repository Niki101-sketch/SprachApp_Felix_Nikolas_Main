<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

// Einheit (Unit) erstellen
function createUnit($unitName, $description, $createdBy, $isPublic = true) {
    // Validierung
    if (empty($unitName)) {
        return ['success' => false, 'message' => 'Einheitenname darf nicht leer sein'];
    }
    
    // Datenbankverbindung herstellen
    $conn = connectDB();
    
    // Einheit erstellen
    $stmt = $conn->prepare("INSERT INTO units (unit_name, description, created_by, is_public) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $unitName, $description, $createdBy, $isPublic);
    
    if ($stmt->execute()) {
        $unitId = $conn->insert_id;
        $stmt->close();
        $conn->close();
        return ['success' => true, 'message' => 'Einheit erstellt', 'unit_id' => $unitId];
    } else {
        $error = $conn->error;
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => 'Fehler beim Erstellen der Einheit: ' . $error];
    }
}

// Vokabel erstellen
function createVocabulary($germanWord, $englishWord, $unitId) {
    // Validierung
    if (empty($germanWord) || empty($englishWord)) {
        return ['success' => false, 'message' => 'Beide Wörter müssen ausgefüllt sein'];
    }
    
    // Datenbankverbindung herstellen
    $conn = connectDB();
    
    // Prüfen, ob die Vokabel bereits existiert
    $stmt = $conn->prepare("SELECT vocab_id FROM vocabulary WHERE german_word = ? AND english_word = ? AND unit_id = ?");
    $stmt->bind_param("ssi", $germanWord, $englishWord, $unitId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => 'Diese Vokabel existiert bereits in dieser Einheit'];
    }
    
    // Vokabel erstellen
    $stmt = $conn->prepare("INSERT INTO vocabulary (german_word, english_word, unit_id) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $germanWord, $englishWord, $unitId);
    
    if ($stmt->execute()) {
        $vocabId = $conn->insert_id;
        $stmt->close();
        $conn->close();
        return ['success' => true, 'message' => 'Vokabel erstellt', 'vocab_id' => $vocabId];
    } else {
        $error = $conn->error;
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => 'Fehler beim Erstellen der Vokabel: ' . $error];
    }
}

// Einheit aktualisieren
function updateUnit($unitId, $unitName, $description, $isPublic) {
    // Validierung
    if (empty($unitName)) {
        return ['success' => false, 'message' => 'Einheitenname darf nicht leer sein'];
    }
    
    // Datenbankverbindung herstellen
    $conn = connectDB();
    
    // Einheit aktualisieren
    $stmt = $conn->prepare("UPDATE units SET unit_name = ?, description = ?, is_public = ? WHERE unit_id = ?");
    $stmt->bind_param("ssii", $unitName, $description, $isPublic, $unitId);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        return ['success' => true, 'message' => 'Einheit aktualisiert'];
    } else {
        $error = $conn->error;
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => 'Fehler beim Aktualisieren der Einheit: ' . $error];
    }
}

// Vokabel aktualisieren
function updateVocabulary($vocabId, $germanWord, $englishWord) {
    // Validierung
    if (empty($germanWord) || empty($englishWord)) {
        return ['success' => false, 'message' => 'Beide Wörter müssen ausgefüllt sein'];
    }
    
    // Datenbankverbindung herstellen
    $conn = connectDB();
    
    // Vokabel-Details abrufen, um die unit_id zu erhalten
    $stmt = $conn->prepare("SELECT unit_id FROM vocabulary WHERE vocab_id = ?");
    $stmt->bind_param("i", $vocabId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => 'Vokabel nicht gefunden'];
    }
    
    $unitId = $result->fetch_assoc()['unit_id'];
    
    // Prüfen, ob die aktualisierte Vokabel bereits existiert (außer die aktuelle Vokabel)
    $stmt = $conn->prepare("SELECT vocab_id FROM vocabulary WHERE german_word = ? AND english_word = ? AND unit_id = ? AND vocab_id != ?");
    $stmt->bind_param("ssii", $germanWord, $englishWord, $unitId, $vocabId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => 'Diese Vokabel existiert bereits in dieser Einheit'];
    }
    
    // Vokabel aktualisieren
    $stmt = $conn->prepare("UPDATE vocabulary SET german_word = ?, english_word = ? WHERE vocab_id = ?");
    $stmt->bind_param("ssi", $germanWord, $englishWord, $vocabId);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        return ['success' => true, 'message' => 'Vokabel aktualisiert'];
    } else {
        $error = $conn->error;
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => 'Fehler beim Aktualisieren der Vokabel: ' . $error];
    }
}

// Einheit löschen
function deleteUnit($unitId) {
    // Datenbankverbindung herstellen
    $conn = connectDB();
    
    // Einheit löschen (Vokabeln werden automatisch durch ON DELETE CASCADE gelöscht)
    $stmt = $conn->prepare("DELETE FROM units WHERE unit_id = ?");
    $stmt->bind_param("i", $unitId);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        return ['success' => true, 'message' => 'Einheit und zugehörige Vokabeln gelöscht'];
    } else {
        $error = $conn->error;
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => 'Fehler beim Löschen der Einheit: ' . $error];
    }
}

// Vokabel löschen
function deleteVocabulary($vocabId) {
    // Datenbankverbindung herstellen
    $conn = connectDB();
    
    // Vokabel löschen
    $stmt = $conn->prepare("DELETE FROM vocabulary WHERE vocab_id = ?");
    $stmt->bind_param("i", $vocabId);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        return ['success' => true, 'message' => 'Vokabel gelöscht'];
    } else {
        $error = $conn->error;
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => 'Fehler beim Löschen der Vokabel: ' . $error];
    }
}

// Einheit favorisieren
function favoriteUnit($userId, $unitId) {
    // Datenbankverbindung herstellen
    $conn = connectDB();
    
    // Prüfen, ob bereits favorisiert
    $stmt = $conn->prepare("SELECT * FROM user_favorites WHERE user_id = ? AND unit_id = ?");
    $stmt->bind_param("ii", $userId, $unitId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => 'Einheit bereits favorisiert'];
    }
    
    // Favorit erstellen
    $stmt = $conn->prepare("INSERT INTO user_favorites (user_id, unit_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $userId, $unitId);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        return ['success' => true, 'message' => 'Einheit favorisiert'];
    } else {
        $error = $conn->error;
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => 'Fehler beim Favorisieren der Einheit: ' . $error];
    }
}

// Favorit entfernen
function unfavoriteUnit($userId, $unitId) {
    // Datenbankverbindung herstellen
    $conn = connectDB();
    
    // Favorit entfernen
    $stmt = $conn->prepare("DELETE FROM user_favorites WHERE user_id = ? AND unit_id = ?");
    $stmt->bind_param("ii", $userId, $unitId);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        return ['success' => true, 'message' => 'Favorit entfernt'];
    } else {
        $error = $conn->error;
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => 'Fehler beim Entfernen des Favoriten: ' . $error];
    }
}

// Alle öffentlichen Einheiten abrufen
function getAllPublicUnits() {
    // SQL-Abfrage direkt verwenden
    $sql = "SELECT u.*, us.username FROM units u 
            JOIN users us ON u.created_by = us.user_id 
            WHERE u.is_public = 1 
            ORDER BY u.created_at DESC";
    return dbQuery($sql, [], true);
}

// Einheiten eines Benutzers abrufen
function getUserUnits($userId) {
    // Datenbankverbindung herstellen
    $conn = connectDB();
    
    // Einheiten des Benutzers abrufen
    $stmt = $conn->prepare("SELECT * FROM units WHERE created_by = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $units = [];
    while ($row = $result->fetch_assoc()) {
        $units[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    return $units;
}

// Favorisierte Einheiten eines Benutzers abrufen
function getUserFavorites($userId) {
    // SQL-Abfrage direkt verwenden
    $sql = "SELECT f.*, u.*, us.username 
            FROM user_favorites f 
            JOIN units u ON f.unit_id = u.unit_id 
            JOIN users us ON u.created_by = us.user_id 
            WHERE f.user_id = ?";
    return dbQuery($sql, [$userId], true);
}

// Vokabeln einer Einheit abrufen
function getUnitVocabulary($unitId) {
    // Datenbankverbindung herstellen
    $conn = connectDB();
    
    // Vokabeln der Einheit abrufen
    $stmt = $conn->prepare("SELECT * FROM vocabulary WHERE unit_id = ?");
    $stmt->bind_param("i", $unitId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $vocabulary = [];
    while ($row = $result->fetch_assoc()) {
        $vocabulary[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    return $vocabulary;
}

// Falsche Vokabeln eines Benutzers abrufen
function getUserWrongAnswers($userId) {
    // Datenbankverbindung herstellen
    $conn = connectDB();
    
    // Falsch beantwortete Vokabeln abrufen
    $stmt = $conn->prepare("
        SELECT w.*, v.*, u.unit_name 
        FROM wrong_answers w 
        JOIN vocabulary v ON w.vocab_id = v.vocab_id 
        JOIN units u ON v.unit_id = u.unit_id 
        WHERE w.user_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $wrongAnswers = [];
    while ($wrong = $result->fetch_assoc()) {
        // Struktur anpassen, um mit dem alten Code kompatibel zu sein
        $wrong['vocabulary'] = [
            'german_word' => $wrong['german_word'],
            'english_word' => $wrong['english_word'],
            'units' => [
                'unit_name' => $wrong['unit_name']
            ]
        ];
        $wrongAnswers[] = $wrong;
    }
    
    $stmt->close();
    $conn->close();
    
    return $wrongAnswers;
}