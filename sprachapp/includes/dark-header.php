<?php
// Diese Datei in jeder PHP-Datei ganz oben einfügen mit: include 'includes/dark-header.php';
// Füge sie vor dem <!DOCTYPE html> Tag ein
?>
<!-- Universelles Dark Mode für SprachApp - Browserübergreifend -->
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="color-scheme" content="dark">
    <meta name="theme-color" content="#121212">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <!-- Haupt-Stylesheets -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap">
    
    <!-- Titel-Platzhalter - wird von der jeweiligen Seite überschrieben -->
    <title><?= isset($pageTitle) ? $pageTitle . ' - SprachApp' : 'SprachApp' ?></title>
    
    <!-- Kritische Dark-Mode-Styles - direkt eingebettet für sofortige Anwendung -->
    <style>
        /* Basis-Dark-Mode */
        html, body {
            background-color: #121212 !important;
            color: rgba(255, 255, 255, 0.87) !important;
            font-family: 'Roboto', sans-serif;
        }
        
        /* Container-Elemente */
        .container, .container-fluid, .row, [class^="col-"] {
            background-color: #121212 !important;
        }
        
        /* Cards */
        .card {
            background-color: #1e1e1e !important;
            border-color: rgba(255, 255, 255, 0.12) !important;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
        }
        
        .card-header {
            background-color: #2d2d2d !important;
            border-bottom-color: rgba(255, 255, 255, 0.12) !important;
            color: rgba(255, 255, 255, 0.87) !important;
        }
        
        .card-body {
            background-color: #1e1e1e !important;
            color: rgba(255, 255, 255, 0.87) !important;
        }
        
        .card-footer {
            background-color: #2d2d2d !important;
            border-top-color: rgba(255, 255, 255, 0.12) !important;
        }
        
        /* Formulare */
        .form-control, .input-group-text {
            background-color: #2d2d2d !important;
            color: rgba(255, 255, 255, 0.87) !important;
            border-color: rgba(255, 255, 255, 0.12) !important;
        }
        
        .form-control:focus {
            background-color: #363636 !important;
            color: rgba(255, 255, 255, 0.87) !important;
            border-color: #6200ee !important;
            box-shadow: 0 0 0 0.25rem rgba(98, 0, 238, 0.25) !important;
        }
        
        .form-control:disabled, .form-control[readonly] {
            background-color: #1e1e1e !important;
            color: rgba(255, 255, 255, 0.6) !important;
        }
        
        .form-select {
            background-color: #2d2d2d !important;
            color: rgba(255, 255, 255, 0.87) !important;
            border-color: rgba(255, 255, 255, 0.12) !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='rgba(255,255,255,0.6)' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e") !important;
        }
        
        /* Buttons */
        .btn-primary {
            background-color: #6200ee !important;
            border-color: #6200ee !important;
        }
        
        .btn-primary:hover, .btn-primary:focus {
            background-color: #7c4dff !important;
            border-color: #7c4dff !important;
        }
        
        .btn-secondary {
            background-color: #2d2d2d !important;
            border-color: #2d2d2d !important;
            color: rgba(255, 255, 255, 0.87) !important;
        }
        
        .btn-outline-secondary {
            color: rgba(255, 255, 255, 0.87) !important;
            border-color: rgba(255, 255, 255, 0.12) !important;
        }
        
        .btn-outline-secondary:hover, .btn-outline-secondary:focus {
            background-color: #2d2d2d !important;
            color: #7c4dff !important;
        }
        
        .btn-success {
            background-color: #4caf50 !important;
            border-color: #4caf50 !important;
        }
        
        .btn-info {
            background-color: #2196f3 !important;
            border-color: #2196f3 !important;
        }
        
        .btn-warning {
            background-color: #ff9800 !important;
            border-color: #ff9800 !important;
        }
        
        .btn-danger {
            background-color: #cf6679 !important;
            border-color: #cf6679 !important;
        }
        
        /* Navbar */
        .navbar {
            background-color: #1e1e1e !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12) !important;
        }
        
        .navbar-dark .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.87) !important;
        }
        
        .navbar-dark .navbar-nav .nav-link:hover {
            color: #7c4dff !important;
        }
        
        .navbar-brand {
            color: #7c4dff !important;
        }
        
        /* Dropdown-Menüs */
        .dropdown-menu {
            background-color: #2d2d2d !important;
            border-color: rgba(255, 255, 255, 0.12) !important;
        }
        
        .dropdown-item {
            color: rgba(255, 255, 255, 0.87) !important;
        }
        
        .dropdown-item:hover, .dropdown-item:focus {
            background-color: #363636 !important;
            color: #7c4dff !important;
        }
        
        .dropdown-divider {
            border-top-color: rgba(255, 255, 255, 0.12) !important;
        }
        
        /* Tabellen */
        .table {
            color: rgba(255, 255, 255, 0.87) !important;
        }
        
        .table th, .table td {
            border-color: rgba(255, 255, 255, 0.12) !important;
            color: rgba(255, 255, 255, 0.87) !important;
        }
        
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(255, 255, 255, 0.05) !important;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.08) !important;
        }
        
        /* Alerts */
        .alert-success {
            background-color: rgba(76, 175, 80, 0.15) !important;
            border: none !important;
            color: #81c784 !important;
        }
        
        .alert-danger {
            background-color: rgba(207, 102, 121, 0.15) !important;
            border: none !important;
            color: #e57373 !important;
        }
        
        .alert-info {
            background-color: rgba(33, 150, 243, 0.15) !important;
            border: none !important;
            color: #64b5f6 !important;
        }
        
        .alert-warning {
            background-color: rgba(255, 152, 0, 0.15) !important;
            border: none !important;
            color: #ffb74d !important;
        }
        
        /* Modals */
        .modal-content {
            background-color: #1e1e1e !important;
            border-color: rgba(255, 255, 255, 0.12) !important;
        }
        
        .modal-header, .modal-footer {
            border-color: rgba(255, 255, 255, 0.12) !important;
        }
        
        /* Breadcrumbs */
        .breadcrumb {
            background-color: #2d2d2d !important;
        }
        
        .breadcrumb-item a {
            color: #7c4dff !important;
        }
        
        .breadcrumb-item.active {
            color: rgba(255, 255, 255, 0.6) !important;
        }
        
        /* Badges */
        .badge {
            background-color: #2d2d2d !important;
        }
        
        .badge.bg-primary {
            background-color: #6200ee !important;
        }
        
        .badge.bg-success {
            background-color: #4caf50 !important;
        }
        
        .badge.bg-info {
            background-color: #2196f3 !important;
        }
        
        .badge.bg-warning {
            background-color: #ff9800 !important;
        }
        
        .badge.bg-danger {
            background-color: #cf6679 !important;
        }
        
        /* Links */
        a {
            color: #7c4dff !important;
        }
        
        a:hover {
            color: #03dac6 !important;
        }
        
        /* Progress Bars */
        .progress {
            background-color: #2d2d2d !important;
        }
        
        .progress-bar {
            background-color: #6200ee !important;
        }
        
        /* List Groups */
        .list-group-item {
            background-color: #2d2d2d !important;
            border-color: rgba(255, 255, 255, 0.12) !important;
            color: rgba(255, 255, 255, 0.87) !important;
        }
        
        /* Texte und Überschriften */
        h1, h2, h3, h4, h5, h6 {
            color: rgba(255, 255, 255, 0.87) !important;
        }
        
        .text-muted {
            color: rgba(255, 255, 255, 0.6) !important;
        }
        
        /* Karteikarten-spezifische Styles */
        .flashcard {
            perspective: 1000px !important;
        }
        
        .flashcard-inner {
            transition: transform 0.6s !important;
            transform-style: preserve-3d !important;
        }
        
        .flashcard-front, .flashcard-back {
            -webkit-backface-visibility: hidden !important;
            backface-visibility: hidden !important;
            background-color: #1f1f1f !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
        }
        
        .flashcard-back {
            transform: rotateY(180deg) !important;
            background-color: #2b2b2b !important;
        }
        
        .flashcard.flipped .flashcard-inner {
            transform: rotateY(180deg) !important;
        }
        
        .word {
            color: #7c4dff !important;
            font-weight: bold !important;
        }
        
        /* Footer */
        footer {
            background-color: #1e1e1e !important;
            border-top: 1px solid rgba(255, 255, 255, 0.12) !important;
            color: rgba(255, 255, 255, 0.6) !important;
        }
        
        /* Firefox-spezifische Styles */
        @-moz-document url-prefix() {
            body, html {
                background-color: #121212 !important;
                color: rgba(255, 255, 255, 0.87) !important;
            }
            
            .card {
                background-color: #1e1e1e !important;
            }
            
            .form-control {
                background-color: #2d2d2d !important;
                color: rgba(255, 255, 255, 0.87) !important;
            }
        }
        
        /* Safari-Fix */
        @media not all and (min-resolution:.001dpcm) { 
            @supports (-webkit-appearance:none) {
                html, body { 
                    background-color: #121212 !important; 
                    color: rgba(255, 255, 255, 0.87) !important;
                }
                
                .card {
                    background-color: #1e1e1e !important;
                }
                
                .form-control {
                    background-color: #2d2d2d !important;
                    color: rgba(255, 255, 255, 0.87) !important;
                }
            }
        }
    </style>
    
    <!-- Sofortiges Dark Mode Script - läuft vor DOM-Aufbau -->
    <script>
        // Sofortige Anwendung von Dark-Mode-Farben vor DOM-Laden
        (function() {
            document.documentElement.style.backgroundColor = "#121212";
            document.documentElement.style.color = "rgba(255, 255, 255, 0.87)";
            
            // Event für DOM-Fertigstellung
            document.addEventListener('DOMContentLoaded', function() {
                document.body.style.backgroundColor = "#121212";
                
                // Explizite Browser-Erkennung
                var isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
                var isFirefox = navigator.userAgent.indexOf("Firefox") > -1;
                
                // Safari-spezifische Fixes
                if (isSafari) {
                    document.body.classList.add('safari');
                    document.documentElement.style.backgroundColor = "#121212";
                    document.body.style.backgroundColor = "#121212";
                    
                    var cards = document.querySelectorAll('.card');
                    cards.forEach(function(card) {
                        card.style.backgroundColor = "#1e1e1e";
                    });
                    
                    var forms = document.querySelectorAll('.form-control');
                    forms.forEach(function(form) {
                        form.style.backgroundColor = "#2d2d2d";
                        form.style.color = "rgba(255, 255, 255, 0.87)";
                    });
                }
                
                // Firefox-spezifische Fixes
                if (isFirefox) {
                    document.body.classList.add('firefox');
                    document.documentElement.style.backgroundColor = "#121212";
                    document.body.style.backgroundColor = "#121212";
                    
                    var cards = document.querySelectorAll('.card');
                    cards.forEach(function(card) {
                        card.style.backgroundColor = "#1e1e1e";
                    });
                    
                    var forms = document.querySelectorAll('.form-control');
                    forms.forEach(function(form) {
                        form.style.backgroundColor = "#2d2d2d";
                        form.style.color = "rgba(255, 255, 255, 0.87)";
                    });
                }
            });
        })();
    </script>