<?php

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin_panel/index.php'); // Adjust the path to your login page
    exit();
}
// Database connection
include '../component-library/connect.php';
// Database connection
try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $user_name, $user_password);
    // Set PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
} catch (PDOException $e) {
    die('Connection failed: ' . $e->getMessage());
}

// Query to get categories and their book counts, sorted alphabetically
$query = "SELECT category, COUNT(*) AS item_count FROM books GROUP BY category ORDER BY category ASC";
$categ = $conn->prepare($query);
$categ->execute();
$categories = $categ->fetchAll(PDO::FETCH_ASSOC);

// Check if the logout button is clicked
if (isset($_POST['logout'])) {
    // Unset student-specific session variables
    unset($_SESSION['user_id']);
    unset($_SESSION['user_name']);
    // Redirect to student login page
    header('Location: student_login.php');
    exit();
}

include '../admin_panel/side_nav.php';
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
    <link rel="stylesheet" href="../style/styleshitt.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- Include SweetAlert -->
</head>
<body>
<div class="main p-3">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-10 fw-bold fs-3">
                    <p><span>Dashboard</span></p>
                </div>
            </div>
        </div>
    <div class="container my-4">
        <div class="border-box">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Categories</h1>
                <div class="search-input-wrapper">
                    <i class="bi bi-search position-absolute" style="left: 15px; top: 50%; transform: translateY(-50%);"></i>
                    <input type="text" id="searchInput" placeholder="Search Category" class="form-control control rounded-pill ps-5" onkeyup="searchCategories()">
                </div>
            </div>
            <table class="table table-striped">
                <thead>
                    <tr class="table-primary">
                        <th>Category</th>
                        <th>Item(s)</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="categoriesTableBody">
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><a href="categbooks.php?category=<?php echo urlencode($category['category']); ?>" class="book-title"><?php echo htmlspecialchars($category['category']); ?></a></td>
                            <td><?php echo htmlspecialchars($category['item_count']); ?></td>
                            <td><a href="categbooks.php?category=<?php echo urlencode($category['category']); ?>">Browse</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    function searchCategories() {
        const input = document.getElementById('searchInput').value.toLowerCase(); // Get the search input
        const rows = document.querySelectorAll('#categoriesTableBody tr'); // Select all rows in the category table
        rows.forEach(row => {
            const categoryName = row.cells[0].textContent.toLowerCase(); // Get the category name from the first cell
            // Show row if input matches category name
            row.style.display = categoryName.includes(input) ? '' : 'none'; // Show or hide row
        });
    }
</script>
</body>
</html>