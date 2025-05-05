<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
requireLogin();

// Benutzer-ID abrufen
$userId = $_SESSION['user_id'];

// Datenbankverbindung herstellen
$conn = connectDB();

// Benutzerdaten abrufen
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();

// Benutzereinstellungen abrufen
$userSettings = null;
$settingsStmt = $conn->prepare("SELECT * FROM user_settings WHERE user_id = ?");
if ($settingsStmt) {
    $settingsStmt->bind_param("i", $userId);
    $settingsStmt->execute();
    $settingsResult = $settingsStmt->get_result();
    
    if ($settingsResult->num_rows > 0) {
        $userSettings = $settingsResult->fetch_assoc();
    }
}

// Favorisierte Einheiten abrufen
$favorites = [];
$stmt = $conn->prepare("
    SELECT uf.*, u.unit_name, u.description 
    FROM user_favorites uf 
    JOIN units u ON uf.unit_id = u.unit_id 
    WHERE uf.user_id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$favsResult = $stmt->get_result();

while ($fav = $favsResult->fetch_assoc()) {
    $favorites[] = $fav;
}

// Lernfortschritt abrufen
$progress = [];
$stmt = $conn->prepare("
    SELECT up.*, v.german_word, v.english_word, u.unit_name
    FROM user_progress up
    JOIN vocabulary v ON up.vocab_id = v.vocab_id
    JOIN units u ON v.unit_id = u.unit_id
    WHERE up.user_id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$progressResult = $stmt->get_result();

while ($prog = $progressResult->fetch_assoc()) {
    $progress[] = $prog;
}

// Falsch beantwortete Vokabeln abrufen
$wrongAnswers = [];
$stmt = $conn->prepare("
    SELECT w.*, v.german_word, v.english_word, u.unit_name
    FROM wrong_answers w 
    JOIN vocabulary v ON w.vocab_id = v.vocab_id 
    JOIN units u ON v.unit_id = u.unit_id 
    WHERE w.user_id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$wrongResult = $stmt->get_result();

while ($wrong = $wrongResult->fetch_assoc()) {
    $wrongAnswers[] = $wrong;
}

// Vom Benutzer erstellte Einheiten
$createdUnits = [];
$stmt = $conn->prepare("
    SELECT * FROM units WHERE created_by = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$unitsResult = $stmt->get_result();

while ($unit = $unitsResult->fetch_assoc()) {
    // Vokabeln für jede Einheit abrufen
    $vocabStmt = $conn->prepare("
        SELECT * FROM vocabulary WHERE unit_id = ?
    ");
    $vocabStmt->bind_param("i", $unit['unit_id']);
    $vocabStmt->execute();
    $vocabResult = $vocabStmt->get_result();
    
    $vocabulary = [];
    while ($vocab = $vocabResult->fetch_assoc()) {
        $vocabulary[] = $vocab;
    }
    
    $unit['vocabulary'] = $vocabulary;
    $createdUnits[] = $unit;
}

// JSON erstellen
$exportData = [
    'user' => [
        'username' => $userData['username'],
        'email' => $userData['email'],
        'registration_date' => $userData['created_at'],
        'streak_days' => $userData['streak_days'],
        'total_words_learned' => $userData['total_words_learned'],
        'total_units_learned' => $userData['total_units_learned']
    ],
    'settings' => $userSettings,
    'favorites' => $favorites,
    'progress' => $progress,
    'wrong_answers' => $wrongAnswers,
    'created_units' => $createdUnits
];

$jsonData = json_encode($exportData, JSON_PRETTY_PRINT);

// Datei zum Download vorbereiten
$filename = 'sprachapp_export_' . $userData['username'] . '_' . date('Y-m-d') . '.json';

// HTTP-Header für den Download setzen
header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($jsonData));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Daten ausgeben
echo $jsonData;

// Verbindung schließen
$conn->close();
exit;