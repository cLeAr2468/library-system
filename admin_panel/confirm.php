<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin_panel/index.php');
    exit();
}

include "../component-library/connect.php";

function sendEmail($to, $subject, $message) {
    $headers = "From: Online Library Administrator <reyesjerald638@gmail.com>\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    mail($to, $subject, $message, $headers);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["user_id"]) && isset($_POST["action"])) {
    $user_id = $_POST["user_id"];
    $action = $_POST["action"];
    try {
        if ($action == 'approve') {
            $updateQuery = "UPDATE user_info SET status = 'approved' WHERE user_id = :user_id";
            $updates = $conn->prepare($updateQuery);
            $updates->bindParam(':user_id', $user_id);
            $updates->execute();

            $emailQuery = "SELECT email FROM user_info WHERE user_id = :user_id";
            $emails = $conn->prepare($emailQuery);
            $emails->bindParam(':user_id', $user_id);
            $emails->execute();
            $emailResult = $emails->fetch(PDO::FETCH_ASSOC);
            $user_email = $emailResult['email'];

            // Send approval email
            $subject = "Account Approved";
            $message = "Your account has been approved! Please login now: <a href='http://localhost/library-system/index.php'>Login Here</a>";
            sendEmail($user_email, $subject, $message);
            echo "success";
        } elseif ($action == 'remove') {
            $emailQuery = "SELECT email FROM user_info WHERE user_id = :user_id";
            $emails = $conn->prepare($emailQuery);
            $emails->bindParam(':user_id', $user_id);
            $emails->execute();
            $emailResult = $emails->fetch(PDO::FETCH_ASSOC);
            $user_email = $emailResult['email'];

            // Delete user
            $deleteQuery = "DELETE FROM user_info WHERE user_id = :user_id";
            $deleted = $conn->prepare($deleteQuery);
            $deleted->bindParam(':user_id', $user_id);
            $deleted->execute();

            // Send decline email
            $subject = "Account Declined";
            $message = "Your account has been declined. Please register again with valid information: <a href='http://localhost/library-system/student/student_login.php'>Register Here</a>";
            sendEmail($user_email, $subject, $message);
            echo "success";
        }
        exit();
    } catch (PDOException $e) {
        error_log($e->getMessage());
        echo "error";
        exit();
    }
}

try {
    $search = "SELECT * FROM user_info WHERE status != 'approved'";
    $stat = $conn->prepare($search);
    $stat->execute();
    $result = $stat->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Query failed: " . $e->getMessage());
}

include '../admin_panel/side_nav.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Confirmation Account</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
        <link rel="stylesheet" href="../style/styleshitt.css">
</head>
<style>
.action-buttons {
    display: flex; /* Use flexbox to align buttons */
    gap: 10px; /* Space between buttons */
}

.approve-btn, .remove-btn {
    padding: 6px 10px;
    border-radius: 5px;
    cursor: pointer;
}

.approve-btn {
    background-color: darkgreen;
    color: white;
}

.remove-btn {
    background-color: gold;
    color: black;
}

table {
    position: relative;
    border-collapse: collapse;
    width: auto;
    margin: 0 auto; /* Center the table */
    table-layout: auto; /* Adjust the table size based on content */
}

th, td {
    background-color: #ddd;
    border: 1px inset;
    padding: 10px;
    text-align: left;
    word-wrap: break-word; /* Wrap long content in cells */
    vertical-align: middle;
}

.table thead th {
    background-color: rgb(3, 163, 3);
    color: white;
}
    </style>
<body>

<div class="main p-3">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-10 fw-bold fs-3">
                <p><span>Dashboard</span></p>
            </div>
        </div>
    </div>
<div class="container my-3">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h1 class="card-title">User Confirmation Account</h1>
                <div class="mb-3" style="width: 40%;">
                    <div class="search-input-wrapper position-relative">
                        <i class="bi bi-search position-absolute" style="left: 15px; top: 50%; transform: translateY(-50%);"></i>
                        <input type="text" id="searchInput" placeholder="Search ID or Name" class="form-control control rounded-pill ps-5" onkeyup="searchUsers()">
                        <div id="suggestions" class="suggestions-box position-absolute" style="z-index: 1000; background: whitesmoke; border: 1px solid #ddd; display: none;"></div>
                    </div>
                </div>
            </div>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>First Name</th>
                        <th>Middle Name</th>
                        <th>LAst Name</th>
                        <th>Patron Type</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th colspan="2">Action</th>
                    </tr>
                </thead>
                <tbody id="userTableBody">
                <?php foreach($result as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['first_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['middle_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['patron_type']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['address']); ?></td>
                    <td>
                        <div class="action-buttons"> <!-- Add a wrapper for the buttons -->
                            <button class="approve-btn btn-success action-btn" data-user-id="<?php echo htmlspecialchars($row['user_id']); ?>" onclick="approveAccount('<?php echo htmlspecialchars($row['user_id']); ?>')">Approve</button>
                            <button class="remove-btn btn-danger action-btn" data-user-id="<?php echo htmlspecialchars($row['user_id']); ?>" onclick="removeAccount('<?php echo htmlspecialchars($row['user_id']); ?>')">Decline</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/js/bootstrap.min.js"></script> 
<script>
    function approveAccount(user_id) {
    if (confirm("Are you sure you want to approve this account?")) {
        sendRequest(user_id, 'approve');
    }
}
function removeAccount(user_id) {
    if (confirm("Are you sure you want to decline this account?")) {
        sendRequest(user_id, 'remove');
    }
}
function sendRequest(user_id, action) {
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "confirm.php?" + new Date().getTime(), true); // Prevent caching
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            if (xhr.responseText.trim() === "success") {
                var row = document.querySelector("button[data-user-id='" + user_id + "']").closest('tr');
                if (action === 'approve') {
                    alert("Account approved successfully!");
                } else if (action === 'remove') {
                    alert("Account declined successfully!");
                }
                row.parentNode.removeChild(row); // Remove row from the table
            } else {
                alert("Failed to process the request.");
            }
        }
    };
    xhr.send("user_id=" + encodeURIComponent(user_id) + "&action=" + encodeURIComponent(action));
}

function searchUsers() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#userTableBody tr');
            rows.forEach(row => {
                const userId = row.cells[0].textContent.toLowerCase();
                const userName = row.cells[1].textContent.toLowerCase();
                if (userId.includes(input) || userName.includes(input)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
</script>
</body>
</html>