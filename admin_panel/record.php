<?php
// Database connection setup
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin_panel/index.php'); // Adjust the path to your login page
    exit();
}
$dsn = 'mysql:host=localhost;dbname=library-system';
$username = 'root';
$password = '';
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die('Connection failed: ' . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'] ?? null;
    $callNoOrIsbn = $_POST['call_no'] ?? null;
    $returnDate = $_POST['return_sched'] ?? null;

    if ($userId && $callNoOrIsbn && $returnDate) {
        try {
            // Initialize variables
            $userIdToInsert = null;
            $userName = null;
            $patronType = null;

            // Check for users
            $userQuery = $pdo->prepare("SELECT user_id, user_name, patron_type FROM user_info WHERE user_id = :userId");
            $userQuery->execute([':userId' => $userId]);
            $user = $userQuery->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $userIdToInsert = $user['user_id'];
                $userName = $user['user_name'];
                $patronType = $user['patron_type'];
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'ID No is not found in records.']);
                exit;
            }

            // Validate Call Number or ISBN
            $bookQuery = $pdo->prepare("SELECT books_title FROM books WHERE call_no = :callNoOrIsbn OR ISBN = :callNoOrIsbn");
            $bookQuery->execute([':callNoOrIsbn' => $callNoOrIsbn]);
            $book = $bookQuery->fetch(PDO::FETCH_ASSOC);

            if (!$book) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Book not found with the provided Call No or ISBN.']);
                exit;
            }

            // Insert borrowing record into the database
            $insertQuery = $pdo->prepare("INSERT INTO borrow_books (user_id, user_name, patron_type, books_title, fine, return_sched, borrowed_date, call_no_isbn, books_status) VALUES (:userId, :userName, :patronType, :booksTitle, 0, :returnSched, :borrowedDate, :callNoIsbn, 'borrowed')");

            // Set the borrowed date to the current date and time
            $borrowedDate = (new DateTime())->format('Y-m-d H:i:s');
            $insertQuery->execute([
                ':userId' => $userIdToInsert,
                ':userName' => $userName,
                ':patronType' => $patronType,
                ':booksTitle' => $book['books_title'],
                ':returnSched' => $returnDate,
                ':borrowedDate' => $borrowedDate,
                ':callNoIsbn' => $callNoOrIsbn,
            ]);

            // Now check for overdue books and update fines
            $currentDateTime = new DateTime();
            $finePerDay = 3; // Define the fine amount per day

            // Check for overdue books
            $overdueBooksQuery = $pdo->prepare("
                SELECT user_id, return_sched, fine
                FROM borrow_books
                WHERE return_sched < :currentDateTime AND books_status = 'borrowed'
            ");
            $overdueBooksQuery->execute([':currentDateTime' => $currentDateTime->format('Y-m-d H:i:s')]);
            $overdueBooks = $overdueBooksQuery->fetchAll(PDO::FETCH_ASSOC);

            // Update fines for overdue books
            foreach ($overdueBooks as $overdueBook) {
                $returnSched = new DateTime($overdueBook['return_sched']);
                $interval = $currentDateTime->diff($returnSched);
                $daysLate = $interval->days; // Total days late
                $fineAmount = $daysLate * $finePerDay; // Calculate fine

                // Update fine in the database
                if ($fineAmount > 0) { // Only update if there's a fine
                    $updateFineQuery = $pdo->prepare("
                        UPDATE borrow_books 
                        SET fine = fine + :additionalFine 
                        WHERE user_id = :userId AND return_sched = :returnSched AND books_status = 'borrowed'
                    ");
                    $updateFineQuery->execute([
                        ':additionalFine' => $fineAmount,
                        ':userId' => $overdueBook['user_id'],
                        ':returnSched' => $overdueBook['return_sched']
                    ]);
                }
            }

            // Return success response
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Borrowing successful!']);
        } catch (PDOException $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    }
}

// Fetch existing user records to display in the table
$query = "SELECT user_id, user_name, patron_type, COUNT(*) AS item_count, SUM(fine) AS total_fine FROM reserve_books GROUP BY user_id, user_name, patron_type";
$stmt = $pdo->prepare($query);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include sidebar and navigation
include '../admin_panel/sidebar_nav.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../admin_style/design.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../admin_style/style.css">
    <style>
        .custom-btn {
            width: 150px;
            height: 40px;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
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
                <h1 class="h3">Users</h1>
                <div class="search-input-wrapper">
                    <input type="text" placeholder="Search..." class="form-control rounded-pill ps-5">
                    <img src="https://placehold.co/20" alt="search-icon" />
                </div>
            </div>
            <div class="d-flex mb-3">
                <button class="btn btn-primary me-2 custom-btn" data-bs-toggle="modal" data-bs-target="#circulateModal">Circulate Item</button>
                <button class="btn btn-secondary custom-btn" data-bs-toggle="modal" data-bs-target="#returnModal">Quick Return</button>
            </div>
            <table class="table table-striped">
                <thead>
                    <tr class="table-primary">
                        <th>User ID</th>
                        <th>User Name</th>
                        <th>Patron Type</th>
                        <th>Books Borrowed</th>
                        <th>Total Fine</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['user_id']); ?></td>
                            <td><?php echo htmlspecialchars($student['user_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['patron_type']); ?></td>
                            <td><?php echo htmlspecialchars($student['item_count']); ?></td>
                            <td><?php echo htmlspecialchars($student['total_fine']); ?></td>
                            <td><a href="selected_student.php?student=<?php echo urlencode($student['user_name']); ?>">Browse</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <footer class="footer">
            <div class="container text-center">
                <span class="text-muted">© 2024 NwSSU Library Sj Campus <i class="fas fa-comment-alt-plus    "></i>. All rights reserved.</span>
            </div>
        </footer>
</div>
<!-- Circulate Item Modal -->
<div class="modal fade" id="circulateModal" tabindex="-1" aria-labelledby="circulateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="circulateModalLabel">Circulate Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="circulateForm">
                    <div class="mb-3">
                        <label for="user_id" class="form-label">User ID</label>
                        <input type="text" class="form-control" id="user_id" name="user_id" required>
                    </div>
                    <div class="mb-3">
                        <label for="call_no" class="form-label">Call No / ISBN</label>
                        <input type="text" class="form-control" id="call_no" name="call_no" required>
                    </div>
                    <div class="mb-3">
                        <label for="return_sched" class="form-label">Return Schedule</label>
                        <input type="date" class="form-control" id="return_sched" name="return_sched" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- JavaScript for Form Submission -->
<script>
document.getElementById('circulateForm').addEventListener('submit', function(e) {
    e.preventDefault(); // Prevent default form submission
    const formData = new FormData(this); // Create FormData object from the form
    fetch('../admin_panel/circulation.php', { // Ensure this path is correct
        method: 'POST',
        body: formData,
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json(); // Parse JSON response
    })
    .then(data => {
        Swal.fire({
            icon: data.success ? 'success' : 'warning',
            title: data.success ? 'Borrowing Successful' : 'Borrowing Failed',
            text: data.message || (data.success ? 'Books borrowed successfully!' : 'An error occurred while borrowing books.'),
            confirmButtonText: 'OK'
        }).then(() => {
            if (data.success) {
                $('#circulateModal').modal('hide'); // Hide the modal
                location.reload(); // Refresh the page after modal is closed
            }
        });
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An unexpected error occurred. Please try again later.',
            confirmButtonText: 'OK'
        });
    });
});
</script>
</body>
</html>