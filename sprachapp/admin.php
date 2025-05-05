<?php
require_once 'config.php';
require_once 'vocabulary.php';
requireAdmin(); // Nur Admins erlauben

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'dashboard';

// AJAX-Anfragen verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'create_unit':
            $unitName = $_POST['unit_name'] ?? '';
            $description = $_POST['description'] ?? '';
            $isPublic = isset($_POST['is_public']) && $_POST['is_public'] === 'true';
            
            $result = createUnit($unitName, $description, $userId, $isPublic);
            echo json_encode($result);
            break;
            
        case 'update_unit':
            $unitId = (int)($_POST['unit_id'] ?? 0);
            $unitName = $_POST['unit_name'] ?? '';
            $description = $_POST['description'] ?? '';
            $isPublic = isset($_POST['is_public']) && $_POST['is_public'] === 'true';
            
            $result = updateUnit($unitId, $unitName, $description, $isPublic);
            echo json_encode($result);
            break;
            
        case 'delete_unit':
            $unitId = (int)($_POST['unit_id'] ?? 0);
            
            $result = deleteUnit($unitId);
            echo json_encode($result);
            break;
            
        case 'create_vocab':
            $unitId = (int)($_POST['unit_id'] ?? 0);
            $germanWord = $_POST['german_word'] ?? '';
            $englishWord = $_POST['english_word'] ?? '';
            
            $result = createVocabulary($germanWord, $englishWord, $unitId);
            echo json_encode($result);
            break;
            
        case 'update_vocab':
            $vocabId = (int)($_POST['vocab_id'] ?? 0);
            $germanWord = $_POST['german_word'] ?? '';
            $englishWord = $_POST['english_word'] ?? '';
            
            $result = updateVocabulary($vocabId, $germanWord, $englishWord);
            echo json_encode($result);
            break;
            
        case 'delete_vocab':
            $vocabId = (int)($_POST['vocab_id'] ?? 0);
            
            $result = deleteVocabulary($vocabId);
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Unbekannte Aktion']);
    }
    
    exit;
}

// Admin-Daten für das Dashboard laden
$totalUnits = count(supabaseQuery("units", 'GET') ?? []);
$totalVocabulary = count(supabaseQuery("vocabulary", 'GET') ?? []);
$totalUsers = count(supabaseQuery("users", 'GET') ?? []);

