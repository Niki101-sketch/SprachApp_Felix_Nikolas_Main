<?php
// Datenbankverbindung

//include 'connectionlocalhost.php';
include 'connection.php';

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Verbindung fehlgeschlagen: " . $e->getMessage());
}

// Units aus der Datenbank laden
// Hier werden alle Units geladen, später kann nach Gruppe gefiltert werden
$stmt = $pdo->prepare("
    SELECT 
        u.unitid,
        u.unitname,
        COUNT(vg.gvocabid) as vocab_count
    FROM unit u
    LEFT JOIN vocabgerman vg ON u.unitid = vg.unitid
    GROUP BY u.unitid, u.unitname
    ORDER BY u.unitid
");
$stmt->execute();
$units = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Units</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
         .nav-item {
            margin: 0.5rem 0;
        }
        .nav-link {
            border-radius: 0.5rem;
            transition: all 0.3s;
        }
        .nav-link:hover {
            background-color: #0d6efd;
            color: white !important;
            transform: translateY(-3px);
        }
        .unit-card {
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .unit-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <!-- Navigation Bar für Login/Registrieren -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">SprachApp</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto auth-buttons">
                    <li class="nav-item">
                        <a href="login.php" class="btn btn-outline-primary">Login</a>
                    </li>
                    <li class="nav-item">
                        <a href="registrieren.php" class="btn btn-primary">Registrieren</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">
        <h1>Verfügbare Units</h1>
        
        <?php if (empty($units)): ?>
            <div class="alert alert-info" role="alert">
                Zurzeit sind keine Units verfügbar.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($units as $unit): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card unit-card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($unit['unitname']); ?></h5>
                                <p class="card-text">
                                    <?php echo $unit['vocab_count']; ?> Vokabel<?php echo $unit['vocab_count'] != 1 ? 'n' : ''; ?>
                                </p>
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="karteikarten.php?unit=<?php echo $unit['unitid']; ?>" class="btn btn-primary">
                                        <i class="bi bi-play-circle"></i> Start
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>