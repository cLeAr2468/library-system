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
    $fineRecordsQuery = $conn->prepare("SELECT * FROM reserve_books WHERE user_id = ? AND status = 'overdue'");
    $fineRecordsQuery->execute([$user_id]);
    $fineRecords = $fineRecordsQuery->fetchAll(PDO::FETCH_ASSOC);
    if (!$fineRecords) {
        die('No fine records found for this user.');
    }
} else {
    die('No user ID provided');
}

include '../admin_panel/side_nav.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserved Record</title>
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
        <h2>Reserved Record</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>User Name</th>
                    <th>Book Title</th>
                    <th>Status</th>

                </tr>
            </thead>
            <tbody>
                <?php foreach ($fineRecords as $record): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($record['user_id']); ?></td>
                        <td><?php echo htmlspecialchars($record['user_name']); ?></td>
                        <td><?php echo htmlspecialchars($record['book_title']); ?></td>
                        <td><?php echo htmlspecialchars($record['status']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>
