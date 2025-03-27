<?php
/// Database connection setup
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin_panel/index.php');
    exit();
}
include '../component-library/connect.php';
// Database connection
try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $user_name, $user_password);
    // Set PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
} catch (PDOException $e) {
    die('Connection failed: ' . $e->getMessage());
}

function updateOverdueBooks($conn) {
    $currentDate = date('Y-m-d');
    // Update the reserve_books table for overdue books
    $updateReserveQuery = $conn->prepare("
        UPDATE reserve_books 
        SET fine = DATEDIFF(:currentDate, return_sched) * 3,
            status = CASE 
                WHEN return_sched < :currentDate THEN 'overdue' 
                ELSE status 
            END
        WHERE return_sched < :currentDate AND status = 'borrowed'
    ");
    $updateReserveQuery->execute([':currentDate' => $currentDate]);
}
// Call the function to update overdue books
updateOverdueBooks($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'] ?? null;
    $bookInput = $_POST['book_input'] ?? null; // Changed to book_input to accept both title and ISBN
    $returnDate = $_POST['return_sched'] ?? null;
    $actionType = $_POST['action_type'] ?? null;

    if (isset($_POST['quick_checkout'])) {
        // Quick Check Out logic
        $response = ['success' => false, 'message' => ''];
        if ($userId && $bookInput && $returnDate) {
            try {
                // Check if user exists
                $userQuery = $conn->prepare("SELECT user_id, user_name, patron_type FROM user_info WHERE user_id = :userId");
                $userQuery->execute([':userId' => $userId]);
                $user = $userQuery->fetch(PDO::FETCH_ASSOC);
                if (!$user) {
                    $response['message'] = 'User not found.';
                } else {
                    // Check if book exists and get call_no based on input (ISBN or Title)
                    $bookQuery = $conn->prepare("SELECT call_no, books_title, ISBN, copies FROM books WHERE ISBN = :input OR books_title LIKE :input LIMIT 1");
                    $bookQuery->execute([':input' => $bookInput]);
                    $book = $bookQuery->fetch(PDO::FETCH_ASSOC);
                    if (!$book) {
                        $response['message'] = 'Book not found.';
                    } else {
                        // Check if the user already has a reservation for this book
                        $reservationQuery = $conn->prepare("SELECT * FROM reserve_books WHERE user_id = :userId AND ISBN = :isbn AND status = 'reserved'");
                        $reservationQuery->execute([':userId' => $userId, ':isbn' => $book['ISBN']]);
                        $existingReservation = $reservationQuery->fetch(PDO::FETCH_ASSOC);
                        $currentDate = date('Y-m-d H:i:s');
                        $fine = 0;

                        // If the user already has a reservation
                        if ($existingReservation) {
                            // Update the existing reservation status to 'borrowed'
                            $updateQuery = $conn->prepare("UPDATE reserve_books SET status = 'borrowed', borrowed_date = :borrowDate, return_sched = :returnSched, fine = $fine WHERE user_id = :userId AND ISBN = :isbn AND status = 'reserved'");
                            $updateQuery->execute([
                                ':borrowDate' => $currentDate,
                                ':returnSched' => $returnDate,
                                ':userId' => $userId,
                                ':isbn' => $book['ISBN']
                            ]);
                            $response['success'] = true;
                            $response['message'] = 'Checkout successful!.';
                        } else {
                            // Check if there are copies available
                            if ($book['copies'] <= 0) {
                                $response['message'] = 'Book is not available for checkout.';
                            } else {
                                // If no existing reservation, insert a new record
                                try {
                                    // Insert into reserve_books for quick check out
                                    $insertReserveQuery = $conn->prepare("INSERT INTO reserve_books (user_id, user_name, patron_type, book_title, call_no, copies, status, ISBN, borrowed_date, return_sched, fine) 
                                        VALUES (:userId, :userName, :patronType, :booksTitle, :callNo, :copies, 'borrowed', :isbn, :borrowedDate, :returnSched, :fine)");
                                    $copies = 1; // Set to the appropriate value based on your application logic
                                    $insertReserveQuery->execute([
                                        ':userId' => $user['user_id'],
                                        ':userName' => $user['user_name'],
                                        ':patronType' => $user['patron_type'],
                                        ':booksTitle' => $book['books_title'],
                                        ':callNo' => $book['call_no'],
                                        ':copies' => $copies,
                                        ':isbn' => $book['ISBN'],
                                        ':borrowedDate' => $currentDate,
                                        ':returnSched' => $returnDate,
                                        ':fine' => $fine,
                                    ]);
                                    // Decrease the copies in the books table
                                    $updateBookQuery = $conn->prepare("UPDATE books SET copies = copies - 1 WHERE ISBN = :isbn");
                                    $updateBookQuery->execute([':isbn' => $book['ISBN']]);
                                    // Check if copies are now 0 and update status if necessary
                                    $checkCopiesQuery = $conn->prepare("SELECT copies FROM books WHERE ISBN = :isbn");
                                    $checkCopiesQuery->execute([':isbn' => $book['ISBN']]);
                                    $currentCopies = $checkCopiesQuery->fetchColumn();
                                    if ($currentCopies <= 0) {
                                        $updateStatusQuery = $conn->prepare("UPDATE books SET status = 'not available' WHERE ISBN = :isbn");
                                        $updateStatusQuery->execute([':isbn' => $book['ISBN']]);
                                    }
                                    $response['success'] = true;
                                    $response['message'] = 'Quick Check Out successful!';
                                } catch (PDOException $e) {
                                    $response['message'] = 'Failed to process the quick check out: ' . $e->getMessage();
                                }
                            }
                        }
                    }
                }
            } catch (PDOException $e) {
                $response['message'] = 'Database error: ' . $e->getMessage();
            }
        } else {
            $response['message'] = 'Missing required parameters.';
        }
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Fetch user reservations with status 'reserved' for display
$query = "SELECT user_id, user_name, patron_type, book_title, ISBN, copies, status, return_sched 
          FROM reserve_books 
          WHERE status = 'reserved' 
          GROUP BY user_id, user_name, patron_type, book_title, ISBN, copies, status, return_sched";
$stmt = $conn->prepare($query);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include sidebar and navigation
include '../admin_panel/side_nav.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../admin_style/design.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../style/styleshitt.css">
    <style>
        .custom-btn1 {
            width: 200px;
            height: 40px;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #177245;
            color: white;
        }
        .custom-btn1:hover {
            background-color: darkgreen;
            color: white;
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
                    <i class="bi bi-search position-absolute" style="left: 15px; top: 50%; transform: translateY(-50%);"></i>
                    <input type="text" id="searchInput" placeholder="Search ID or Name" class="form-control control rounded-pill ps-5" onkeyup="searchUsers()">
                </div>
            </div>
            <div class="d-flex mb-3">
                <button class="btn me-2 custom-btn1" onclick="showCirculateModal()">Quick Check Out</button>
            </div>
            <table class="table table-striped">
                <thead>
                    <tr class="table-primary">
                        <th>User ID</th>
                        <th>User Name</th>
                        <th>Books Title</th>
                        <th>Patron Type</th>
                        <th>Status</th>
                        <th>Copy</th>
                        <th>Return Schedule</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="userTableBody">
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['user_id']); ?></td>
                            <td><?php echo htmlspecialchars($student['user_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['book_title']); ?></td>
                            <td><?php echo htmlspecialchars($student['patron_type']); ?></td>
                            <td><?php echo htmlspecialchars($student['status']); ?></td>
                            <td><?php echo htmlspecialchars($student['copies']); ?></td>
                            <td><?php echo htmlspecialchars($student['return_sched']); ?></td>
                            <td>
                                <button class="btn btn-success btn-sm" onclick="borrow('<?php echo htmlspecialchars($student['user_id']); ?>', '<?php echo htmlspecialchars($student['book_title']); ?>')">Check Out</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <footer class="footer">
        <div class="container text-center">
            <span class="text-muted">© 2024 NwSSU Library Sj Campus <i class="fas fa-comment-alt-plus"></i>. All rights reserved.</span>
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
                        <label for="book_input" class="form-label">Book Title / ISBN</label>
                        <input type="text" class="form-control" id="book_input" name="book_input" required>
                    </div>
                    <div class="mb-3">
                        <label for="return_sched" class="form-label">Return Schedule</label>
                        <input type="date" class="form-control" id="return_sched" name="return_sched" required>
                    </div>
                    <input type="hidden" name="action_type" value="borrow">
                    <input type="hidden" name="quick_checkout" value="1"> <!-- New hidden field for quick checkout -->
                    <button type="submit" class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
// Function to reset the Circulate Item modal fields
function resetCirculateModal() {
    document.getElementById('user_id').value = '';  // Clear user ID
    document.getElementById('book_input').value = ''; // Clear book input
    document.getElementById('return_sched').value = ''; // Clear return schedule
}
// JavaScript for Form Submission
document.getElementById('circulateForm').addEventListener('submit', function(e) {
    e.preventDefault(); // Prevent the default form submission behavior
    const formData = new FormData(this);
    fetch('circulation.php', { // Change to your PHP file
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: data.message
            }).then(() => {
                // Reset the form after successful submission
                resetCirculateModal(); // Reset modal fields
                const circulateModal = bootstrap.Modal.getInstance(document.getElementById('circulateModal'));
                circulateModal.hide(); // Hide the modal after submission
                location.reload(); // Refresh the page
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message
            });
        }
    })
    .catch(() => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Something went wrong. Please try again.'
        });
    });
});
// Function to show the modal and reset its fields
function showCirculateModal() {
    resetCirculateModal(); // Reset modal fields
    const circulateModal = new bootstrap.Modal(document.getElementById('circulateModal'));
    circulateModal.show();
}
// Function to pre-fill the borrow modal
function borrow(userId, isbn) {
    document.getElementById('user_id').value = userId;  // Pre-fill user ID
    document.getElementById('book_input').value = isbn; // Pre-fill ISBN
    document.getElementById('return_sched').value = ''; // Clear return schedule
    const circulateModal = new bootstrap.Modal(document.getElementById('circulateModal'));
    circulateModal.show();
}

  // Function to search users
  function searchUsers() {
        const input = document.getElementById('searchInput').value.toLowerCase();
        const rows = document.querySelectorAll('#userTableBody tr'); // Select all rows in the user table
        rows.forEach(row => {
        const userId = row.cells[0].textContent.toLowerCase(); // User ID
        const userName = row.cells[1].textContent.toLowerCase(); // User Name
        // Show row if input matches User ID or User Name
        if (userId.includes(input) || userName.includes(input)) {
        row.style.display = ''; // Show row
        } else {
        row.style.display = 'none'; // Hide row
        }
    });
}

</script>
</body>
</html>