<?php
session_start();
include '../component-library/connect.php';

try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $user_name, $user_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Assume the logged-in student ID is stored in session
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('location:student_login.php');
    exit();
}

// Logout logic
if (isset($_POST['logout'])) {
    unset($_SESSION['user_id']);
    header('location:../index.php');
    exit();
}

// Pagination variables
$limit = 10; // Number of books to display per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page
$offset = ($page - 1) * $limit; // Calculate offset
$totalPages = 0; // Initialize totalPages
try {
    // Prepare SQL to get the top returned books
    $stmt = $conn->prepare("
        SELECT b.title, b.books_image, b.status, b.copies, b.copyright, b.publisher, b.author, b.ISBN, COUNT(rb.id) AS return_count, b.id
        FROM reserve_books rb
        JOIN books b ON rb.id = b.id
        WHERE rb.status = 'returned' 
        GROUP BY b.title, b.books_image, b.status, b.copies, b.copyright, b.publisher, b.author, b.ISBN, b.id
        ORDER BY return_count DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count for pagination
    $countStmt = $conn->prepare("
        SELECT COUNT(DISTINCT b.title) AS total_count
        FROM reserve_books rb
        JOIN books b ON rb.id = b.id
        WHERE rb.status = 'returned'
    ");
    $countStmt->execute();
    $totalBooks = $countStmt->fetchColumn();
    
    // Calculate total pages only if we have books
    if ($totalBooks > 0) {
        $totalPages = ceil($totalBooks / $limit); // Calculate total pages
    }
} catch (PDOException $e) {
    // Handle error
    echo "Error: " . $e->getMessage();
    exit(); // Exit on error
}

// Fetch student profile data
$stud = $conn->prepare("SELECT * FROM user_info WHERE user_id = ?");
$stud->execute([$user_id]);
$student = $stud->fetch(PDO::FETCH_ASSOC);
$profile_image = $student['images'] ?? '../images/prof.jpg'; // Fallback if no image

// Handle profile update via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateProfile'])) {
    $user_name = $_POST['user_name'];
    $patron_type = $_POST['patron_type'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $current_password = $_POST['current_password'] ?? null;
    $new_password = $_POST['new_password'] ?? null;
    $confirm_password = $_POST['confirm_password'] ?? null;

    // Validation
    if (empty($user_name) || empty($patron_type) || empty($email) || empty($address)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit;
    }

    try {
        // Update user info without changing the password initially
        $stmt = $conn->prepare("UPDATE user_info SET user_name = ?, patron_type = ?, email = ?, address = ? WHERE user_id = ?");
        $stmt->execute([$user_name, $patron_type, $email, $address, $user_id]);

        // Check if the user provided the current password
        if (!empty($current_password)) {
            // Fetch the current user's password
            $stmt = $conn->prepare("SELECT password FROM user_info WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verify current password
            if ($result && !password_verify($current_password, $result['password'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Current password is not correct.']);
                exit;
            }

            // Update password if new password is provided
            if (!empty($new_password)) {
                if ($new_password !== $confirm_password) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'New password and confirm password do not match.']);
                    exit;
                }
                // Hash the new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE user_info SET password = ? WHERE user_id = ?");
                $stmt->execute([$hashed_password, $user_id]);
            }
        }

        // Ensure JSON response
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully.']);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error updating profile: ' . $e->getMessage()]);
    }
    exit(); // Ensure we exit after handling the AJAX request
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NwSSU : Catalogs</title>
    <link rel="stylesheet" href="../admin_style/design.css">
    <style>
        .book-image-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 110px;
            margin-bottom: 5%;
            margin-top: 5%;
        }
        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            z-index: 1000;
            min-width: 10rem;
            padding: 0.5rem 0;
            margin: 0;
            font-size: 1rem;
            color: #212529;
            text-align: left;
            list-style: none;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid rgba(0, 0, 0, 0.15);
            border-radius: 0.25rem;
            width: auto;
            height: auto;
        }
        .dropdown-item {
            width: 100%;
            padding: 0.25rem 1.5rem;
            clear: both;
            font-weight: 400;
            color: #212529;
            text-align: inherit;
            white-space: nowrap;
            background-color: transparent;
            border: 0;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="#">
            <img src="../images/logo.png" alt="NwSSU Logo" style="width: 40px; height: 40px;">
            NwSSU
        </a>
        <div class="mx-auto text-center">
            <a class="navbar-brand fw-bold text-uppercase" href="#">Library Management System</a>
        </div>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="dropdown ms-2">
            <a class="btn btn-outline-light dropdown-toggle" href="#" role="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="../uploaded_file/<?php echo htmlspecialchars($profile_image); ?>" alt="Profile Picture" class="profile-pic me-2">
                My Account
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                <li>
                    <a class="dropdown-item" href="profile.php?user_id=<?php echo htmlspecialchars($user_id); ?>">Profile</a>
                </li>
                <li>
                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#settingsModal" id="settingsBtn">Settings</a>
                </li>
                <li>
                    <form method="POST" action="">
                        <button type="submit" name="logout" class="dropdown-item logout">Logout</button>
                    </form>
                </li>
            </ul>
        </div>
        <!-- Modal for Settings -->
        <div class="modal fade" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="settingsModalLabel">Settings</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="updateForm">
                            <div class="mb-3">
                                <label for="userId" class="form-label">User ID</label>
                                <input type="text" class="form-control" id="userId" value="<?php echo htmlspecialchars($user_id); ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="user_name" class="form-label">User Name</label>
                                <input type="text" class="form-control" id="user_name" name="user_name" value="<?php echo htmlspecialchars($student['user_name']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="patron_type" class="form-label">Patron Type</label>
                                <input type="text" class="form-control" id="patron_type" name="patron_type" value="<?php echo htmlspecialchars($student['patron_type']); ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($student['address']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                            <input type="hidden" name="updateProfile" value="1"> <!-- Hidden field to identify update request -->
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="saveChanges">Update</button>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <nav class="sub-navbar">
        <div class="container">
            <ul class="navbar-nav d-flex justify-content-center">
                <li class="nav-item"><a class="nav-link" href="../student/home.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="../student/studbooks_display.php">Catalog</a></li>
                <li class="nav-item"><a class="nav-link" href="../student/topcollect.php">Top Collection</a></li>
                <li class="nav-item"><a class="nav-link" href="../student/newcollect.php">New Collections</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="aboutDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        About
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="aboutDropdown">
                        <li>
                            <a class="dropdown-item" href="../student/Mission_VIsion.php">Library Mission/Vision</a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="onlineServicesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Online Services
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="onlineServicesDropdown">
                        <li>
                            <a class="dropdown-item" href="https://www.proquest.com/" target="_blank">Proquest Central Database</a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="https://ejournals.ph/" target="_blank">Philippine E-Journals</a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="https://starbooks.ph/" target="_blank">Dost Starbooks</a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="askALibrarianDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Ask a Librarian?
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="askALibrarianDropdown">
                        <li>
                            <a class="dropdown-item" href="https://mail.google.com/mail/?view=cm&fs=1&to=nwssulibrarysjcampus@gmail.com" target="_blank">
                                <i class="bi bi-envelope me-2"></i> <!-- Gmail icon -->
                                Email Account
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="https://www.facebook.com/NwSSU.sjclibrary?mibextid=LQQJ4d" target="_blank">
                                <i class="bi bi-messenger me-2"></i> <!-- Messenger icon -->
                                Messenger
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
    <div class="container mt-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="card-title">Top Collection</h2>
                </div>
                <table class="table table-bordered">
                    <thead class="thead-light">
                        <tr>
                            <th></th> <!-- Empty header for book image column -->
                            <th>Title</th>
                            <th>Authors/Editors</th>
                            <th>Publisher</th>
                            <th>Status</th>
                            <th>Copies</th>
                        </tr>
                    </thead>
                    <tbody id="bookTable">
                        <?php if (empty($books)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No new collections found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($books as $book): ?>
                                <tr>
                                    <td class="text-center book-image-container">
                                        <?php if (!empty($book['books_image'])): ?>
                                            <img src="../uploaded_file/<?php echo htmlspecialchars($book['books_image']); ?>" alt="Book Cover" class="img-thumbnail" style="width: 80px; height: 110px;">
                                        <?php else: ?>
                                            <div style="width: 80px; height: 110px; background-color: rgba(232, 232, 232, 0.65); display: flex; align-items: center; justify-content: center; color: #555;">
                                                <b>Book Cover</b>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="book-info">
                                            <div>
                                                <a href="studbook_detail.php?id=<?php echo urlencode($book['id']); ?>" class="book-title">
                                                    <?php echo htmlspecialchars($book['title']); ?>
                                                </a><br>
                                                <small>Copy Right: <?php echo htmlspecialchars($book['copyright']); ?></small><br>
                                                <small>ISBN: <?php echo htmlspecialchars($book['ISBN']); ?></small><br>
                                                <small>Call No: <?php echo htmlspecialchars($book['id']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="book-info">
                                        <a href="selected_author.php?author=<?php echo urlencode($book['author']); ?>" class="books-link">
                                        <?php echo htmlspecialchars($book['author']); ?>
                                        </a>
                                    </td>
                                    <td class="book-info">
                                        <a href="publisher_browse.php?publisher=<?php echo urlencode($book['publisher']); ?>" class="books-link">
                                            <?php echo htmlspecialchars($book['publisher']); ?>
                                        </a>
                                    </td>
                                    <td class="text-center"><?php echo htmlspecialchars($book['status']); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($book['copies']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
<!-- Pagination -->
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <!-- Pagination -->
            </div>
        </div>
    </div>
    <footer class="footer">
        <div class="container text-center">
            <span class="text-muted">© 2024 NwSSU Library. All rights reserved.</span>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.getElementById('saveChanges').addEventListener('click', function() {
            // Get form data
            let formData = new FormData(document.getElementById('updateForm'));
            // Send AJAX request
            fetch('profile.php', { // Ensure this points to the correct endpoint
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Profile Updated',
                            text: data.message || 'Your profile has been updated successfully!',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Update Failed',
                            text: data.message || 'An error occurred while updating your profile. Please try again.',
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
        });
    </script>
</body>
</html>