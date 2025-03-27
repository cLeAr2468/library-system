<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin_panel/index.php'); // Adjust the path to your login page
    exit();
}
include '../component-library/connect.php';

// Fetch books data from the database
$query = $conn->query("SELECT * FROM books");
$books = $query->fetchAll(PDO::FETCH_ASSOC);

function insertOrUpdateBook($data, $bookId = null) {
    global $conn;
    try {
        $imagePath = null; // Default to null
        // Check if the image is uploaded and no error occurred
        if (isset($_FILES['book_image']) && $_FILES['book_image']['error'] === UPLOAD_ERR_OK) {
            $originalFileName = basename($_FILES['book_image']['name']);
            $imagePath = '../uploaded_file/' . uniqid() . '_' . $originalFileName; // Unique file name
            if (!file_exists(dirname($imagePath))) {
                mkdir(dirname($imagePath), 0777, true);
            }
            if (!move_uploaded_file($_FILES['book_image']['tmp_name'], $imagePath)) {
                throw new Exception('Failed to upload the image.');
            }
        }
        $currentDate = date('Y-m-d'); // Format: YYYY-MM-DD
        $dateAcquired = isset($data['date_acquired']) && !empty($data['date_acquired']) ? $data['date_acquired'] : null;
        if ($bookId) {
            // Update existing book
            $stmt = $conn->prepare("UPDATE books SET 
                material_type = ?, 
                sub_type = ?, 
                category = ?, 
                title = ?, 
                author = ?, 
                publisher = ?, 
                status = ?, 
                subject = ?, 
                content = ?, 
                summary = ?, 
                issn = ?, 
                ISBN = ?, 
                copyright = ?, 
                page_number = ?, 
                edition = ?, 
                copies = ?, 
                date_acquired = ?, 
                catalog_date = ?, 
                books_image = COALESCE(?, books_image) 
                WHERE id = ?");  // Change 'call_no' to 'id' if 'id' is the primary key
            $data['books_image'] = $imagePath ? $imagePath : null; // If no new image, set to null for COALESCE
            $dataToBind = [
                $data['material_type'],
                $data['sub_type'],
                $data['category'],
                $data['title'],
                $data['author'],
                $data['publisher'],
                $data['status'],
                $data['subject'],
                $data['content'],
                $data['summary'],
                $data['issn'], 
                $data['ISBN'],
                $data['copyright'], 
                $data['page_number'],
                $data['edition'],
                $data['copies'],
                $dateAcquired, 
                $currentDate, 
                $data['books_image'], 
                $bookId  // Use the book ID for the update
            ];
        } else {
            // Insert new book
            $stmt = $conn->prepare("INSERT INTO books (
                material_type, sub_type, category, title, author, publisher, status, subject, content, summary, issn, call_no, ISBN, copyright, page_number, edition, copies, date_acquired, books_image, catalog_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $data['books_image'] = $imagePath; 
            $dataToBind = [
                $data['material_type'],
                $data['sub_type'],
                $data['category'],
                $data['title'],
                $data['author'],
                $data['publisher'],
                $data['status'],
                $data['subject'],
                $data['content'],
                $data['summary'],
                $data['issn'], 
                $data['call_no'],
                $data['ISBN'],
                $data['copyright'], 
                $data['page_number'],
                $data['edition'],
                $data['copies'],
                $dateAcquired, 
                $data['books_image'],
                $currentDate
            ];
        }
        $stmt->execute($dataToBind);
        return ['status' => 'success', 'message' => $bookId ? "Book updated successfully!" : "Book added successfully!"];
    } catch (Exception $e) {
        error_log("Error inserting/updating book: " . $e->getMessage());
        return ['status' => 'error', 'message' => "Failed to " . ($bookId ? "update" : "add") . " book: " . $e->getMessage()];
    }
}

// Insert or update book if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bookId = isset($_POST['book_id']) ? $_POST['book_id'] : null; // Check if book ID is present
    $response = insertOrUpdateBook($_POST, $bookId);
    echo json_encode($response); // Return JSON response
    exit();
}

// Determine the current page number and set the number of books per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$books_per_page = 10;
$offset = ($page - 1) * $books_per_page;

// Handle search query and category
$search_query = $_GET['query'] ?? '';
$search_category = $_GET['category'] ?? 'all';
$search_query_param = $search_query ? $search_query . '%' : '%'; // Add '%' after search query

// Prepare the SQL query based on the selected category
if ($search_category !== 'all') {
    $query = $conn->prepare("SELECT * FROM books WHERE $search_category LIKE :search ORDER BY title ASC LIMIT :offset, :limit");
} else {
    $query = $conn->prepare("SELECT * FROM books WHERE 
        call_no LIKE :search OR 
        title LIKE :search OR 
        author LIKE :search OR 
        copyright LIKE :search OR 
        publisher LIKE :search OR 
        category LIKE :search OR 
        status LIKE :search OR 
        ISBN LIKE :search OR 
        edition LIKE :search OR 
        subject LIKE :search OR 
        content LIKE :search OR 
        summary LIKE :search 
        ORDER BY title ASC LIMIT :offset, :limit");
}
$query->bindValue(':search', $search_query_param, PDO::PARAM_STR);
$query->bindValue(':offset', $offset, PDO::PARAM_INT);
$query->bindValue(':limit', $books_per_page, PDO::PARAM_INT);
$query->execute();
$books = $query->fetchAll(PDO::FETCH_ASSOC);

// Fetch total number of books for pagination calculation
if ($search_category !== 'all') {
    $total_books_query = $conn->prepare("SELECT COUNT(*) FROM books WHERE $search_category LIKE :search");
} else {
    $total_books_query = $conn->prepare("SELECT COUNT(*) FROM books WHERE 
        call_no LIKE :search OR 
        title LIKE :search OR 
        author LIKE :search OR 
        copyright LIKE :search OR 
        publisher LIKE :search OR 
        category LIKE :search OR 
        status LIKE :search OR 
        ISBN LIKE :search OR 
        edition LIKE :search OR 
        subject LIKE :search OR 
        content LIKE :search OR 
        summary LIKE :search");
}
$total_books_query->bindValue(':search', $search_query_param, PDO::PARAM_STR);
$total_books_query->execute();
$total_books = $total_books_query->fetchColumn();
$total_pages = ceil($total_books / $books_per_page);

include '../admin_panel/side_nav.php';
?>