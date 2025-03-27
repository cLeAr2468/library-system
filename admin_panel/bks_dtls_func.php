<?php
session_start(); // Start the session to use session variables
// Check if the admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin_panel/index.php'); // Redirect to login page
    exit();
}
include '../component-library/connect.php';
// Database connection
try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $user_name, $user_password);
    // Set PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
} catch (PDOException $e) {
    $_SESSION['message'] = 'Database connection failed: ' . $e->getMessage();
    $_SESSION['message_type'] = 'error';
    exit();
}

// Fetch book details and related books
$id = $_GET['id'] ?? null; // Changed from call_no to id
$book = null;
$relatedBooks = [];
if ($id) {
    try {
        // Fetch the main book details
        $details = $conn->prepare("SELECT * FROM books WHERE id = ?"); // Changed from call_no to id
        $details->execute([$id]);
        $book = $details->fetch(PDO::FETCH_ASSOC);
        // Fetch related books
        if ($book) {
            $booksRelated = $conn->prepare("SELECT * FROM books WHERE category = ? AND id != ? LIMIT 4"); // Changed from call_no to id
            $booksRelated->execute([$book['category'], $id]);
            $relatedBooks = $booksRelated->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = 'Failed to fetch book details: ' . $e->getMessage();
        $_SESSION['message_type'] = 'error';
    }
}

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['books_image'])) {
    $targetDir = "../uploaded_file/";
    $fileName = basename($_FILES["books_image"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
    // Allowed file types
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
    // Validate file type and upload
    if (in_array($fileType, $allowedTypes)) {
        if (move_uploaded_file($_FILES["books_image"]["tmp_name"], $targetFilePath)) {
            try {
                // Update image path in the database
                $id = $_POST['id']; // Changed from call_no to id
                $update = $conn->prepare("UPDATE books SET books_image = ? WHERE id = ?"); // Changed from call_no to id
                $update->execute([$targetFilePath, $id]);
                echo json_encode(['success' => true, 'message' => "Book image updated successfully!", 'fileName' => $fileName]);
                exit();
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => "Failed to update book image: " . $e->getMessage()]);
                exit();
            }
        } else {
            echo json_encode(['success' => false, 'message' => "Error uploading the file."]);
            exit();
        }
    } else {
        echo json_encode(['success' => false, 'message' => "Only JPG, JPEG, PNG, and GIF files are allowed."]);
        exit();
    }
}

?>