// Einheiten des Administrators laden
$adminUnits = getUserUnits($userId);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin-Bereich - SprachApp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?= $action === 'dashboard' ? 'active' : '' ?>" href="admin.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $action === 'units' ? 'active' : '' ?>" href="admin.php?action=units">
                                <i class="fas fa-folder me-2"></i> Einheiten verwalten
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $action === 'vocabulary' ? 'active' : '' ?>" href="admin.php?action=vocabulary">
                                <i class="fas fa-book me-2"></i> Vokabeln verwalten
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $action === 'users' ? 'active' : '' ?>" href="admin.php?action=users">
                                <i class="fas fa-users me-2"></i> Benutzer verwalten
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <?php if ($action === 'dashboard'): ?>
                    <!-- Dashboard -->
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1><i class="fas fa-folder"></i> Einheiten verwalten</h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <a href="admin.php?action=create_unit" class="btn btn-sm btn-success">
                                <i class="fas fa-plus"></i> Neue Einheit
                            </a>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Beschreibung</th>
                                            <th>Öffentlich</th>
                                            <th>Vokabeln</th>
                                            <th>Erstellt am</th>
                                            <th>Aktionen</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // Alle Einheiten abrufen
                                        $allUnits = supabaseQuery("units?select=*,users!inner(username)&order=created_at.desc", 'GET');
                                        if (empty($allUnits)): 
                                        ?>
                                        <tr>
                                            <td colspan="7" class="text-center">Keine Einheiten gefunden.</td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($allUnits as $unit): ?>
                                            <?php 
                                            // Anzahl der Vokabeln in dieser Einheit abrufen
                                            $unitVocabs = getUnitVocabulary($unit['unit_id']);
                                            $vocabCount = count($unitVocabs);
                                            ?>
                                            <tr>
                                                <td><?= $unit['unit_id'] ?></td>
                                                <td><?= h($unit['unit_name']) ?></td>
                                                <td><?= h(substr($unit['description'], 0, 50)) ?>...</td>
                                                <td><?= $unit['is_public'] ? '<span class="badge bg-success">Ja</span>' : '<span class="badge bg-secondary">Nein</span>' ?></td>
                                                <td><?= $vocabCount ?></td>
                                                <td><?= date('d.m.Y', strtotime($unit['created_at'])) ?></td>
                                                <td>
                                                    <a href="admin.php?action=edit_unit&id=<?= $unit['unit_id'] ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="admin.php?action=vocabulary&unit_id=<?= $unit['unit_id'] ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-book"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-danger delete-unit" data-id="<?= $unit['unit_id'] ?>" data-name="<?= h($unit['unit_name']) ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                
                <?php elseif ($action === 'create_unit' || $action === 'edit_unit'): ?>
                    <?php
                    // Einheit bearbeiten
                    $unitId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
                    $isEdit = $action === 'edit_unit' && $unitId > 0;
                    $unit = null;
                    
                    if ($isEdit) {
                        $unitData = supabaseQuery("units?unit_id=eq.$unitId", 'GET');
                        if (!empty($unitData)) {
                            $unit = $unitData[0];
                        } else {
                            // Einheit nicht gefunden
                            echo '<div class="alert alert-danger">Einheit nicht gefunden.</div>';
                            echo '<a href="admin.php?action=units" class="btn btn-primary">Zurück zur Übersicht</a>';
                            exit;
                        }
                    }
                    ?>
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1>
                            <i class="fas fa-folder"></i> 
                            <?= $isEdit ? 'Einheit bearbeiten' : 'Neue Einheit erstellen' ?>
                        </h1>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <form id="unit-form">
                                <?php if ($isEdit): ?>
                                <input type="hidden" name="action" value="update_unit">
                                <input type="hidden" name="unit_id" value="<?= $unitId ?>">
                                <?php else: ?>
                                <input type="hidden" name="action" value="create_unit">
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <label for="unit_name" class="form-label">Name der Einheit</label>
                                    <input type="text" class="form-control" id="unit_name" name="unit_name" required 
                                           value="<?= $isEdit ? h($unit['unit_name']) : '' ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Beschreibung</label>
                                    <textarea class="form-control" id="description" name="description" rows="4"><?= $isEdit ? h($unit['description']) : '' ?></textarea>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="is_public" name="is_public" 
                                           <?= (!$isEdit || $unit['is_public']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_public">Öffentlich (für alle Benutzer sichtbar)</label>
                                </div>
                                
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <?= $isEdit ? 'Aktualisieren' : 'Erstellen' ?>
                                    </button>
                                    <a href="admin.php?action=units" class="btn btn-secondary">Abbrechen</a>
                                </div>
                            </form>
                            
                            <div id="form-result" class="mt-3"></div>
                        </div>
                    </div>
                    
                <?php elseif ($action === 'vocabulary'): ?>
                    <?php
                    // Vokabeln einer bestimmten Einheit verwalten
                    $unitId = isset($_GET['unit_id']) ? (int)$_GET['unit_id'] : 0;
                    
                    if ($unitId > 0) {
                        // Einheit abrufen
                        $unitData = supabaseQuery("units?unit_id=eq.$unitId", 'GET');
                        if (!empty($unitData)) {
                            $unit = $unitData[0];
                            
                            // Vokabeln dieser Einheit abrufen
                            $vocabularies = getUnitVocabulary($unitId);
                        } else {
                            // Einheit nicht gefunden
                            echo '<div class="alert alert-danger">Einheit nicht gefunden.</div>';
                            echo '<a href="admin.php?action=units" class="btn btn-primary">Zurück zur Übersicht</a>';
                            exit;
                        }
                    }
                    ?>
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1>
                            <i class="fas fa-book"></i> 
                            <?= $unitId > 0 ? 'Vokabeln für: ' . h($unit['unit_name']) : 'Vokabeln verwalten' ?>
                        </h1>
                        <?php if ($unitId > 0): ?>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addVocabModal">
                                <i class="fas fa-plus"></i> Neue Vokabel
                            </button>
                            <button class="btn btn-sm btn-primary ms-2" data-bs-toggle="modal" data-bs-target="#bulkImportModal">
                                <i class="fas fa-file-import"></i> Massenimport
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($unitId > 0): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="vocabulary-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Deutsch</th>
                                            <th>Englisch</th>
                                            <th>Erstellt am</th>
                                            <th>Aktionen</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($vocabularies)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">Keine Vokabeln in dieser Einheit.</td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($vocabularies as $vocab): ?>
                                            <tr>
                                                <td><?= $vocab['vocab_id'] ?></td>
                                                <td><?= h($vocab['german_word']) ?></td>
                                                <td><?= h($vocab['english_word']) ?></td>
                                                <td><?= date('d.m.Y', strtotime($vocab['created_at'])) ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-info edit-vocab" 
                                                            data-id="<?= $vocab['vocab_id'] ?>"
                                                            data-german="<?= h($vocab['german_word']) ?>"
                                                            data-english="<?= h($vocab['english_word']) ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger delete-vocab" 
                                                            data-id="<?= $vocab['vocab_id'] ?>"
                                                            data-german="<?= h($vocab['german_word']) ?>"
                                                            data-english="<?= h($vocab['english_word']) ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Neue Vokabel Modal -->
                    <div class="modal fade" id="addVocabModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Neue Vokabel hinzufügen</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="vocab-form">
                                        <input type="hidden" name="action" value="create_vocab">
                                        <input type="hidden" name="unit_id" value="<?= $unitId ?>">
                                        
                                        <div class="mb-3">
                                            <label for="german_word" class="form-label">Deutsch</label>
                                            <input type="text" class="form-control" id="german_word" name="german_word" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="english_word" class="form-label">Englisch</label>
                                            <input type="text" class="form-control" id="english_word" name="english_word" required>
                                        </div>
                                        
                                        <div id="vocab-form-result"></div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                                    <button type="button" class="btn btn-primary" id="save-vocab">Speichern</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Vokabel bearbeiten Modal -->
                    <div class="modal fade" id="editVocabModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Vokabel bearbeiten</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="edit-vocab-form">
                                        <input type="hidden" name="action" value="update_vocab">
                                        <input type="hidden" name="vocab_id" id="edit_vocab_id">
                                        
                                        <div class="mb-3">
                                            <label for="edit_german_word" class="form-label">Deutsch</label>
                                            <input type="text" class="form-control" id="edit_german_word" name="german_word" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="edit_english_word" class="form-label">Englisch</label>
                                            <input type="text" class="form-control" id="edit_english_word" name="english_word" required>
                                        </div>
                                        
                                        <div id="edit-vocab-form-result"></div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                                    <button type="button" class="btn btn-primary" id="update-vocab">Aktualisieren</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Vokabel löschen Bestätigungsmodal -->
                    <div class="modal fade" id="deleteVocabModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Vokabel löschen</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Möchten Sie die folgende Vokabel wirklich löschen?</p>
                                    <p><strong id="delete-vocab-info"></strong></p>
                                    <input type="hidden" id="delete_vocab_id">
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                                    <button type="button" class="btn btn-danger" id="confirm-delete-vocab">Löschen</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Massenimport Modal -->
                    <div class="modal fade" id="bulkImportModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Vokabeln massenimportieren</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Fügen Sie Ihre Vokabeln im Format "Deutsch;Englisch" ein, jede Vokabel in einer neuen Zeile.</p>
                                    <form id="bulk-import-form">
                                        <input type="hidden" name="unit_id" value="<?= $unitId ?>">
                                        
                                        <div class="mb-3">
                                            <textarea class="form-control" id="bulk_vocab" name="bulk_vocab" rows="10" placeholder="Haus;house&#10;Auto;car&#10;Katze;cat"></textarea>
                                        </div>
                                        
                                        <div id="bulk-import-result"></div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                                    <button type="button" class="btn btn-primary" id="import-vocab">Importieren</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php else: ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="alert alert-info">
                                <p>Bitte wählen Sie eine Einheit aus, um deren Vokabeln zu verwalten.</p>
                            </div>
                            
                            <div class="list-group">
                                <?php foreach ($adminUnits as $unit): ?>
                                <a href="admin.php?action=vocabulary&unit_id=<?= $unit['unit_id'] ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1"><?= h($unit['unit_name']) ?></h5>
                                        <?php 
                                        // Anzahl der Vokabeln in dieser Einheit abrufen
                                        $unitVocabs = getUnitVocabulary($unit['unit_id']);
                                        $vocabCount = count($unitVocabs);
                                        ?>
                                        <span class="badge bg-primary rounded-pill"><?= $vocabCount ?> Vokabeln</span>
                                    </div>
                                    <p class="mb-1"><?= h(substr($unit['description'], 0, 100)) ?>...</p>
                                    <small><?= $unit['is_public'] ? 'Öffentlich' : 'Privat' ?> • Erstellt am <?= date('d.m.Y', strtotime($unit['created_at'])) ?></small>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                <?php elseif ($action === 'users'): ?>
                    <!-- Benutzer verwalten -->
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1><i class="fas fa-users"></i> Benutzer verwalten</h1>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Benutzername</th>
                                            <th>E-Mail</th>
                                            <th>Admin</th>
                                            <th>Streak</th>
                                            <th>Wörter gelernt</th>
                                            <th>Einheiten gelernt</th>
                                            <th>Registriert am</th>
                                            <th>Aktionen</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // Alle Benutzer abrufen
                                        $allUsers = supabaseQuery("users?order=created_at.desc", 'GET');
                                        if (empty($allUsers)): 
                                        ?>
                                        <tr>
                                            <td colspan="9" class="text-center">Keine Benutzer gefunden.</td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($allUsers as $user): ?>
                                            <tr>
                                                <td><?= $user['user_id'] ?></td>
                                                <td><?= h($user['username']) ?></td>
                                                <td><?= h($user['email']) ?></td>
                                                <td><?= $user['is_admin'] ? '<span class="badge bg-danger">Ja</span>' : '<span class="badge bg-secondary">Nein</span>' ?></td>
                                                <td><?= $user['streak_days'] ?></td>
                                                <td><?= $user['total_words_learned'] ?></td>
                                                <td><?= $user['total_units_learned'] ?></td>
                                                <td><?= date('d.m.Y', strtotime($user['created_at'])) ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-info edit-user" 
                                                            data-id="<?= $user['user_id'] ?>"
                                                            data-username="<?= h($user['username']) ?>"
                                                            data-email="<?= h($user['email']) ?>"
                                                            data-admin="<?= $user['is_admin'] ? '1' : '0' ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.3/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Einheit erstellen/bearbeiten
            const unitForm = document.getElementById('unit-form');
            if (unitForm) {
                unitForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(unitForm);
                    const params = new URLSearchParams();
                    
                    for (const [key, value] of formData.entries()) {
                        if (key === 'is_public') {
                            params.append(key, 'true');
                        } else {
                            params.append(key, value);
                        }
                    }
                    
                    // Wenn is_public Checkbox nicht angekreuzt ist, trotzdem Wert senden
                    if (!formData.has('is_public')) {
                        params.append('is_public', 'false');
                    }
                    
                    fetch('admin.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: params
                    })
                    .then(response => response.json())
                    .then(data => {
                        const resultDiv = document.getElementById('form-result');
                        
                        if (data.success) {
                            resultDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                            if (formData.get('action') === 'create_unit') {
                                unitForm.reset();
                            }
                            
                            // Nach 2 Sekunden zur Einheitenübersicht umleiten
                            setTimeout(() => {
                                window.location.href = 'admin.php?action=units';
                            }, 2000);
                        } else {
                            resultDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                });
            }
            
            // Einheit löschen
            const deleteUnitButtons = document.querySelectorAll('.delete-unit');
            if (deleteUnitButtons.length > 0) {
                deleteUnitButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const unitId = this.dataset.id;
                        const unitName = this.dataset.name;
                        
                        if (confirm(`Möchten Sie die Einheit "${unitName}" wirklich löschen? Alle zugehörigen Vokabeln werden ebenfalls gelöscht.`)) {
                            const params = new URLSearchParams();
                            params.append('action', 'delete_unit');
                            params.append('unit_id', unitId);
                            
                            fetch('admin.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: params
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    alert(data.message);
                                    window.location.reload();
                                } else {
                                    alert(`Fehler: ${data.message}`);
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                            });
                        }
                    });
                });
            }
            
            // Vokabel hinzufügen
            const saveVocabButton = document.getElementById('save-vocab');
            if (saveVocabButton) {
                saveVocabButton.addEventListener('click', function() {
                    const form = document.getElementById('vocab-form');
                    const formData = new FormData(form);
                    const params = new URLSearchParams();
                    
                    for (const [key, value] of formData.entries()) {
                        params.append(key, value);
                    }
                    
                    fetch('admin.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: params
                    })
                    .then(response => response.json())
                    .then(data => {
                        const resultDiv = document.getElementById('vocab-form-result');
                        
                        if (data.success) {
                            resultDiv.innerHTML = `<div class="alert alert-success mt-3">${data.message}</div>`;
                            form.reset();
                            
                            // Nach 1 Sekunde Modal schließen und Seite neu laden
                            setTimeout(() => {
                                const modal = bootstrap.Modal.getInstance(document.getElementById('addVocabModal'));
                                modal.hide();
                                window.location.reload();
                            }, 1000);
                        } else {
                            resultDiv.innerHTML = `<div class="alert alert-danger mt-3">${data.message}</div>`;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                });
            }
            
            // Vokabel bearbeiten Modal öffnen
            const editVocabButtons = document.querySelectorAll('.edit-vocab');
            if (editVocabButtons.length > 0) {
                editVocabButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const vocabId = this.dataset.id;
                        const germanWord = this.dataset.german;
                        const englishWord = this.dataset.english;
                        
                        document.getElementById('edit_vocab_id').value = vocabId;
                        document.getElementById('edit_german_word').value = germanWord;
                        document.getElementById('edit_english_word').value = englishWord;
                        
                        const editModal = new bootstrap.Modal(document.getElementById('editVocabModal'));
                        editModal.show();
                    });
                });
            }
            
            // Vokabel aktualisieren
            const updateVocabButton = document.getElementById('update-vocab');
            if (updateVocabButton) {
                updateVocabButton.addEventListener('click', function() {
                    const form = document.getElementById('edit-vocab-form');
                    const formData = new FormData(form);
                    const params = new URLSearchParams();
                    
                    for (const [key, value] of formData.entries()) {
                        params.append(key, value);
                    }
                    
                    fetch('admin.php', {<i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <div class="card text-white bg-primary">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5 class="card-title">Einheiten</h5>
                                            <h2><?= $totalUnits ?></h2>
                                        </div>
                                        <div>
                                            <i class="fas fa-folder fa-3x"></i>
                                        </div>
                                    </div>
                                    <a href="admin.php?action=units" class="text-white">Verwalten <i class="fas fa-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-4">
                            <div class="card text-white bg-success">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5 class="card-title">Vokabeln</h5>
                                            <h2><?= $totalVocabulary ?></h2>
                                        </div>
                                        <div>
                                            <i class="fas fa-book fa-3x"></i>
                                        </div>
                                    </div>
                                    <a href="admin.php?action=vocabulary" class="text-white">Verwalten <i class="fas fa-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-4">
                            <div class="card text-white bg-info">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h5 class="card-title">Benutzer</h5>
                                            <h2><?= $totalUsers ?></h2>
                                        </div>
                                        <div>
                                            <i class="fas fa-users fa-3x"></i>
                                        </div>
                                    </div>
                                    <a href="admin.php?action=users" class="text-white">Verwalten <i class="fas fa-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h3>Meine Einheiten</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Beschreibung</th>
                                                    <th>Öffentlich</th>
                                                    <th>Vokabeln</th>
                                                    <th>Erstellt am</th>
                                                    <th>Aktionen</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($adminUnits)): ?>
                                                <tr>
                                                    <td colspan="6" class="text-center">Keine Einheiten gefunden.</td>
                                                </tr>
                                                <?php else: ?>
                                                    <?php foreach ($adminUnits as $unit): ?>
                                                    <?php 
                                                    // Anzahl der Vokabeln in dieser Einheit abrufen
                                                    $unitVocabs = getUnitVocabulary($unit['unit_id']);
                                                    $vocabCount = count($unitVocabs);
                                                    ?>
                                                    <tr>
                                                        <td><?= h($unit['unit_name']) ?></td>
                                                        <td><?= h(substr($unit['description'], 0, 50)) ?>...</td>
                                                        <td><?= $unit['is_public'] ? '<span class="badge bg-success">Ja</span>' : '<span class="badge bg-secondary">Nein</span>' ?></td>
                                                        <td><?= $vocabCount ?></td>
                                                        <td><?= date('d.m.Y', strtotime($unit['created_at'])) ?></td>
                                                        <td>
                                                            <a href="admin.php?action=edit_unit&id=<?= $unit['unit_id'] ?>" class="btn btn-sm btn-info">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="admin.php?action=vocabulary&unit_id=<?= $unit['unit_id'] ?>" class="btn btn-sm btn-primary">
                                                                <i class="fas fa-book"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <a href="admin.php?action=create_unit" class="btn btn-success">
                                        <i class="fas fa-plus"></i> Neue Einheit erstellen
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                <?php elseif ($action === 'units'): ?>
                    <!-- Einheiten verwalten -->
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1>