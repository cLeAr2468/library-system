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
    $actionType = $_POST['action_type'] ?? null;

    if ($actionType === 'checkin') {
        // Check-in logic
        $currentDate = date('Y-m-d H:i:s');
        $response = ['success' => false, 'message' => ''];
        if ($userId && $bookInput) {
            try {
                // Check if there's a borrowed or overdue record for this user and book
                $statusQuery = $conn->prepare("
                    SELECT status FROM reserve_books   
                    WHERE user_id = :userId AND ISBN = :isbn AND status IN ('borrowed', 'overdue')
                ");
                $statusQuery->execute([':userId' => $userId, ':isbn' => $bookInput]);
                $bookStatus = $statusQuery->fetchColumn();
                // Proceed with check-in logic based on the current book status
                if ($bookStatus === 'borrowed') {
                    // Update to set return_date and change status to 'returned'
                    $updateQuery = $conn->prepare("
                        UPDATE reserve_books   
                        SET return_date = :returnDate, status = 'returned'   
                        WHERE user_id = :userId AND ISBN = :isbn AND status = 'borrowed'
                    ");
                    $updateQuery->execute([':returnDate' => $currentDate, ':userId' => $userId, ':isbn' => $bookInput]);
                    // Increase copies in books table
                    $increaseCopiesQuery = $conn->prepare("UPDATE books SET copies = copies + 1 WHERE ISBN = :isbn");
                    $increaseCopiesQuery->execute([':isbn' => $bookInput]);
                    $response['success'] = true;
                    $response['message'] = 'Book checked in successfully!';
                } elseif ($bookStatus === 'overdue') {
                    // Fetch fine details if overdue
                    $fineQuery = $conn->prepare("
                        SELECT book_title, fine, user_name, patron_type 
                        FROM reserve_books   
                        WHERE user_id = :userId AND ISBN = :isbn AND status = 'overdue'
                    ");
                    $fineQuery->execute([':userId' => $userId, ':isbn' => $bookInput]);
                    $fineDetails = $fineQuery->fetch(PDO::FETCH_ASSOC);
                    if ($fineDetails) {
                        $response['success'] = true;
                        $response['fineDetails'] = $fineDetails;
                    } else {
                        $response['message'] = 'No fine details found.';
                    }
                } else {
                    $response['message'] = 'Book status is not valid for check-in or already returned.';
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

// Fetch user reservations for display
$query = "SELECT user_id, user_name, patron_type, book_title, ISBN, copies, status, return_sched 
          FROM reserve_books 
          WHERE status IN ('borrowed', 'overdue') 
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
                                <?php if ($student['status'] === 'borrowed' || $student['status'] === 'overdue'): ?>
                                    <button class="btn btn-warning btn-sm" onclick="checkIn('<?php echo htmlspecialchars($student['user_id']); ?>', '<?php echo htmlspecialchars($student['ISBN']); ?>')">Check In</button>
                                <?php endif; ?>
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
<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">Payment for Overdue Book</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">User ID: </label>
                    <label id="modalUserId" class="form-label"></label>
                </div>
                <div class="mb-3">
                    <label class="form-label">User Name: </label>
                    <label id="modalUserName" class="form-label"></label>
                </div>
                <div class="mb-3">
                    <label class="form-label">Patron Type: </label>
                    <label id="modalPatronType" class="form-label"></label>
                </div>
                <div class="mb-3">
                    <label class="form-label">Book Title: </label>
                    <label id="modalBookTitle" class="form-label"></label>
                </div>
                <div class="mb-3">
                    <label class="form-label">ISBN: </label>
                    <label id="modalISBN" class="form-label"></label>
                </div>
                <div class="mb-3">
                    <label class="form-label">Fine Amount</label>
                    <input type="number" class="form-control" id="modalFine" />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="payButton">Pay</button>
            </div>
        </div>
    </div>
</div>
<script>
// Function to handle check-in
function checkIn(userId, isbn) {
    Swal.fire({
        title: 'Are you sure?',
        text: "Do you want to check in this book?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, check in!',
        cancelButtonText: 'No, cancel!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Proceed with the check-in process
            fetch('circulation.php', { // Your PHP endpoint
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    user_id: userId,
                    book_input: isbn, // Changed to book_input to match the updated PHP
                    action_type: 'checkin'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.fineDetails) {
                        // Populate modal with fine details
                        document.getElementById('modalUserId').innerText = userId;
                        document.getElementById('modalUserName').innerText = data.fineDetails.user_name;
                        document.getElementById('modalPatronType').innerText = data.fineDetails.patron_type;
                        document.getElementById('modalBookTitle').innerText = data.fineDetails.book_title;
                        document.getElementById('modalISBN').innerText = isbn;
                        document.getElementById('modalFine').value = data.fineDetails.fine;
                        // Show the payment modal
                        const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
                        paymentModal.show();
                        // Handle payment button click
                        document.getElementById('payButton').onclick = function() {
                            processPayment(userId, isbn, paymentModal);
                        };
                    } else {
                        Swal.fire('Success', data.message, 'success').then(() => {
                            location.reload(); // Refresh the page
                        });
                    }
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(() => {
                Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
            });
        }
    });
}

// Payment processing function
function processPayment(userId, isbn, paymentModal) {
    const fineAmount = document.getElementById('modalFine').value;
    fetch('circulation.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
            user_id: userId,
            ISBN: isbn,
            action_type: 'pay',
            fine: fineAmount
        })
    })
    .then(response => response.json())
    .then(paymentData => {
        if (paymentData.success) {
            Swal.fire('Success', 'Payment successful! Book returned.', 'success').then(() => {
                paymentModal.hide();
                location.reload(); // Refresh the page
            });
        } else {
            Swal.fire('Error', paymentData.message, 'error');
        }
    })
    .catch(() => {
        Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
    });
}

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