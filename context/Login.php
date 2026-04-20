<?php
// Include the database connection
include '../connection/db_connect.php';

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        // Prepare the SQL statement
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Password is correct; set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role']; // Assuming you have a 'role' column in your users table

            // Redirect based on role
           if (in_array($user['role'], ['admin', 'staff'])) {
    // Redirect admin or staff to admin dashboard
    header("Location: ../admin/src/index.php?login=success");
    exit;
} else {
    // Redirect regular users to homepage
    header("Location: ../index.php?login=success");
    exit;
}
        } else {
            echo "<script>alert('Invalid username or password.');</script>";
        }
    } catch (PDOException $e) {
        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/loginForm.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <title>EYC Login</title>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-left-side">
                <form action="" method="post" class="login-form">
                <h1 class="login-title">Login</h1>
                <div class="login-main-input">
                    <div class="login-input-container">
                        <i class="fa-regular fa-user"></i>
                        <input type="text" name="username" placeholder="Username" required>
                    </div>
                    <div class="login-input-container">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="password" placeholder="Password" required>
                    </div>
                    <div class="login-forgot-password">
                        <a href="#">Forgot password?</a>
                    </div>
                </div>
                <div class="login-button-container">
                    <button type="submit" class="login-submit-btn">Login</button>
                    <p class="login-signup-link">Not Register Yet? <a href="Signup.php">Sign Up</a></p>
                </div>
            </form>
        </div>
        <div class="login-right-side">
            <!-- Background image -->
        </div>
        </div>
    </div>
</body>
</html>