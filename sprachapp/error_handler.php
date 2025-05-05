<?php
// Füge diese Datei ein, um Fehlermeldungen anzuzeigen
// während der Entwicklung/Debugging

// Aktiviere Fehlerberichte
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Logge Fehler in eine Datei
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Eigener Error Handler
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $error_message = date('[Y-m-d H:i:s]') . " Error: [$errno] $errstr in $errfile on line $errline\n";
    error_log($error_message, 3, 'php_errors.log');
    
    // Zeige Fehler nur während der Entwicklung an
    if (true) { // Setze auf false für Produktion
        echo "<div style='color:red; background-color:#f8d7da; padding:10px; margin:10px; border:1px solid #f5c6cb; border-radius:5px;'>";
        echo "<h3>Fehler wurde erkannt:</h3>";
        echo "<p><strong>Typ:</strong> $errno</p>";
        echo "<p><strong>Nachricht:</strong> $errstr</p>";
        echo "<p><strong>Datei:</strong> $errfile</p>";
        echo "<p><strong>Zeile:</strong> $errline</p>";
        echo "</div>";
    }
    
    // Gib true zurück, um anzuzeigen, dass der Fehler behandelt wurde
    return true;
}

// Registriere den benutzerdefinierten Error Handler
set_error_handler("customErrorHandler");

// Eigener Exception Handler
function customExceptionHandler($exception) {
    $error_message = date('[Y-m-d H:i:s]') . " Exception: " . $exception->getMessage() . 
                     " in " . $exception->getFile() . " on line " . $exception->getLine() . "\n";
    error_log($error_message, 3, 'php_errors.log');
    
    // Zeige Exception nur während der Entwicklung an
    if (true) { // Setze auf false für Produktion
        echo "<div style='color:red; background-color:#f8d7da; padding:10px; margin:10px; border:1px solid #f5c6cb; border-radius:5px;'>";
        echo "<h3>Exception wurde geworfen:</h3>";
        echo "<p><strong>Nachricht:</strong> " . $exception->getMessage() . "</p>";
        echo "<p><strong>Datei:</strong> " . $exception->getFile() . "</p>";
        echo "<p><strong>Zeile:</strong> " . $exception->getLine() . "</p>";
        echo "<p><strong>Trace:</strong><br>" . nl2br($exception->getTraceAsString()) . "</p>";
        echo "</div>";
    }
}

// Registriere den benutzerdefinierten Exception Handler
set_exception_handler("customExceptionHandler");

// Handler für fatale Fehler
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        $error_message = date('[Y-m-d H:i:s]') . " Fatal Error: " . $error['message'] . 
                         " in " . $error['file'] . " on line " . $error['line'] . "\n";
        error_log($error_message, 3, 'php_errors.log');
        
        // Zeige fatalen Fehler nur während der Entwicklung an
        if (true) { // Setze auf false für Produktion
            echo "<div style='color:white; background-color:#dc3545; padding:10px; margin:10px; border:1px solid #bd2130; border-radius:5px;'>";
            echo "<h3>Fataler Fehler:</h3>";
            echo "<p><strong>Nachricht:</strong> " . $error['message'] . "</p>";
            echo "<p><strong>Datei:</strong> " . $error['file'] . "</p>";
            echo "<p><strong>Zeile:</strong> " . $error['line'] . "</p>";
            echo "</div>";
        }
    }
});