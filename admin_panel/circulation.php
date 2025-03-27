<?php
    /// Database connection setup
    session_start();
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: ../admin_panel/index.php');
        exit();
    }
    include '../component-library/connect.php';
    try {
        $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $user_name, $user_password);
        // Set PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
    } catch (PDOException $e) {
        die('Connection failed: ' . $e->getMessage());
    }
    
    function updateOverdueBooks($conn)
    {
        $currentDate = date('Y-m-d');
        // Update the reserve_books table for overdue books
        $updateReserveQuery = $conn->prepare("
            UPDATE reserve_books 
            SET fine = DATEDIFF(:currentDate, return_sched) * 3,
                status = 'overdue'
            WHERE return_sched < :currentDate AND status = 'borrowed'
        ");
        // Execute the query with the current date
        $updateReserveQuery->execute([':currentDate' => $currentDate]);
        // Update the fine for books that are already overdue
        $updateExistingFinesQuery = $conn->prepare("
            UPDATE reserve_books 
            SET fine = DATEDIFF(:currentDate, return_sched) * 3
            WHERE return_sched < :currentDate AND status = 'overdue'
        ");
        // Execute the query to update fines for already overdue books
        $updateExistingFinesQuery->execute([':currentDate' => $currentDate]);
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
                    // Check if user exists and get account status
                    $userQuery = $conn->prepare("SELECT user_id, user_name, patron_type, account_status FROM user_info WHERE user_id = :userId");
                    $userQuery->execute([':userId' => $userId]);
                    $user = $userQuery->fetch(PDO::FETCH_ASSOC);
                    if (!$user) {
                        $response['message'] = 'User not found.';
                    } elseif ($user['account_status'] === 'inactive') {
                        $response['message'] = 'This account is inactive.';
                    } else {
                        // Check if book exists and get call_no based on input (ISBN or Title)
                        $bookQuery = $conn->prepare("SELECT call_no, books_title, ISBN, copies FROM books WHERE ISBN = :input OR books_title LIKE :input LIMIT 1");
                        $bookQuery->execute([':input' => $bookInput]);
                        $book = $bookQuery->fetch(PDO::FETCH_ASSOC);
                        if (!$book) {
                            $response['message'] = 'Book not found.';
                        } else {
                            // Check if the user already has a reservation for this book
                            $reservationQuery = $conn->prepare("SELECT * FROM reserve_books WHERE user_id = :userId AND ISBN = :isbn AND status IN ('reserved', 'borrowed')");
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
                                $response['message'] = 'Checkout successful!';
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
        } elseif ($actionType === 'checkin') {
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
                        // Update book status to 'available' if it was 'not available'
                        $checkStatusQuery = $conn->prepare("SELECT status FROM books WHERE ISBN = :isbn");
                        $checkStatusQuery->execute([':isbn' => $bookInput]);
                        $bookStatus = $checkStatusQuery->fetchColumn();
                        if ($bookStatus === 'not available') {
                            $updateAvailableStatusQuery = $conn->prepare("UPDATE books SET status = 'available' WHERE ISBN = :isbn");
                            $updateAvailableStatusQuery->execute([':isbn' => $bookInput]);
                        }
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
    
 // Pay action for overdue books
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action_type'] === 'pay') {
    $userId = $_POST['user_id'];
    $isbn = $_POST['ISBN'];
    $paymentAmount = $_POST['fine']; // This should now be the amount user intends to pay

    // Fetch the current fine from the database
    $fineQuery = $conn->prepare("SELECT fine FROM reserve_books WHERE user_id = :userId AND ISBN = :isbn AND status = 'overdue'");
    $fineQuery->execute([':userId' => $userId, ':isbn' => $isbn]);
    $currentFine = $fineQuery->fetchColumn();

    if ($currentFine === false) {
        // No fine found, return an error
        echo json_encode(['success' => false, 'message' => 'No overdue record found for this user and book.']);
        exit;
    }

    // If payment amount is 0, treat it as a return without payment
    if ($paymentAmount == 0) {
        try {
            // Update record to set return_date and status to 'returned'
            $updateQuery = $conn->prepare("
                UPDATE reserve_books   
                SET return_date = NOW(), status = 'returned'   
                WHERE user_id = :userId AND ISBN = :isbn AND status = 'overdue'
            ");
            $updateQuery->execute([':userId' => $userId, ':isbn' => $isbn]);
            
            // Increment copies in books table
            $increaseCopiesQuery = $conn->prepare("UPDATE books SET copies = copies + 1 WHERE ISBN = :isbn");
            $increaseCopiesQuery->execute([':isbn' => $isbn]);

            // Update book status to 'available' if previously 'not available'
            $checkStatusQuery = $conn->prepare("SELECT status FROM books WHERE ISBN = :isbn");
            $checkStatusQuery->execute([':isbn' => $isbn]);
            $bookStatus = $checkStatusQuery->fetchColumn();
            if ($bookStatus === 'not available') {
                $updateAvailableStatusQuery = $conn->prepare("UPDATE books SET status = 'available' WHERE ISBN = :isbn");
                $updateAvailableStatusQuery->execute([':isbn' => $isbn]);
            }

            // Return success response
            echo json_encode(['success' => true, 'message' => "Book returned successful! Outstanding fine:$currentFine."]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }

    // Check if payment exceeds current fine
    if ($paymentAmount > $currentFine) {
        echo json_encode(['success' => false, 'message' => 'Payment exceeds current fine!']);
        exit;
    }

    try {
        // Calculate the new fine
        $newFine = $currentFine - $paymentAmount;

        // Fetch user details for the payment record
        $userDetailsQuery = $conn->prepare("SELECT user_name, patron_type FROM user_info WHERE user_id = :userId");
        $userDetailsQuery->execute([':userId' => $userId]);
        $userDetails = $userDetailsQuery->fetch(PDO::FETCH_ASSOC);

        // Update record to set return_date, status to 'returned', and update the fine
        $updateQuery = $conn->prepare("
            UPDATE reserve_books   
            SET return_date = NOW(), status = 'returned', fine = :newFine   
            WHERE user_id = :userId AND ISBN = :isbn AND status = 'overdue'
        ");
        $updateQuery->execute([':userId' => $userId, ':isbn' => $isbn, ':newFine' => $newFine]);

        // Increment copies in books table
        $increaseCopiesQuery = $conn->prepare("UPDATE books SET copies = copies + 1 WHERE ISBN = :isbn");
        $increaseCopiesQuery->execute([':isbn' => $isbn]);

        // Update book status to 'available' if previously 'not available'
        $checkStatusQuery = $conn->prepare("SELECT status FROM books WHERE ISBN = :isbn");
        $checkStatusQuery->execute([':isbn' => $isbn]);
        $bookStatus = $checkStatusQuery->fetchColumn();
        if ($bookStatus === 'not available') {
            $updateAvailableStatusQuery = $conn->prepare("UPDATE books SET status = 'available' WHERE ISBN = :isbn");
            $updateAvailableStatusQuery->execute([':isbn' => $isbn]);
        }

        // Insert payment record into the pay table with ISBN
        $insertPaymentQuery = $conn->prepare("
            INSERT INTO pay (user_id, user_name, patron_type, total_pay, payment_date, ISBN) 
            VALUES (:userId, :userName, :patronType, :totalFine, NOW(), :isbn)
        ");
        $insertPaymentQuery->execute([
            ':userId' => $userId,
            ':userName' => $userDetails['user_name'],
            ':patronType' => $userDetails['patron_type'],
            ':totalFine' => $paymentAmount, // The amount paid
            ':isbn' => $isbn // The ISBN of the book being paid for
        ]);

        // Return success response
        echo json_encode(['success' => true, 'message' => 'Return and payment successful!']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

    // Fetch user reservations for display
    $query = "SELECT user_id, user_name, patron_type, book_title, ISBN, copies, status, return_sched 
            FROM reserve_books 
            WHERE status IN ('reserved', 'borrowed', 'overdue') 
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

            .custom-btn2 {
                width: 200px;
                height: 40px;
                font-size: 1rem;
                display: flex;
                align-items: center;
                justify-content: center;
                background-color: #FFBF00;
                color: black;
            }

            .custom-btn1:hover {
                background-color: darkgreen;
                color: white;
            }

            .custom-btn2:hover {
                background-color: gold;
                color: black;
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
                <div class="mb-4">
                    <h1 class="h3">Users</h1>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <button class="btn me-2 custom-btn1" onclick="showCirculateModal()">Quick Check Out</button>
                    <div class="search-input-wrapper mb-1" style="width: 40%;">
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
                                    <?php if ($student['status'] === 'reserved'): ?>
                                        <button class="btn btn-success btn-sm" onclick="borrow('<?php echo htmlspecialchars($student['user_id']); ?>', '<?php echo htmlspecialchars($student['book_title']); ?>')">Check Out</button>
                                    <?php elseif ($student['status'] === 'borrowed' || $student['status'] === 'overdue'): ?>
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
                        <label class="form-label">Current Fine Amount</label>
                        <label class="form-control" id="modalCurrentFine"></label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount to Pay</label>
                        <input type="number" class="form-control" id="modalPaymentAmount" min="0" />
                    </div>
                    <div class="mb-3" id="warningMessage" style="display: none; color: red;">
                        <strong>Warning: Payment exceeds current fine!</strong>
                    </div>
                    <button id="payButton" class="btn btn-primary">Return</button>
                </div>
            </div>
        </div>
    </div>
        <script>
            // Function to reset the Circulate Item modal fields
            function resetCirculateModal() {
                document.getElementById('user_id').value = ''; // Clear user ID
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
                                icon: 'warning',
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
                document.getElementById('user_id').value = userId; // Pre-fill user ID
                document.getElementById('book_input').value = isbn; // Pre-fill ISBN
                document.getElementById('return_sched').value = ''; // Clear return schedule
                const circulateModal = new bootstrap.Modal(document.getElementById('circulateModal'));
                circulateModal.show();
            }
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
                                        document.getElementById('modalCurrentFine').innerText = data.fineDetails.fine; // Set current fine
                                        document.getElementById('modalPaymentAmount').value = ''; // Clear payment input
                                        document.getElementById('warningMessage').style.display = 'none'; // Reset warning message
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
            document.getElementById('payButton').addEventListener('click', function() {
    const currentFine = parseFloat(document.getElementById('modalCurrentFine').innerText) || 0;
    const paymentAmount = parseFloat(document.getElementById('modalPaymentAmount').value) || 0;
    const warningMessage = document.getElementById('warningMessage');

    // Check if payment amount exceeds current fine
    if (paymentAmount > currentFine) {
        warningMessage.style.display = 'block'; // Show warning message
    } else {
        warningMessage.style.display = 'none'; // Hide warning message

        // Proceed with payment
        const userId = document.getElementById('modalUserId').innerText;
        const isbn = document.getElementById('modalISBN').innerText;

        // Make the AJAX request to pay for overdue books
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'circulation.php', true); // Replace with your actual endpoint
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                alert(response.message);
                // You may want to close the modal or refresh the page here
                const paymentModal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
                paymentModal.hide(); // Hide the payment modal
                location.reload(); // Refresh the page to see the updated data
            } else {
                alert('Error: ' + response.message);
            }
        };
        xhr.send(`action_type=pay&user_id=${userId}&ISBN=${isbn}&fine=${paymentAmount}`); // Send payment amount as fine
    }
});

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