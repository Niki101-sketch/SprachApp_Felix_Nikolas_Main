<?php
/**
 * Dark Mode Converter für SprachApp
 * 
 * Dieses Skript fügt allen PHP-Dateien im aktuellen Verzeichnis (rekursiv) den dunklen Theme-Header hinzu.
 * Achtung: Erstelle Backups deiner Dateien, bevor du dieses Skript ausführst!
 */

// Fehlerberichterstattung aktivieren
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verzeichnis für Header-Datei erstellen
if (!is_dir('includes')) {
    mkdir('includes', 0755);
    echo "Verzeichnis 'includes' erstellt...\n";
}

// Dark-Header-Datei schreiben
$darkHeaderPath = 'includes/dark-header.php';
$darkHeaderContent = file_get_contents(__DIR__ . '/includes/dark-header.php');

if (!file_exists($darkHeaderPath)) {
    file_put_contents($darkHeaderPath, $darkHeaderContent);
    echo "Datei '{$darkHeaderPath}' erstellt...\n";
}

// Liste der Dateien, die nicht bearbeitet werden sollen
$skipFiles = ['convert-to-dark.php', 'index.html', 'README.md', '.htaccess'];

// Funktion zum rekursiven Durchsuchen von Verzeichnissen
function processPHPFiles($directory) {
    global $skipFiles;
    
    $files = scandir($directory);
    $converted = 0;
    $skipped = 0;
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $path = $directory . '/' . $file;
        
        // Wenn es sich um ein Verzeichnis handelt, rekursiv aufrufen
        if (is_dir($path)) {
            // Verzeichnisse includes/, vendor/, node_modules/ überspringen
            if (in_array($file, ['includes', 'vendor', 'node_modules', 'cache', 'logs', '.git'])) {
                echo "Verzeichnis '{$path}' übersprungen...\n";
                continue;
            }
            
            list($subConverted, $subSkipped) = processPHPFiles($path);
            $converted += $subConverted;
            $skipped += $subSkipped;
            continue;
        }
        
        // Nur PHP-Dateien bearbeiten
        if (pathinfo($path, PATHINFO_EXTENSION) !== 'php') {
            $skipped++;
            continue;
        }
        
        // Zu überspringende Dateien
        if (in_array($file, $skipFiles)) {
            echo "Datei '{$path}' übersprungen...\n";
            $skipped++;
            continue;
        }
        
        // Prüfen, ob die Datei bereits den Header enthält
        $content = file_get_contents($path);
        if (strpos($content, 'include \'includes/dark-header.php\'') !== false) {
            echo "Datei '{$path}' hat bereits den Header...\n";
            $skipped++;
            continue;
        }
        
        // Header zur Datei hinzufügen
        $newContent = '';
        
        // PHP-Tag am Anfang der Datei suchen
        if (strpos($content, '<?php') === 0) {
            // PHP-Code am Anfang - Header nach dem ersten PHP-Block einfügen
            $phpEndPos = strpos($content, '?>');
            
            if ($phpEndPos !== false) {
                // PHP-Ende-Tag gefunden
                $newContent = substr($content, 0, $phpEndPos + 2);
                $newContent .= "\n<?php include 'includes/dark-header.php'; ?>\n";
                $newContent .= substr($content, $phpEndPos + 2);
            } else {
                // Kein PHP-Ende-Tag - Header nach dem require/include-Block einfügen
                $lines = explode("\n", $content);
                $requireEndLine = 0;
                
                // Nach dem letzten require/include/ini_set suchen
                foreach ($lines as $idx => $line) {
                    if (preg_match('/(require|include|ini_set|error_reporting)/i', $line)) {
                        $requireEndLine = $idx;
                    }
                }
                
                // Header nach dem letzten require/include einfügen
                array_splice($lines, $requireEndLine + 1, 0, "include 'includes/dark-header.php';");
                $newContent = implode("\n", $lines);
            }
        } else {
            // Kein PHP am Anfang - Header am Anfang der Datei einfügen
            $newContent = "<?php include 'includes/dark-header.php'; ?>\n" . $content;
        }
        
        // Neue Datei speichern
        file_put_contents($path, $newContent);
        echo "Datei '{$path}' konvertiert...\n";
        $converted++;
    }
    
    return [$converted, $skipped];
}

// Starten der Konvertierung
echo "Beginne mit der Konvertierung aller PHP-Dateien...\n";
list($converted, $skipped) = processPHPFiles('.');
echo "Konvertierung abgeschlossen!\n";
echo "Insgesamt wurden {$converted} Dateien konvertiert und {$skipped} Dateien übersprungen.\n";
echo "Das dunkle Design sollte jetzt auf allen Seiten aktiviert sein.\n";
echo "Öffne die Webseite in verschiedenen Browsern, um die Kompatibilität zu überprüfen.\n";
?>