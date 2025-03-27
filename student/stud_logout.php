<?php
session_start();

// Check if the student is logged in
if (isset($_SESSION['student_logged_in']) && $_SESSION['student_logged_in'] === true) {
    // Unset and destroy the student session
    session_unset();
    session_destroy();
    
    // Redirect to the student login page
    header('Location: ../index.html');
    exit();
} else {
    // If not logged in, redirect to the login page
    header('Location: ../index.html');
    exit();
}
?>
