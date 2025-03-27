<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin_panel/index.php'); // Adjust the path to your login page
    exit();
}

// Database connection
try {
    $pdo = new PDO('mysql:host=localhost;dbname=library-system', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $announcement = $_POST['announcement'] ?? '';
    $currentDateTime = date('Y-m-d H:i:s');
    $imagePath = null;

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $filepaths = "../uploaded_file/";
        $imageFileName = basename($_FILES['image']['name']);
        $targetFilePath = $filepaths . $imageFileName;

        // Move uploaded file to the target directory
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFilePath)) {
            $imagePath = $targetFilePath;
        } else {
            $_SESSION['message'] = 'Failed to upload image.';
            $_SESSION['message_type'] = 'error';
        }
    }

    if (!empty($announcement)) {
        try {
            $announce = $pdo->prepare("INSERT INTO announcement (message, date, image) VALUES (?, ?, ?)");
            $announce->execute([$announcement, $currentDateTime, $imagePath]);

            $_SESSION['message'] = 'Announcement added successfully!';
            $_SESSION['message_type'] = 'success';
            header('Location: announcement.php');
            exit();
        } catch (PDOException $e) {
            $_SESSION['message'] = 'Failed to add announcement: ' . $e->getMessage();
            $_SESSION['message_type'] = 'error';
        }
    } else {
        $_SESSION['message'] = 'Please enter an announcement.';
        $_SESSION['message_type'] = 'warning';
    }
}
include '../admin_panel/sidebar_nav.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Announcement</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link rel="stylesheet" href="../admin_style/style.css">
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
        <div class="container mt-5">
            <h2>Add Announcement</h2>
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                    <?php 
                    echo $_SESSION['message']; 
                    unset($_SESSION['message']); // Clear message after displaying
                    ?>
                </div>
            <?php endif; ?>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="Title" class="form-label">Title</label>
                    <input type="text" name="title" id="title" placeholder="Enter Title" required>
                </div>
                <div class="mb-3">
                    <label for="announcement" class="form-label">Announcement</label>
                    <textarea class="form-control" id="announcement" name="announcement" rows="4" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="image" class="form-label">Optional Image</label>
                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                </div>
                <button type="submit" class="btn btn-primary">Submit</button>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
