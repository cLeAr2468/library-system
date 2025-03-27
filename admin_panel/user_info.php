<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin_panel/index.php');
    exit();
}

include '../component-library/connect.php';

try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $user_name, $user_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
} catch (PDOException $e) {
    die('Connection failed: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    try {
        $deleteQuery = $conn->prepare("DELETE FROM user_info WHERE user_id = ?");
        $deleteQuery->execute([$user_id]);
        echo json_encode(['status' => 'success', 'message' => 'User deleted successfully!', 'redirect' => 'Student_list.php']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete user: ' . $e->getMessage()]);
    }
    exit();
}

// Check if the user_id is provided in the query string
$user_id = $_GET['user_id'] ?? null;
if ($user_id) {
    // Fetch student profile data from the database
    $stud = $conn->prepare("SELECT * FROM user_info WHERE user_id = ?");
    $stud->execute([$user_id]);
    $student = $stud->fetch(PDO::FETCH_ASSOC);
    if (!$student) {
        die('Student not found');
    }

    // Fetch counts for reserved, borrowed, and overdue books
    $reservedBooksCount = $conn->prepare("SELECT COUNT(*) as total_reserved FROM reserve_books WHERE user_id = ? AND status = 'reserved'");
    $reservedBooksCount->execute([$user_id]);
    $reservedBooksCount = $reservedBooksCount->fetch(PDO::FETCH_ASSOC)['total_reserved'];

    $borrowedBooksCount = $conn->prepare("SELECT COUNT(*) as total_borrowed FROM reserve_books WHERE user_id = ? AND status = 'borrowed'");
    $borrowedBooksCount->execute([$user_id]);
    $borrowedBooksCount = $borrowedBooksCount->fetch(PDO::FETCH_ASSOC)['total_borrowed'];

    $overdueBooksCount = $conn->prepare("SELECT COUNT(*) as total_overdue FROM reserve_books WHERE user_id = ? AND status = 'overdue'");
    $overdueBooksCount->execute([$user_id]);
    $overdueBooksCount = $overdueBooksCount->fetch(PDO::FETCH_ASSOC)['total_overdue'];

    // Fallback for profile image
    $profile_image = $student['images'] ?? '../images/prof.jpg';
} else {
    die('No user ID provided');
}

// Handle image upload and update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    $targetDir = "../uploaded_file/";
    $fileName = basename($_FILES["profile_image"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

    // Allow certain file formats
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
    if (in_array($fileType, $allowedTypes)) {
        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $targetFilePath)) {
            try {
                $update = $conn->prepare("UPDATE user_info SET images = ? WHERE user_id = ?");
                $update->execute([$fileName, $user_id]);
                $_SESSION['profile_image'] = $fileName;
                $_SESSION['message'] = "Profile picture updated successfully!";
                $_SESSION['message_type'] = "success";
            } catch (PDOException $e) {
                $_SESSION['message'] = "Failed to update profile picture: " . $e->getMessage();
                $_SESSION['message_type'] = "error";
            }
        } else {
            $_SESSION['message'] = "Sorry, there was an error uploading your file.";
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "Sorry, only JPG, JPEG, PNG, and GIF files are allowed.";
        $_SESSION['message_type'] = "error";
    }
    header("Location: user_info.php?user_id=$user_id");
    exit();
}

// Fetch total fine from the database
$totalFineQuery = $conn->prepare("SELECT SUM(fine) as fine FROM reserve_books WHERE user_id = ?");
$totalFineQuery->execute([$user_id]);
$totalFine = $totalFineQuery->fetch(PDO::FETCH_ASSOC)['fine'] ?? 0;

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_amount'])) {
    $paymentAmount = floatval($_POST['pay_amount']);
    if ($paymentAmount <= 0) {
        echo json_encode(['status' => 'error', 'message' => "Please enter a valid payment amount."]);
        exit();
    }

    // Fetch all fines for this user, ordered by the oldest first
    $allFinesQuery = $conn->prepare("SELECT * FROM reserve_books WHERE user_id = :userId ORDER BY fine ASC");
    $allFinesQuery->execute([':userId' => $user_id]);
    $allFines = $allFinesQuery->fetchAll(PDO::FETCH_ASSOC);

    if ($paymentAmount > $totalFine) {
        echo json_encode(['status' => 'warning', 'message' => "Payment amount exceeds the total fine. Please enter a valid amount."]);
        exit();
    }

    // Deduct payment from fines
    foreach ($allFines as $fineRecord) {
        $fineId = $fineRecord['reserve_id'];
        $currentFine = floatval($fineRecord['fine']);
        if ($paymentAmount >= $currentFine) {
            $paymentAmount -= $currentFine;
            $newFine = 0;
        } else {
            $newFine = $currentFine - $paymentAmount;
            $paymentAmount = 0;
        }
        $updateQuery = $conn->prepare("UPDATE reserve_books SET fine = :newFine WHERE reserve_id = :fineId");
        $updateQuery->execute([':newFine' => $newFine, ':fineId' => $fineId]);

        if ($paymentAmount <= 0) {
            break;
        }
    }

    // Insert payment record into the pay table
    $userDetailsQuery = $conn->prepare("SELECT user_name, patron_type FROM user_info WHERE user_id = :userId");
    $userDetailsQuery->execute([':userId' => $user_id]);
    $userDetails = $userDetailsQuery->fetch(PDO::FETCH_ASSOC);

    $insertPaymentQuery = $conn->prepare("
        INSERT INTO pay (user_id, user_name, patron_type, total_pay, payment_date) 
        VALUES (:userId, :userName, :patronType, :totalPay, NOW())
    ");
    $insertPaymentQuery->execute([
        ':userId' => $user_id,
        ':userName' => $userDetails['user_name'],
        ':patronType' => $userDetails['patron_type'],
        ':totalPay' => $_POST['pay_amount']
    ]);

    echo json_encode(['status' => 'success', 'message' => "Payment processed successfully!"]);
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
include '../admin_panel/side_nav.php';
?>

<!-- Include Bootstrap and SweetAlert -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="../style/styleshitt.css">
<style>
    * {
        box-sizing: border-box;
    }
    .border-box {
        border: 1px solid #dee2e6;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        background-color: white;
    }
    .student-cover {
        width: 150px;
        height: 200px;
        object-fit: cover;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        margin-bottom: 10px;
        box-shadow: 8px 8px 10px rgba(0, 0, 0, 0.1);
        border-radius: 1rem;
        margin-top: 11%;
        margin-right: 60%;
        cursor: pointer;
    }
    .change-photo-btn {
        margin-right: 30%;
        width: 40%;
        border-radius: 40px;
    }
    .alert {
        margin-top: 20px;
    }
</style>

<div class="main p-3">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-10 fw-bold fs-3">
                <p><span>Dashboard</span></p>
            </div>
        </div>
    </div>
    <div class="border p-4 shadow container d-flex justify-content-center align-items-center" style="min-height: 50vh; background-color: white;">
        <div class="w-100">
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type']; ?> text-center">
                    <?php echo $_SESSION['message'];
                    unset($_SESSION['message'], $_SESSION['message_type']); ?>
                </div>
            <?php endif; ?>
            <div class="student-details-container row justify-content-center">
                <div class="col-md-5">
                    <h2 class="name-center">
                        <?php 
                        echo htmlspecialchars($student['first_name'] . ' ' . ($student['middle_name'] ?? '') . ' ' . htmlspecialchars($student['last_name'])); 
                        ?>
                    </h2>
                    <table class="table student-details-table">
                        <tr>
                            <th>Student ID</th>
                            <td><?php echo htmlspecialchars($student['user_id']); ?></td>
                        </tr>
                        <tr>
                            <th>Patron Type</th>
                            <td><?php echo htmlspecialchars($student['patron_type']); ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                        </tr>
                        <tr>
                            <th>Address</th>
                            <td><?php echo htmlspecialchars($student['address']); ?></td>
                        </tr>
                        <tr>
                            <th>Account Status</th>
                            <td><?php echo htmlspecialchars($student['account_status']); ?></td>
                        </tr>
                        <tr>
                            <th>Reserved Books</th>
                            <td>
                                <?php echo htmlspecialchars($reservedBooksCount); ?>
                                [<a href="user_reserve.php?user_id=<?php echo urlencode($user_id); ?>">Browse</a>]
                            </td>
                        </tr>
                        <tr>
                            <th>Borrowed Books</th>
                            <td>
                                <?php echo htmlspecialchars($borrowedBooksCount); ?>
                                [<a href="borrowed.php?user_id=<?php echo urlencode($user_id); ?>">Browse</a>]
                            </td>
                        </tr>
                        <tr>
                            <th>Overdue</th>
                            <td>
                                <?php echo htmlspecialchars($borrowedBooksCount); ?>
                                [<a href="overdue_record.php?user_id=<?php echo urlencode($user_id); ?>">Browse</a>]
                            </td>
                        </tr>
                        <tr>
                            <th>Total Fine</th>
                            <td>
                                <?php echo htmlspecialchars($totalFine); ?>
                                <?php if ($totalFine > 0): ?>
                                    [<a href="user_fine.php?user_id=<?php echo urlencode($user_id); ?>">Edit</a>]
                                    [<a href="#" data-bs-toggle="modal" data-bs-target="#paymentModal">Pay</a>]
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="paymentModalLabel">Pay Fine</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Total Fine: <strong id="totalFineDisplay"><?php echo htmlspecialchars($totalFine); ?></strong></p>
                                <form method="POST" id="paymentForm">
                                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
                                    <div class="mb-3">
                                        <label for="payAmount" class="form-label">Amount to Pay</label>
                                        <input type="number" class="form-control" id="payAmount" name="pay_amount" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Submit Payment</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <img src="../uploaded_file/<?php echo htmlspecialchars($profile_image); ?>" alt="Student Profile" class="student-cover" id="studentImage">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="file" name="profile_image" id="fileInput" style="display: none;" accept="image/*">
                        <button type="submit" id="changePhotoBtn" class="btn btn-primary change-photo-btn" style="display: none;">Change Photo</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById("studentImage").addEventListener("click", function() {
        document.getElementById("fileInput").click();
    });
    document.getElementById("fileInput").addEventListener("change", function(event) {
        var file = event.target.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById("studentImage").src = e.target.result;
            }
            reader.readAsDataURL(file);
            document.getElementById("changePhotoBtn").style.display = "block";
        }
    });

    document.getElementById("paymentForm").addEventListener("submit", function(event) {
        event.preventDefault();
        const formData = new FormData(this);
        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: data.message,
                    confirmButtonText: 'OK'
                }).then(() => {
                    location.reload();
                });
            } else if (data.status === 'warning') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Warning!',
                    text: data.message,
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: data.message,
                });
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred while processing your payment.',
            });
        });
    });
</script>