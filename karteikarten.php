<?php
session_start();
include 'connection.php';

$unitid = $_GET['unit'];
$studentid = 1; // Fest für Demo

// Wenn Button geklickt wurde
if (isset($_POST['answer'])) {
    $gvocabid = $_POST['gvocabid'];
    $evocabid = $_POST['evocabid'];
    $answer = $_POST['answer'];
    
    if ($answer == 'right') {
        $sql = "INSERT INTO vocabright (studentid, gvocabid, evocabid) VALUES ($studentid, $gvocabid, $evocabid)";
    } else {
        $sql = "INSERT INTO vocabwrong (studentid, gvocabid, evocabid) VALUES ($studentid, $gvocabid, $evocabid)";
    }
    $conn->query($sql);
}

// Vokabeln holen
$sql = "SELECT vg.gvocabid, vg.german_word, ve.evocabid, ve.english_word 
        FROM vocabgerman vg, vocabenglish ve, vocabmapping vm 
        WHERE vg.unitid = $unitid AND ve.unitid = $unitid 
        AND vm.gvocabid = vg.gvocabid AND vm.evocabid = ve.evocabid";
$result = $conn->query($sql);
$words = [];
while($row = $result->fetch_assoc()) {
    $words[] = $row;
}

$current = $_GET['current'] ?? 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Karteikarten</title>
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
    </style>
</head>
<body>
    <!-- Navigation Bar für Login/Registrieren -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="index2.php">SprachApp</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </nav>
    <div class="container mt-4">
        <h1>Unit <?php echo $unitid; ?></h1>
        
        <?php if (!empty($words) && $current < count($words)): ?>
            <div class="card text-center">
                <div class="card-body">
                    <?php if (!isset($_POST['show'])): ?>
                        <h2><?php echo $words[$current]['german_word']; ?></h2>
                        <form method="post">
                            <button type="submit" name="show" class="btn btn-secondary">Umdrehen</button>
                            <input type="hidden" name="current" value="<?php echo $current; ?>">
                        </form>
                    <?php else: ?>
                        <h2><?php echo $words[$current]['english_word']; ?></h2>
                        <form method="post" action="?unit=<?php echo $unitid; ?>&current=<?php echo $current + 1; ?>">
                            <button type="submit" name="answer" value="right" class="btn btn-success">Richtig</button>
                            <button type="submit" name="answer" value="wrong" class="btn btn-danger">Falsch</button>
                            <input type="hidden" name="gvocabid" value="<?php echo $words[$current]['gvocabid']; ?>">
                            <input type="hidden" name="evocabid" value="<?php echo $words[$current]['evocabid']; ?>">
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <p>Keine Vokabeln mehr!</p>
            <a href="einheiten.php" class="btn btn-primary">Zurück</a>
        <?php endif; ?>
    </div>
</body>
</html>