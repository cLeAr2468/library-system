<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin_panel/index.php'); // Adjust the path to your login page
    exit();
}

define('BOOKS_PER_PAGE', 10); // Define the number of books per page

include '../component-library/connect.php';
// Database connection
try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $user_name, $user_password);
    // Set PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
} catch (PDOException $e) {
    die('Connection failed: ' . htmlspecialchars($e->getMessage()));
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * BOOKS_PER_PAGE;

// Get the selected category from URL parameter
$selected_category = isset($_GET['category']) ? $_GET['category'] : '';
$selected_category = htmlspecialchars($selected_category); // Validate category to prevent SQL injection

// Initialize books array
$books = [];
$total_pages = 0;

// Fetch books data based on selected category with sorting and pagination
if (!empty($selected_category)) {
    $query = $conn->prepare("SELECT * FROM books WHERE category = :category ORDER BY books_title ASC LIMIT :offset, :limit");
    $query->bindValue(':category', $selected_category, PDO::PARAM_STR);
    $query->bindValue(':offset', $offset, PDO::PARAM_INT);
    $query->bindValue(':limit', BOOKS_PER_PAGE, PDO::PARAM_INT);
    $query->execute();
    $books = $query->fetchAll(PDO::FETCH_ASSOC);

    // Fetch total number of books for pagination calculation
    $total_books_query = $conn->prepare("SELECT COUNT(*) FROM books WHERE category = :category");
    $total_books_query->bindValue(':category', $selected_category, PDO::PARAM_STR);
    $total_books_query->execute();
    $total_books = $total_books_query->fetchColumn();
    $total_pages = ceil($total_books / BOOKS_PER_PAGE);
}

// Fetch distinct categories for display
$categories_query = $conn->query("SELECT DISTINCT category FROM books");
$categories = $categories_query->fetchAll(PDO::FETCH_COLUMN);

// Check if the logout button is clicked
if (isset($_POST['logout'])) {
    unset($_SESSION['user_id']);
    unset($_SESSION['user_name']);
    header('Location: student_login.php');
    exit();
}

include '../admin_panel/sidebar_nav.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../admin_style/design.css">
    <link rel="stylesheet" href="../admin_style/style.css">
</head>
<style>
    .selected-category {
        color: #156295;
    }

    .book-image-container {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 110px;
        padding: 100px 0;
        margin-bottom: 5%;
        margin-top: 5%;
    }
</style>
<body>
<div class="main p-2">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-10 fw-bold fs-3">
                <p><span>Dashboard</span></p>
            </div>
        </div>
    </div>
    <div class="container mt-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="card-title">Search</h2>
                </div>
                <?php if ($selected_category): ?>
                    <div class="book-title selected-category d-flex justify-content-between align-items-center mb-1">
                        <span>
                            Selected Category: <strong><?php echo htmlspecialchars($selected_category); ?></strong> [ <a href="categories.php">All</a> ]
                        </span>
                        <div class="search-input-wrapper">
                            <i class="bi bi-search position-absolute" style="left: 15px; top: 50%; transform: translateY(-50%);"></i>
                            <input type="text" id="searchInput" placeholder="Search Book" class="form-control control rounded-pill ps-5" onkeyup="searchBooks()" style="width: 300px;">
                        </div>
                    </div>
                <?php endif; ?>
                <table class="table table-bordered mt-3">
                    <thead class="thead-light">
                        <tr>
                            <th></th> <!-- Empty header for book image column -->
                            <th>Title</th>
                            <th>Authors/Editors</th>
                            <th>Publisher</th>
                            <th>Status</th>
                            <th>Copies</th>
                        </tr>
                    </thead>
                    <tbody id="booksTableBody">
                        <?php if (empty($books)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No books found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($books as $book): ?>
                                <tr>
                                    <td class="text-center book-image-container">
                                        <?php if (!empty($book['books_image'])): ?>
                                            <img src="../uploaded_file/<?php echo htmlspecialchars($book['books_image']); ?>" alt="Book Cover" class="img-thumbnail" style="width: 80px; height: 110px;">
                                        <?php else: ?>
                                            <div style="width: 80px; height: 110px; background-color: rgba(232, 232, 232, 0.65); display: flex; align-items: center; justify-content: center; color: #555;">
                                                Missing Cover Photo
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="book-info">
                                            <div>
                                                <a href="books_detail.php?call_no=<?php echo urlencode($book['call_no']); ?>" class="book-title">
                                                    <?php echo htmlspecialchars($book['books_title']); ?>
                                                </a><br>
                                                <small>Publish Date: <?php echo htmlspecialchars($book['publish_date']); ?></small><br>
                                                <small>ISBN: <?php echo htmlspecialchars($book['ISBN']); ?></small><br>
                                                <small>Call No: <?php echo htmlspecialchars($book['call_no']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="book-info">
                                        <a href="selected_author.php?author=<?php echo urlencode($book['author']); ?>" class="books-link">
                                            <?php echo htmlspecialchars($book['author']); ?>
                                        </a>
                                    </td>
                                    <td class="book-info">
                                        <a href="publisher_browse.php?publisher=<?php echo urlencode($book['publisher']); ?>" class="books-link">
                                            <?php echo htmlspecialchars($book['publisher']); ?>
                                        </a>
                                    </td>
                                    <td class="text-center"><?php echo htmlspecialchars($book['status']); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($book['copies']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= ($i === $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?= $i; ?>&category=<?php echo urlencode($selected_category); ?>"><?= $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </div>   
    <footer class="footer">
        <div class="container text-center">
            <span class="text-muted">© 2024 NwSSU Library. All rights reserved.</span>
        </div>
    </footer>
</div> 
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function searchBooks() {
        const input = document.getElementById('searchInput').value.toLowerCase();
        const rows = document.querySelectorAll('#booksTableBody tr');
        rows.forEach(row => {
            const title = row.cells[1].textContent.toLowerCase();
            row.style.display = title.includes(input) ? '' : 'none';
        });
    }
</script>
</body>
</html>