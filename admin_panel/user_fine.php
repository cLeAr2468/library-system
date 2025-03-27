<?php
session_start();
// Database connection
include '../component-library/connect.php';
// Database connection
try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $user_name, $user_password);
    // Set PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Check if the user_id is provided in the query string
$user_id = $_GET['user_id'] ?? null;
if ($user_id) {
    // Fetch user fine records
    $fineRecordsQuery = $conn->prepare("SELECT * FROM reserve_books WHERE user_id = ? AND fine != 0");
    $fineRecordsQuery->execute([$user_id]);
    $fineRecords = $fineRecordsQuery->fetchAll(PDO::FETCH_ASSOC);
    if (!$fineRecords) {
        die('No fine records found for this user.');
    }
} else {
    die('No user ID provided');
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pay_amount = $_POST['pay_amount'] ?? 0;
    $user_id = $_POST['user_id'] ?? null;
    $isbn = $_POST['isbn'] ?? null; // Get the ISBN from the form submission

    // Update the fine record in the database
    if ($user_id && $isbn) {
        $updateQuery = $conn->prepare("UPDATE reserve_books SET fine = fine - ? WHERE user_id = ? AND fine > 0 AND ISBN = ?");
        $updateQuery->execute([$pay_amount, $user_id, $isbn]);

        // Send response to the front-end
        echo json_encode(["success" => true, "message" => "Your fine has been updated."]);
        exit;
    }
}
include '../admin_panel/side_nav.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fine Records</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../style/styleshitt.css">
    <link rel="stylesheet" href="../admin_style/design.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    <div class="container">
        <h2>Fine Records</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>User Name</th>
                    <th>Book Title</th>
                    <th>Fine Amount</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fineRecords as $record): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($record['user_id']); ?></td>
                        <td><?php echo htmlspecialchars($record['user_name']); ?></td>
                        <td><?php echo htmlspecialchars($record['book_title']); ?></td>
                        <td><?php echo htmlspecialchars($record['fine']); ?></td>
                        <td><?php echo htmlspecialchars($record['status']); ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#paymentModal" 
                                data-user-id="<?php echo htmlspecialchars($record['user_id']); ?>"
                                data-fine-amount="<?php echo htmlspecialchars($record['fine']); ?>"
                                data-isbn="<?php echo htmlspecialchars($record['ISBN']); ?>"
                                onclick="setPaymentModalData(this)">Update</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel">Update Fine</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Fine: <strong id="totalFineDisplay"></strong></p>
                    <form id="paymentForm" onsubmit="return handlePayment(event)">
                        <input type="hidden" name="user_id" id="modalUserId">
                        <input type="hidden" name="isbn" id="modalISBN"> <!-- Hidden input for ISBN -->
                        <div class="mb-3">
                            <label for="payAmount" class="form-label">Holidays Fine</label>
                            <input type="number" class="form-control" id="payAmount" name="pay_amount" required>
                        </div>
                        <div id="warningMessage" class="text-danger" style="display: none;"></div>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    function setPaymentModalData(button) {
        const userId = button.getAttribute('data-user-id');
        const fineAmount = button.getAttribute('data-fine-amount');
        const isbn = button.getAttribute('data-isbn');
        document.getElementById('modalUserId').value = userId;
        document.getElementById('totalFineDisplay').innerText = fineAmount;
        document.getElementById('modalISBN').value = isbn;
        document.getElementById('payAmount').value = '';
        document.getElementById('warningMessage').style.display = 'none';
    }

    function validatePayment() {
        const fineAmount = parseFloat(document.getElementById('totalFineDisplay').innerText);
        const payAmount = parseFloat(document.getElementById('payAmount').value);
        if (payAmount > fineAmount) {
            Swal.fire('Warning', 'Payment amount exceeds the current fine amount.', 'warning');
            return false;
        }
        return true;
    }

    async function handlePayment(event) {
        event.preventDefault();
        if (!validatePayment()) return;

        const form = document.getElementById('paymentForm');
        const formData = new FormData(form);

        try {
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                Swal.fire('Success', result.message, 'success').then(() => {
                    location.reload(); // Reload the page to refresh the fine table
                });
            } else {
                Swal.fire('Error', 'An error occurred while updating the fine.', 'error');
            }
        } catch (error) {
            Swal.fire('Error', 'An unexpected error occurred.', 'error');
        }
    }
</script>
</body>
</html>
