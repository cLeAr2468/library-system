<?php
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


// Handle reservation cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelReservation'])) {
    $call_no = $_POST['cancelReservation'];
    try {
        // Start a transaction
        $conn->beginTransaction();
        // Get the current date and time for the cancel_date
        $cancel_date = date('Y-m-d H:i:s');
        // Update the status of the reserved book to 'canceled' and set the cancel_date
        $stmt = $conn->prepare("UPDATE reserve_books SET status = 'canceled', cancel_date = ? WHERE call_no = ? AND user_id = ? AND status = 'reserved'");
        $stmt->execute([$cancel_date, $call_no, $user_id]);

        // Check if any row was updated
        if ($stmt->rowCount() > 0) {
            // Increment the copies in the books table
            $updateCopiesStmt = $conn->prepare("UPDATE books SET copies = copies + 1 WHERE call_no = ?");
            $updateCopiesStmt->execute([$call_no]);

            // Check if the book status needs to be updated to 'available'
            $bookStatusStmt = $conn->prepare("SELECT copies, status FROM books WHERE call_no = ?");
            $bookStatusStmt->execute([$call_no]);
            $book = $bookStatusStmt->fetch(PDO::FETCH_ASSOC);

            // If the book has copies available, update its status to 'available'
            if ($book && $book['copies'] > 0 && $book['status'] !== 'available') {
                $updateStatusStmt = $conn->prepare("UPDATE books SET status = 'available' WHERE call_no = ?");
                $updateStatusStmt->execute([$call_no]);
            }
            // Commit the transaction
            $conn->commit();
            // Successful cancellation
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Reservation canceled successfully.']);
        } else {
            // No matching reservation found
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'No reservation found to cancel.']);
        }
    } catch (PDOException $e) {
        // Rollback the transaction in case of error
        $conn->rollBack();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error canceling reservation: ' . $e->getMessage()]);
    }
    exit();
}

// Fetch reserved books for the student
$reservedBooksQuery = $conn->prepare("
    SELECT rb.*, b.title, b.books_image, b.author, b.publisher, b.copyright, b.ISBN 
    FROM reserve_books rb 
    JOIN books b ON rb.call_no = b.call_no 
    WHERE rb.user_id = ? AND rb.status = 'reserved'  -- Ensure you're checking for the correct reserved status
");
$reservedBooksQuery->execute([$user_id]);
$reservedBooks = $reservedBooksQuery->fetchAll(PDO::FETCH_ASSOC);

// Get total reserved books count
$totalReservedBooks = count($reservedBooks);

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
                    <h2 class="card-title">My Reserved Books</h2>
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
                            <th>Reserved Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reservedBooks)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No books found</td>
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
                                    <td><?php echo htmlspecialchars($book['status']); ?></td> <!-- Status from reserve_books -->
                                    <td><?php echo htmlspecialchars($book['copies']); ?></td> <!-- Copies from reserve_books -->
                                    <td><?php echo htmlspecialchars($book['reserved_date']); ?></td>
                                    <td>
                                        <form class="cancel-form" method="POST" action="">
                                            <input type="hidden" name="cancelReservation" value="<?php echo htmlspecialchars($book['call_no']); ?>">
                                            <button type="button" class="btn btn-danger btn-sm cancel-btn">Cancel</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <footer class="footer">
        <div class="container text-center">
            <span class="text-muted">© 2024 NwSSU Library. All rights reserved.</span>
        </div>
    </footer>
    <script>

    document.querySelectorAll('.cancel-btn').forEach(button => {
        button.addEventListener('click', function() {
            const form = this.closest('.cancel-form');
            const callNo = form.querySelector('input[name="cancelReservation"]').value;

            Swal.fire({
                title: 'Are you sure?',
                text: "Do you want to cancel this reservation for the book with Call No: " + callNo + "?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, cancel it!',
                cancelButtonText: 'No, keep it'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Send AJAX request to cancel reservation
                    fetch('', {
                        method: 'POST',
                        body: new URLSearchParams(new FormData(form))
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Reservation Canceled',
                                text: data.message || 'Your reservation has been canceled successfully!',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                // Optionally, you can refresh the table or the entire page
                                location.reload(); // Reload the page to reflect changes
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Cancellation Failed',
                                text: data.message || 'An error occurred while canceling your reservation. Please try again.',
                                confirmButtonText: 'OK'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Network Error',
                            text: 'Unable to communicate with the server. Please check your internet connection and try again.',
                            confirmButtonText: 'OK'
                        });
                    });
                }
            });
        });
    });
</script>
</body>
</html>