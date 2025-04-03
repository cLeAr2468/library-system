<?php
session_start(); // Start the session
include '../component-library/connect.php';
include '../student/side_navbars.php';
// Database connection
try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $user_name, $user_password);
    // Set PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}
// Fetch student profile data
$stud = $conn->prepare("SELECT * FROM user_info WHERE user_id = ?");
$stud->execute([$user_id]);
$student = $stud->fetch(PDO::FETCH_ASSOC);
$profile_image = $student['images'] ?? '../images/prof.jpg'; // Fallback if no image
// Handle profile update via AJAX

$reservedBooksQuery = $conn->prepare("
    SELECT rb.*, b.title, b.books_image, b.author, b.publisher, b.copyright, b.ISBN, rb.status 
    FROM reserve_books rb 
    JOIN books b ON rb.call_no = b.call_no 
    WHERE rb.user_id = ? AND rb.status = 'borrowed' 
");
$reservedBooksQuery->execute([$user_id]);
$reservedBooks = $reservedBooksQuery->fetchAll(PDO::FETCH_ASSOC);

// Get total reserved books count
$totalReservedBooks = count($reservedBooks);
// Check if the logout button is clicked

// Determine the current date for overdue check
$currentDate = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reserved Books</title>
    <link rel="stylesheet" href="../admin_style/design.css">
    <link rel="stylesheet" href="../style/home.css">
</head>
<body>
<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="card-title">My Borrowed Books</h2>
            </div>
            <div class="book-title selected-category">
                Student ID: <strong><?php echo htmlspecialchars($user_id); ?></strong>
            </div>
            <table class="table table-bordered mt-3">
                <thead class="thead-light">
                    <tr>
                        <th></th> <!-- Empty header for book image column -->
                        <th>Title</th>
                        <th>Authors/Editors</th>
                        <th>Publisher</th>
                        <th>Status</th>
                        <th>Copies</th>
                        <th>Borrowed Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reservedBooks)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No books found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reservedBooks as $book): ?>
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
                                            <a href="studbook_detail.php?call_no=<?php echo urlencode($book['call_no']); ?>" class="book-title">
                                                <?php echo htmlspecialchars($book['title']); ?>
                                            </a><br>
                                            <small>Publish Date: <?php echo htmlspecialchars($book['copyright']); ?></small><br>
                                            <small>ISBN: <?php echo htmlspecialchars($book['ISBN']); ?></small><br>
                                            <small>Call No: <?php echo htmlspecialchars($book['call_no']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                <td><?php echo htmlspecialchars($book['publisher']); ?></td>
                                <td><?php echo htmlspecialchars($book['status']); ?></td>
                                <td><?php echo htmlspecialchars($book['copies']); ?></td> <!-- Copies from reserve_books -->
                                <td><?php echo htmlspecialchars($book['borrowed_date']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../student/footer.php'; ?>
</body>
</html>