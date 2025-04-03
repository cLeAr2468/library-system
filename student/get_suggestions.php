<?php
session_start(); // Start the session
include '../component-library/connect.php';

try {
    $term = $_GET['term'] ?? '';
    $category = $_GET['category'] ?? 'all';
    
    if (strlen($term) < 2) {
        echo json_encode([]);
        exit;
    }

    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $user_name, $user_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $search_term = "%$term%";
    
    if ($category !== 'all') {
        $query = $conn->prepare("SELECT DISTINCT $category FROM books WHERE $category LIKE :term LIMIT 10");
        $query->bindParam(':term', $search_term);
    } else {
        // Search across multiple columns
        $query = $conn->prepare("
            SELECT DISTINCT title FROM books WHERE title LIKE :term
            UNION
            SELECT DISTINCT author FROM books WHERE author LIKE :term
            UNION
            SELECT DISTINCT publisher FROM books WHERE publisher LIKE :term
            UNION
            SELECT DISTINCT ISBN FROM books WHERE ISBN LIKE :term
            UNION
            SELECT DISTINCT subject FROM books WHERE subject LIKE :term
            UNION
            SELECT DISTINCT material_type FROM books WHERE material_type LIKE :term
            LIMIT 10
        ");
        $query->bindParam(':term', $search_term);
    }

    $query->execute();
    $suggestions = $query->fetchAll(PDO::FETCH_COLUMN);

    header('Content-Type: application/json');
    echo json_encode($suggestions);

} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} 