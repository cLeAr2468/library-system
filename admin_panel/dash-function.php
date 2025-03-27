<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin_panel/index.php'); // Adjust the path to your login page
    exit();
}
include '../component-library/connect.php'; // Include the connection file


try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $user_name, $user_password);
    // Set PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
    // Fetch announcements
    $home = $conn->query("SELECT * FROM announcement ORDER BY id DESC");
    $announcements = $home->fetchAll(PDO::FETCH_ASSOC);
    // Fetch books for carousel
    $sql = "SELECT title, books_image, call_no FROM books";
    $home = $conn->query($sql);
    $books = $home->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit();
}
// Function to get the count of students by status
function getStudentCountByStatus($conn, $status)
{
    try {
        $studinfo = $conn->prepare("SELECT COUNT(*) FROM user_info WHERE status = :status");
        $studinfo->bindParam(':status', $status, PDO::PARAM_STR);
        $studinfo->execute();
        return $studinfo->fetchColumn();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        return 0;
    }
}

// Function to get the total number of books
function getTotalBooksCount($conn)
{
    try {
        $totalbooks = $conn->query("SELECT COUNT(*) FROM books");
        return $totalbooks->fetchColumn();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        return 0;
    }
}

// Function to get the total number of copies of all books
function getTotalCopiesCount($conn)
{
    try {
        $totalCopies = $conn->query("SELECT SUM(copies) FROM books");
        return $totalCopies->fetchColumn();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        return 0;
    }
}

// Function to get the total number of reserved books
function getTotalReservedBooksCount($conn)
{
    try {
        $reservedBooks = $conn->query("SELECT COUNT(*) FROM reserve_books WHERE status = 'reserved'");
        return $reservedBooks->fetchColumn();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        return 0;
    }
}

// Function to get the total number of borrowed books
function getTotalBorrowedBooksCount($conn)
{
    try {
        $borrowedBooks = $conn->query("SELECT COUNT(*) FROM reserve_books WHERE status IN ('borrowed', 'overdue')");
        return $borrowedBooks->fetchColumn();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        return 0;
    }
}

// Function to get the total number of users with fines
function getTotalUsersWithFinesCount($conn)
{
    try {
        $usersWithFines = $conn->query("SELECT COUNT(DISTINCT user_id) FROM reserve_books WHERE fine > 0");
        return $usersWithFines->fetchColumn();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        return 0;
    }
}

// Retrieve the counts
$approvedCount = getStudentCountByStatus($conn, 'approved');
$pendingCount = getStudentCountByStatus($conn, 'pending');
$totalBooksCount = getTotalBooksCount($conn);
$totalCopiesCount = getTotalCopiesCount($conn); // Get total copies count
$totalReservedBooksCount = getTotalReservedBooksCount($conn);
$totalBorrowedBooksCount = getTotalBorrowedBooksCount($conn);
$totalUsersWithFinesCount = getTotalUsersWithFinesCount($conn);

?>