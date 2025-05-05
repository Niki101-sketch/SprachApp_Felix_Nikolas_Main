<?php
// Einfache Diagnose-Seite
// Lade diese Datei in dein Hauptverzeichnis und rufe sie direkt auf:
// z.B. sprachapp.infinityfreeapp.com/diagnostics.php

// PHP-Info anzeigen
echo "<h1>PHP-Information</h1>";
echo "<p>PHP-Version: " . phpversion() . "</p>";

// Verzeichnisstruktur prüfen
echo "<h1>Verzeichnisstruktur</h1>";
echo "<pre>";
$files = scandir('.');
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        echo $file . (is_dir($file) ? '/' : '') . "\n";
    }
}
echo "</pre>";

// Datenbankverbindung testen
echo "<h1>Datenbankverbindung testen</h1>";
try {
    $db_host = 'sql307.infinityfree.com';
    $db_user = 'if0_38879012';
    $db_pass = 'eyMxuhFdMmvfw';
    $db_name = 'if0_38879012_sprachapp_database';
    
    $conn = new mysqli($db_host, $db_user, $db_pass);
    echo "<p style='color:green'>Verbindung zum Datenbankserver erfolgreich!</p>";
    
    // Datenbank wählen
    if ($conn->select_db($db_name)) {
        echo "<p style='color:green'>Datenbank '$db_name' erfolgreich ausgewählt!</p>";
        
        // Tabellen prüfen
        $result = $conn->query("SHOW TABLES");
        if ($result) {
            echo "<p>Gefundene Tabellen:</p>";
            echo "<ul>";
            while ($row = $result->fetch_row()) {
                echo "<li>" . $row[0] . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color:red'>Konnte Tabellen nicht auflisten: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color:red'>Konnte Datenbank nicht auswählen: " . $conn->error . "</p>";
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "<p style='color:red'>Datenbankfehler: " . $e->getMessage() . "</p>";
}

// Konfiguration prüfen
echo "<h1>Konfigurationsdateien</h1>";
$configFiles = ['config.php', 'supabase_helper.php', 'error_handler.php'];
foreach ($configFiles as $file) {
    if (file_exists($file)) {
        echo "<p style='color:green'>Die Datei '$file' existiert.</p>";
        
        // Dateigröße und Änderungsdatum anzeigen
        echo "<p>Größe: " . filesize($file) . " Bytes</p>";
        echo "<p>Letzte Änderung: " . date("Y-m-d H:i:s", filemtime($file)) . "</p>";
    } else {
        echo "<p style='color:red'>Die Datei '$file' fehlt!</p>";
    }
}

// .htaccess Datei prüfen
if (file_exists('.htaccess')) {
    echo "<p style='color:green'>.htaccess Datei gefunden.</p>";
    echo "<p>Inhalt:</p>";
    echo "<pre>" . htmlspecialchars(file_get_contents('.htaccess')) . "</pre>";
} else {
    echo "<p>Keine .htaccess Datei gefunden. Das ist möglicherweise in Ordnung, aber könnte auch ein Problem sein, wenn eine benötigt wird.</p>";
}

// Berechtigungen prüfen
echo "<h1>Dateiberechtigungen</h1>";
echo "<pre>";
foreach ($files as $file) {
    if ($file != '.' && $file != '..' && !is_dir($file)) {
        $perms = fileperms($file);
        $info = substr(sprintf('%o', $perms), -4);
        echo $file . " - Berechtigungen: " . $info . "\n";
    }
}
echo "</pre>";