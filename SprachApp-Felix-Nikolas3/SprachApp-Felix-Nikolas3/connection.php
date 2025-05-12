<?php
$servername = "sql108.infinityfree.com";
$username = "if0_38905283";
$password = "ewgjt0aaksuC";
$dbname = "if0_38905283_sprachapp";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}
?>