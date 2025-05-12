<?php
// Start the session: must be the first command 
session_start();

// Code zum Erstellen eines Admin-Accounts (beim ersten Laden der Seite)
// Diese Funktion sollte nur einmal ausgeführt werden, du kannst sie später entfernen
function createAdminAccount() {
    // Database connection settings
    $servername = "sql108.infinityfree.com";
    $username = "if0_38905283";
    $password = "ewgjt0aaksuC";
    $dbname = "if0_38905283_sprachapp";
    
    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        return false;
    }
    
    // Check if admin with username 'nblaettnerAdmin' already exists
    $check_sql = "SELECT adminname FROM admin WHERE adminname = 'nblaettnerAdmin'";
    $result = $conn->query($check_sql);
    
    if ($result->num_rows == 0) {
        // Admin doesn't exist, create it
        $admin_username = 'nblaettnerAdmin';
        $admin_password = password_hash('12345678', PASSWORD_DEFAULT);
        $admin_email = 'niki.craft8873@outlook.de'; // Du kannst diese E-Mail ändern
        
        $insert_sql = "INSERT INTO admin (adminname, email, password) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("sss", $admin_username, $admin_email, $admin_password);
        $insert_stmt->execute();
        $insert_stmt->close();
    }
    
    $conn->close();
    return true;
}

// Führe die Admin-Erstellung aus (nur beim ersten Laden)


// Check if the form was submitted
if (isset($_POST['register'])) {
    // Process registration form
    if (!isset($_POST['username']) || !isset($_POST['password']) || !isset($_POST['email']) || !isset($_POST['role'])) {
        $_SESSION['err'] = "Username, password, email, or role is missing";
    } else {
        $user = $_POST['username'];
        $pass = $_POST['password'];
        $email = $_POST['email'];
        $role = $_POST['role'];
        
        // Validate input
        if (empty($user) || empty($pass) || empty($email)) {
            $_SESSION['err'] = "Username, password, or email is empty";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['err'] = "Invalid email format";
        } elseif ($role === 'admin') {
            // Blockiere Admin-Registrierungen
            $_SESSION['err'] = "Admin-Registrierungen sind nicht gestattet";
        } else {
            // Database connection settings
            $servername = "sql108.infinityfree.com";
            $username = "if0_38905283";
            $password = "ewgjt0aaksuC";
            $dbname = "if0_38905283_sprachapp";
            
            // Create connection
            $conn = new mysqli($servername, $username, $password, $dbname);
            
            // Check connection
            if ($conn->connect_error) {
                $_SESSION['err'] = "Database connection failed: " . $conn->connect_error;
            } else {
                // Determine which table to insert into based on role
                if ($role === 'lehrer') {
                    // Check if teacher username or email already exists
                    $check_sql = "SELECT teachername, email FROM teacher WHERE teachername = ? OR email = ?";
                    $insert_sql = "INSERT INTO teacher (teachername, email, password) VALUES (?, ?, ?)";
                    $name_field = "teachername";
                } else {
                    // Check if student username or email already exists
                    $check_sql = "SELECT studentname, email FROM student WHERE studentname = ? OR email = ?";
                    $insert_sql = "INSERT INTO student (studentname, email, password) VALUES (?, ?, ?)";
                    $name_field = "studentname";
                }
                
                // NEUE CODE: Zuerst prüfen, ob der Benutzername bereits als Admin existiert
                $admin_check_sql = "SELECT adminname FROM admin WHERE adminname = ?";
                $admin_check_stmt = $conn->prepare($admin_check_sql);
                $admin_check_stmt->bind_param("s", $user);
                $admin_check_stmt->execute();
                $admin_result = $admin_check_stmt->get_result();

                if ($admin_result->num_rows > 0) {
                    $_SESSION['err'] = "Dieser Benutzername ist bereits vergeben";
                    $admin_check_stmt->close();
                    $conn->close();
                } else {
                    $admin_check_stmt->close();
                    
                    // Check if username or email already exists
                    $check_stmt = $conn->prepare($check_sql);
                    $check_stmt->bind_param("ss", $user, $email);
                    $check_stmt->execute();
                    
                    if ($check_stmt->error) {
                        $_SESSION['err'] = "Check error: " . $check_stmt->error;
                    } else {
                        $result = $check_stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            // Username or email already exists
                            $row = $result->fetch_assoc();
                            if (isset($row[$name_field]) && $row[$name_field] === $user) {
                                $_SESSION['err'] = "Username already exists";
                            } else {
                                $_SESSION['err'] = "Email already exists";
                            }
                        } else {
                            // Hash the password for secure storage
                            $hashed_password = password_hash($pass, PASSWORD_DEFAULT);
                            
                            // Insert new user with hashed password
                            $insert_stmt = $conn->prepare($insert_sql);
                            $insert_stmt->bind_param("sss", $user, $email, $hashed_password);
                            $insert_stmt->execute();
                            
                            if ($insert_stmt->error) {
                                $_SESSION['err'] = "Insert error: " . $insert_stmt->error;
                            } else {
                                if ($insert_stmt->affected_rows > 0) {
                                    // Registration successful
                                    $_SESSION['success'] = "Registration successful! You can now log in.";
                                    // Redirect to login page
                                    header("Location: login.php");
                                    exit();
                                } else {
                                    $_SESSION['err'] = "Registration failed - no rows affected";
                                }
                            }
                            $insert_stmt->close();
                        }
                    }
                    
                    // Close resources
                    $check_stmt->close();
                    $conn->close();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrieren - SprachApp</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .btn-group {
            margin-top: 1.5rem;
        }
        .alert {
            margin-bottom: 1rem;
        }
        .logo-section {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .logo-section h1 {
            font-weight: bold;
            color: #0d6efd;
        }
        .role-group {
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-section">
            <h1>SprachApp</h1>
            <p class="text-muted">Registrieren Sie ein neues Konto</p>
        </div>
        
        <?php
        // Display error message if exists
        if (isset($_SESSION['err'])) {
            echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['err']) . '</div>';
            unset($_SESSION['err']);
        }
        
        // Display success message if exists
        if (isset($_SESSION['success'])) {
            echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success']) . '</div>';
            unset($_SESSION['success']);
        }
        ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <div class="form-group">
                <label for="username" class="form-label">Benutzername</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="email" class="form-label">E-Mail</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Passwort</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Rolle</label>
                <div class="role-group">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="role" id="role-schueler" value="schueler" checked>
                        <label class="form-check-label" for="role-schueler">Schüler</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="role" id="role-lehrer" value="lehrer">
                        <label class="form-check-label" for="role-lehrer">Lehrer</label>
                    </div>
                    <!-- Admin-Option entfernt -->
                </div>
            </div>
            
            <div class="btn-group w-100">
                <button type="submit" name="register" class="btn btn-primary w-100">Registrieren</button>
            </div>
            
            <div class="text-center mt-3">
                <p class="mb-0">Bereits ein Konto? <a href="login.php">Anmelden</a></p>
                <p class="mt-3"><a href="index.php">Zurück zur Startseite</a></p>
            </div>
        </form>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>