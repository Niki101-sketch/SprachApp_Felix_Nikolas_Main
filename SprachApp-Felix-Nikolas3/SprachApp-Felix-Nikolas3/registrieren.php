<?php
// Start the session: must be the first command 
session_start();

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
                } elseif ($role === 'admin') {
                    // Check if admin username or email already exists
                    $check_sql = "SELECT adminname, email FROM admin WHERE adminname = ? OR email = ?";
                    $insert_sql = "INSERT INTO admin (adminname, email, password) VALUES (?, ?, ?)";
                    $name_field = "adminname";
                } else {
                    // Check if student username or email already exists
                    $check_sql = "SELECT studentname, email FROM student WHERE studentname = ? OR email = ?";
                    $insert_sql = "INSERT INTO student (studentname, email, password) VALUES (?, ?, ?)";
                    $name_field = "studentname";
                }
                
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
                        // Insert new user
                        $insert_stmt = $conn->prepare($insert_sql);
                        $insert_stmt->bind_param("sss", $user, $email, $pass);
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrieren-SprachApp</title>
    <style>
        .red {
            color: red;
        }
        .green {
            color: green;
        }
        .container {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .role-group {
            margin: 10px 0;
        }
        .role-group label {
            margin-right: 15px;
            display: inline-block;
        }
        .btn-group {
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Registrieren</h1>
        
        <?php
        // Display error message if exists
        if (isset($_SESSION['err'])) {
            echo "<p class='red'>Registrierungsfehler: " . $_SESSION['err'] . "</p>";
            // Clear the error message
            unset($_SESSION['err']);
        }
        
        // Display success message if exists
        if (isset($_SESSION['success'])) {
            echo "<p class='green'>" . $_SESSION['success'] . "</p>";
            // Clear the success message
            unset($_SESSION['success']);
        }
        ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label>Role:</label>
                <div class="role-group">
                    <label><input type="radio" name="role" value="schueler" checked> Schüler</label>
                    <label><input type="radio" name="role" value="lehrer"> Lehrer</label>
                    <label><input type="radio" name="role" value="admin"> Admin</label>
                </div>
            </div>
            
            <div class="btn-group">
                <input type="reset" value="Reset">
                <input type="submit" value="Registrieren" name="register">
            </div>
            
            <div style="margin-top: 15px; text-align: center;">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </form>
    </div>
</body>
</html>
