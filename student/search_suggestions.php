<?php
session_start(); // Start the session
include '../component-library/connect.php';

header('Content-Type: application/json');

try {
    $query = $_GET['query'] ?? '';
    $category = $_GET['category'] ?? 'all';
    $suggestions = [];

    if (strlen($query) >= 2) {
        $searchFields = [];
        $params = [];

        if ($category === 'all') {
            $searchFields = [
                'title', 'author', 'ISBN', 'publisher', 
                'category', 'material_type', 'sub_type'
            ];
        } else {
            $searchFields = [$category];
        }

        $sqlParts = [];
        foreach ($searchFields as $field) {
            $sqlParts[] = "$field LIKE :query_$field";
            $params["query_$field"] = '%' . $query . '%';
        }

        $sql = "SELECT DISTINCT 
                    title, 
                    author, 
                    ISBN, 
                    publisher,
                    category,
                    material_type,
                    sub_type
                FROM books 
                WHERE " . implode(' OR ', $sqlParts) . "
                LIMIT 10";

        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            foreach ($searchFields as $field) {
                if (stripos($row[$field], $query) !== false) {
                    $suggestions[] = $row[$field];
                }
            }
        }

        // Remove duplicates and limit results
        $suggestions = array_unique($suggestions);
        $suggestions = array_slice($suggestions, 0, 10);
    }

    echo json_encode($suggestions);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
} 