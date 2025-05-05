<?php
// Minimale Konfigurationsdatei ohne Abhängigkeiten
// Diese Datei sollte definitiv funktionieren

// Datenbank-Konfiguration für InfinityFree
$db_host = 'sql307.infinityfree.com';
$db_user = 'if0_38879012';
$db_pass = 'eyMxuhFdMmvfw';
$db_name = 'if0_38879012_sprachapp_database';

// Fehlerberichterstattung aktivieren
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Einfache Funktion zum Testen der Datenbankverbindung
function testDatabaseConnection() {
    global $db_host, $db_user, $db_pass, $db_name;
    
    try {
        // Verbindung zur Datenbank herstellen
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        
        // Verbindung prüfen
        if ($conn->connect_error) {
            return [
                'success' => false,
                'message' => 'Verbindung fehlgeschlagen: ' . $conn->connect_error
            ];
        }
        
        // Tabellen prüfen
        $result = $conn->query("SHOW TABLES");
        $tables = [];
        
        if ($result) {
            while ($row = $result->fetch_row()) {
                $tables[] = $row[0];
            }
        }
        
        $conn->close();
        
        return [
            'success' => true,
            'message' => 'Verbindung erfolgreich',
            'tables' => $tables
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Fehler: ' . $e->getMessage()
        ];
    }
}

// Ausgabe für den Test
echo "<h1>Datenbank-Konfigurationstest</h1>";

$result = testDatabaseConnection();

if ($result['success']) {
    echo "<p style='color:green'>✓ Datenbankverbindung erfolgreich hergestellt!</p>";
    echo "<p>Gefundene Tabellen:</p>";
    
    if (empty($result['tables'])) {
        echo "<p style='color:orange'>Keine Tabellen gefunden. Die Datenbank existiert, ist aber möglicherweise leer.</p>";
    } else {
        echo "<ul>";
        foreach ($result['tables'] as $table) {
            echo "<li>" . htmlspecialchars($table) . "</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p style='color:red'>✗ Datenbankverbindung fehlgeschlagen!</p>";
    echo "<p>Fehlermeldung: " . htmlspecialchars($result['message']) . "</p>";
    
    echo "<h2>Fehlerbehebungstipps:</h2>";
    echo "<ol>";
    echo "<li>Überprüfe, ob die Datenbankverbindungsdaten korrekt sind.</li>";
    echo "<li>Stelle sicher, dass die Datenbank existiert und der Benutzer darauf zugreifen kann.</li>";
    echo "<li>Bei InfinityFree kann es manchmal bis zu 24 Stunden dauern, bis eine neu erstellte Datenbank verfügbar ist.</li>";
    echo "</ol>";
}