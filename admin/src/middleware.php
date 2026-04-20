<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Redirect to login page with error message
    header("Location: ../../context/Login.php?error=Access+denied");
    exit();
}
?>