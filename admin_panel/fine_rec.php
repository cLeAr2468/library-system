<?php
session_start();
// Database connection
include '../component-library/connect.php';
// Check if a specific user ID is provided
$user_id = $_GET['user_id'] ?? null;
if ($user_id) {
    // Fetch student profile data from the database
    $stud = $conn->prepare("SELECT * FROM user_info WHERE user_id = ?");
    $stud->execute([$user_id]);
    $student = $stud->fetch(PDO::FETCH_ASSOC);
    if (!$student) {
        die('Student not found');
    }
}

// Fetch fine records
$fineRecordsQuery = $conn->prepare("
    SELECT r.user_id, u.first_name, SUM(r.fine) AS total_fine
    FROM reserve_books r
    JOIN user_info u ON r.user_id = u.user_id
    WHERE r.fine > 0
    GROUP BY r.user_id, u.first_name
");
$fineRecordsQuery->execute();
$fineRecords = $fineRecordsQuery->fetchAll(PDO::FETCH_ASSOC);

// Include sidebar or other components
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
<div class="main p-2">
    <div class="row">
        <div class="col-md-10 fw-bold fs-2">
            <p><span>Dashboard</span></p>
        </div>
    </div>
    <div class="container">
        <h2>Fine Records</h2>
        <?php if (empty($fineRecords)): ?>
            <div class="alert alert-warning">No fine records found.</div>
        <?php else: ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>User Name</th>
                        <th>Total Fine Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fineRecords as $record): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['user_id']); ?></td>
                            <td><?php echo htmlspecialchars($record['first_name']); ?></td>
                            <td><?php echo number_format($record['total_fine'], 2); ?> PHP</td>
                            <td>
                                <a href="user_fine.php?user_id=<?php echo urlencode($record['user_id']); ?>&first_name=<?php echo urlencode($record['first_name']); ?>&total_fine=<?php echo urlencode($record['total_fine']); ?>" class="btn btn-primary btn-sm">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
