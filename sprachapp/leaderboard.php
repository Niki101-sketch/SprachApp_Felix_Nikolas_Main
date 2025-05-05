<?php
require_once 'config.php';
requireLogin();

// Bestenliste abrufen (Top 50)
function getLeaderboard($limit = 50) {
    $sql = "SELECT l.*, u.username 
            FROM leaderboard l 
            JOIN users u ON l.user_id = u.user_id 
            ORDER BY l.score DESC 
            LIMIT ?";
    return dbQuery($sql, [$limit], true);
}

$leaderboard = getLeaderboard();

$pageTitle = "Bestenliste";
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - SprachApp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="css/dark-theme.css">
    <style>
        /* Inline-Styles für die Bestenliste */
        :root {
            --gold-color: #FFD700;
            --silver-color: #C0C0C0;
            --bronze-color: #CD7F32;
        }
        
        body {
            background-color: var(--dark-bg);
            color: var(--dark-text-primary);
            font-family: 'Roboto', sans-serif;
        }
        
        /* Tabellenstile */
        .table {
            color: var(--dark-text-primary) !important;
        }
        
        .table thead th {
            color: var(--dark-primary-light) !important;
            border-bottom: 2px solid var(--dark-border);
            font-weight: 600;
        }
        
        .table td, .table th {
            color: var(--dark-text-primary) !important;
            border-top: 1px solid var(--dark-border);
            vertical-align: middle;
        }
        
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.08);
        }
        
        /* Tabellenzellen formatieren */
        .table td {
            padding: 0.75rem;
        }
        
        /* Top-3-Platzierungen */
        .gold-rank {
            color: var(--gold-color) !important;
            font-weight: bold;
        }
        
        .silver-rank {
            color: var(--silver-color) !important;
            font-weight: bold;
        }
        
        .bronze-rank {
            color: var(--bronze-color) !important;
            font-weight: bold;
        }
        
        /* Current User Hervorhebung */
        .current-user {
            background-color: rgba(98, 0, 238, 0.15) !important;
        }
        
        /* Animation für die Tabelle */
        .fadeInUp {
            animation: fadeInUp 0.5s ease forwards;
        }
        
        @keyframes fadeInUp {
            0% {
                opacity: 0;
                transform: translateY(20px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Verzögerte Animation für Tabellenzeilen */
        tr:nth-child(1) { animation-delay: 0.1s; }
        tr:nth-child(2) { animation-delay: 0.15s; }
        tr:nth-child(3) { animation-delay: 0.2s; }
        tr:nth-child(4) { animation-delay: 0.25s; }
        tr:nth-child(5) { animation-delay: 0.3s; }
        tr:nth-child(6) { animation-delay: 0.35s; }
        tr:nth-child(7) { animation-delay: 0.4s; }
        tr:nth-child(8) { animation-delay: 0.45s; }
        tr:nth-child(9) { animation-delay: 0.5s; }
        tr:nth-child(10) { animation-delay: 0.55s; }
        
        /* Trophäen-Animation */
        @keyframes trophyShine {
            0% { transform: scale(1) rotate(0deg); opacity: 1; }
            50% { transform: scale(1.2) rotate(5deg); opacity: 1; }
            100% { transform: scale(1) rotate(0deg); opacity: 1; }
        }
        
        .fa-trophy {
            animation: trophyShine 2s infinite;
            display: inline-block;
        }
        
        /* Punkte-Badge */
        .badge.bg-primary {
            background-color: var(--dark-primary) !important;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        /* Fire-Animation */
        @keyframes flicker {
            0% { opacity: 0.8; }
            50% { opacity: 1; }
            100% { opacity: 0.8; }
        }
        
        .fa-fire {
            animation: flicker 1.5s infinite;
            color: var(--dark-warning) !important;
        }
        
        /* Punktesystem-Box */
        .point-system {
            background-color: var(--dark-surface-light);
            padding: 1.5rem;
            border-radius: 10px;
            margin-top: 2rem;
            border-left: 4px solid var(--dark-primary);
        }
        
        .point-system h4 {
            color: var(--dark-primary-light);
            margin-bottom: 1rem;
        }
        
        .point-system ul {
            padding-left: 1.5rem;
        }
        
        .point-system li {
            margin-bottom: 0.5rem;
            color: var(--dark-text-primary);
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Startseite</a></li>
                        <li class="breadcrumb-item active">Bestenliste</li>
                    </ol>
                </nav>
                
                <div class="card mb-4 animate__animated animate__fadeIn">
                    <div class="card-header bg-primary text-white">
                        <h2><i class="fas fa-trophy"></i> Bestenliste</h2>
                    </div>
                    <div class="card-body">
                        <?php if (empty($leaderboard)): ?>
                        <div class="alert alert-info">
                            Noch keine Einträge in der Bestenliste.
                        </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Rang</th>
                                            <th>Benutzer</th>
                                            <th>Punkte</th>
                                            <th>Streak</th>
                                            <th>Wörter gelernt</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($leaderboard as $index => $entry): ?>
                                        <?php 
                                        // Hervorheben des aktuellen Benutzers
                                        $isCurrentUser = $entry['user_id'] == $_SESSION['user_id'];
                                        $rank = $index + 1;
                                        $rankClass = '';
                                        
                                        if ($rank === 1) {
                                            $rankClass = 'gold-rank'; // Gold
                                        } else if ($rank === 2) {
                                            $rankClass = 'silver-rank'; // Silber
                                        } else if ($rank === 3) {
                                            $rankClass = 'bronze-rank'; // Bronze
                                        }
                                        ?>
                                        <tr class="<?= $isCurrentUser ? 'current-user' : '' ?> fadeInUp">
                                            <td>
                                                <?php if ($rank <= 3): ?>
                                                <span class="<?= $rankClass ?>">
                                                    <i class="fas fa-trophy"></i> <?= $rank ?>
                                                </span>
                                                <?php else: ?>
                                                <span style="color: var(--dark-text-primary);"><?= $rank ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span style="color: var(--dark-text-primary) !important;"><?= h($entry['username']) ?></span>
                                                <?= $isCurrentUser ? ' <span class="badge bg-info">Du</span>' : '' ?>
                                            </td>
                                            <td><span class="badge bg-primary rounded-pill"><?= $entry['score'] ?></span></td>
                                            <td>
                                                <i class="fas fa-fire"></i> <span style="color: var(--dark-text-primary) !important;"><?= $entry['streak_days'] ?> Tage</span>
                                            </td>
                                            <td><span style="color: var(--dark-text-primary) !important;"><?= $entry['words_learned'] ?></span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                        
                        <div class="point-system mt-4">
                            <h4><i class="fas fa-info-circle"></i> Punktesystem</h4>
                            <ul>
                                <li>10 Punkte für jedes gelernte Wort</li>
                                <li>5 Punkte für jeden Tag deiner Streak</li>
                                <li>Lerne täglich, um deine Streak zu erhalten und mehr Punkte zu sammeln!</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="mt-5 py-4" style="background-color: var(--dark-surface); border-top: 1px solid var(--dark-border);">
        <div class="container">
            <div class="text-center">
                <p>&copy; <?= date('Y') ?> SprachApp. Alle Rechte vorbehalten.</p>
            </div>
        </div>
    </footer>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.3/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>