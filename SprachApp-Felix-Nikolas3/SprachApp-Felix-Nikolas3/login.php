<?php 
// Start the session: must be the first command 
session_start(); 

// Check if the form was submitted
if (isset($_POST['login'])) {
    // Process login form
    if (!isset($_POST['username']) || !isset($_POST['password']) || !isset($_POST['role'])) {
        $_SESSION['err'] = "Username, password, or role is missing";
    } else {
        $user = $_POST['username'];
        $pass = $_POST['password'];
        $role = $_POST['role'];
        
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
                // Determine which table to query based on role
                if ($role === 'lehrer') {
                    $sql = "SELECT teacherid, teachername, password FROM teacher WHERE teachername = ? AND password = ?";
                    $id_field = "teacherid";
                    $username_field = "teachername";
                } elseif ($role === 'admin') {
                    $sql = "SELECT adminid, adminname, password FROM admin WHERE adminname = ? AND password = ?";
                    $id_field = "adminid";
                    $username_field = "adminname";
                } else {
                    $sql = "SELECT studentid, studentname, password FROM student WHERE studentname = ? AND password = ?";
                    $id_field = "studentid";
                    $username_field = "studentname";
                }
                
                // Prepare and execute query
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $user, $pass);
                $stmt->execute();
                
                if ($stmt->error) {
                    $_SESSION['err'] = "Query error: " . $stmt->error;
                } else {
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        // User found, set session variables
                        $row = $result->fetch_assoc();
                        
                        $_SESSION['authenticated'] = true;
                        $_SESSION['username'] = $user;
                        $_SESSION['role'] = $role;
                        $_SESSION['userid'] = $row[$id_field];
                        
                        // Redirect to success page
                        header("Location: index.php");
                        exit();
                    } else {
                        // Login failed
                        $_SESSION['err'] = "Invalid username or password for $role account";
                    }
                }
                
                // Close resources
                $stmt->close();
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
    <title>Login-SprachApp</title>
    <style>
        .red {
            color: red;
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
        <h1>Login</h1>
        
        <?php
        // Display error message if exists
        if (isset($_SESSION['err'])) {
            echo "<p class='red'>Login error: " . $_SESSION['err'] . "</p>";
            // Clear the error message
            unset($_SESSION['err']);
        }
        
        // Display success message if exists
        if (isset($_SESSION['success'])) {
            echo "<p style='color: green'>" . $_SESSION['success'] . "</p>";
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
                <input type="submit" value="Login" name="login">
            </div>
            
            <div style="margin-top: 15px; text-align: center;">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </form>
    </div>
</body>
</html>
