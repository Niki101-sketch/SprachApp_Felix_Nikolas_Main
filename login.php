<?php 
// Start the session: must be the first command 
session_start(); 

// Check if the form was submitted
if (isset($_POST['login'])) {
    // Process login form
    if (!isset($_POST['username']) || !isset($_POST['password'])) {
        $_SESSION['err'] = "Username or password is missing";
    } else {
        $user = $_POST['username'];
        $pass = $_POST['password'];
        
        // Validate input
        if (empty($user) || empty($pass)) {
            $_SESSION['err'] = "Username or password is empty";
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
                // First try student table
                $found = false;
                
                // Check in student table
                $sql = "SELECT studentid, studentname, password FROM student WHERE studentname = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $user);
                $stmt->execute();
                
                if (!$stmt->error) {
                    $result = $stmt->get_result();
                    if ($result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        if (password_verify($pass, $row['password'])) {
                            $_SESSION['authenticated'] = true;
                            $_SESSION['username'] = $user;
                            $_SESSION['role'] = 'schueler';
                            $_SESSION['userid'] = $row['studentid'];
                            $found = true;
                        }
                    }
                }
                $stmt->close();
                
                // If not found in student table, check teacher table
                if (!$found) {
                    $sql = "SELECT teacherid, teachername, password FROM teacher WHERE teachername = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("s", $user);
                    $stmt->execute();
                    
                    if (!$stmt->error) {
                        $result = $stmt->get_result();
                        if ($result->num_rows > 0) {
                            $row = $result->fetch_assoc();
                            if (password_verify($pass, $row['password'])) {
                                $_SESSION['authenticated'] = true;
                                $_SESSION['username'] = $user;
                                $_SESSION['role'] = 'lehrer';
                                $_SESSION['userid'] = $row['teacherid'];
                                $found = true;
                            }
                        }
                    }
                    $stmt->close();
                }
                
                // If not found in teacher table, check admin table
                if (!$found) {
                    $sql = "SELECT adminid, adminname, password FROM admin WHERE adminname = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("s", $user);
                    $stmt->execute();
                    
                    if (!$stmt->error) {
                        $result = $stmt->get_result();
                        if ($result->num_rows > 0) {
                            $row = $result->fetch_assoc();
                            if (password_verify($pass, $row['password'])) {
                                $_SESSION['authenticated'] = true;
                                $_SESSION['username'] = $user;
                                $_SESSION['role'] = 'admin';
                                $_SESSION['userid'] = $row['adminid'];
                                $found = true;
                            }
                        }
                    }
                    $stmt->close();
                }
                
                if ($found) {
                    // Redirect to index2.php instead of index.php
                    header("Location: index2.php");
                    exit();
                } else {
                    // Login failed
                    $_SESSION['err'] = "Invalid username or password";
                }
                
                // Close connection
                $conn->close();
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
    <title>Login - SprachApp</title>
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
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-section">
            <h1>SprachApp</h1>
            <p class="text-muted">Melden Sie sich an, um fortzufahren</p>
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
                <label for="password" class="form-label">Passwort</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            
            <div class="btn-group w-100">
                <button type="submit" name="login" class="btn btn-primary w-100">Anmelden</button>
            </div>
            
            <div class="text-center mt-3">
                <p class="mb-0">Noch kein Konto? <a href="registrieren.php">Registrieren</a></p>
                <p class="mt-3"><a href="index.php">Zurück zur Startseite</a></p>
            </div>
        </form>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>