<?php
// Include the database connection
include '../connection/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
    $email = $_POST['email'];
    $mobile = $_POST['mobile'];

    try {
        // Prepare an SQL statement
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, mobile) VALUES (:username, :password, :email, :mobile)");

        // Bind parameters
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':mobile', $mobile);

        // Execute the statement
        $stmt->execute();

        // Redirect to index.php after successful signup
        echo "<script>alert('Sign up successful!');</script>";
        header("Location: Login.php");
        exit();
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../css/Signup.css">
    <title>EYC SignUp</title>
</head>
<body>
    <div class="signup-container">
        <!-- Right side first -->
        <div class="signup-right-side">
            <!-- Background image -->
        </div>

        <!-- Left side second -->
        <div class="signup-left-side">
            <form action="#" method="post" class="signup-form">
                <h1 class="signup-title">Sign Up</h1>
                <div class="signup-main-input">
                    <div class="signup-input-container">
                        <i class="fa-regular fa-user"></i>
                        <input type="text" name="username" placeholder="Username" required>
                    </div>
                    <div class="signup-input-container">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="password" placeholder="Password" required>
                    </div>
                    <div class="signup-input-container">
                        <i class="fa-regular fa-envelope"></i>
                        <input type="email" name="email" placeholder="Email" required>
                    </div>
                    <div class="signup-input-container">
                        <i class="fa-solid fa-phone"></i>
                        <input type="tel" name="mobile" placeholder="Mobile Number" required>
                    </div>
                </div>
                <div class="signup-button-container">
                    <button type="submit" class="signup-submit-btn">Sign Up</button>
                    <p class="signup-signin-link">Already have an account? <a href="Login.php">Sign In</a></p>
                </div>
            </form>
        </div>
    </div>
</body>
</html>