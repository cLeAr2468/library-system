<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin_panel/index.php'); // Adjust the path to your login page
    exit();
}
include '../component-library/connect.php';

// Database connection
try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $user_name, $user_password);
    // Set PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Fetch user data from the database
$query = $conn->query("SELECT * FROM user_info WHERE status = 'approved'");
$userinfo = $query->fetchAll(PDO::FETCH_ASSOC);

function userExists($userId, $email) {
    global $conn;
    $userIdExists = $conn->prepare("SELECT COUNT(*) FROM user_info WHERE user_id = ?");
    $userIdExists->execute([$userId]);
    $emailExists = $conn->prepare("SELECT COUNT(*) FROM user_info WHERE email = ?");
    $emailExists->execute([$email]);
    if ($userIdExists->fetchColumn() > 0) {
        return "User ID already exists! Please enter another ID.";
    } elseif ($emailExists->fetchColumn() > 0) {
        return "Email already exists! Please enter another email.";
    }
    return false; // No duplicates found
}

function insertOrUpdateUser($data, $Id = null) {
    global $conn;
    try {
        // If inserting a new user, check for existing user_id or email
        if (!$Id) {
            $duplicateMessage = userExists($data['user_id_input'], $data['email']);
            if ($duplicateMessage) {
                echo json_encode(['success' => false, 'message' => $duplicateMessage]);
                return; // Exit the function if user exists
            }
        }
        $imagePath = null; // Default to null
        // Check if the image is uploaded and no error occurred
        if (isset($_FILES['images']) && $_FILES['images']['error'] === UPLOAD_ERR_OK) {
            $originalFileName = basename($_FILES['images']['name']);
            // Create a unique filename to avoid overwriting
            $uniqueFileName = time() . '_' . $originalFileName;
            $imagePath = '../uploaded_file/' . $uniqueFileName;
            // Ensure the upload directory exists
            if (!file_exists(dirname($imagePath))) {
                mkdir(dirname($imagePath), 0777, true);
            }
            // Move the uploaded file to the target directory
            if (!move_uploaded_file($_FILES['images']['tmp_name'], $imagePath)) {
                throw new Exception('Failed to upload the image.');
            }
        }
        if ($Id) {
            // Update existing user
            $dataToBind = [];
            $query = "UPDATE user_info SET first_name = ?, middle_name = ?, last_name = ?, patron_type = ?, email = ?, address = ?, status = ?, account_status = ? ";
            $dataToBind[] = $data['first_name'];
            $dataToBind[] = $data['middle_name'];
            $dataToBind[] = $data['last_name'];
            $dataToBind[] = $data['patron_type'];
            $dataToBind[] = $data['email'];
            $dataToBind[] = $data['address'];
            $dataToBind[] = $data['status'];
            $dataToBind[] = $data['account_status'];
            // Update password if a new one is provided
            if (!empty($data['password'])) {
                $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
                $query .= ", password = ?";
                $dataToBind[] = $hashedPassword; // Add hashed password to data binding
            }
            // Check if new image is provided
            if ($imagePath) {
                $query .= ", images = ?";
                $dataToBind[] = $imagePath; // Add new image path to data binding
            }
            $query .= " WHERE user_id = ?";
            $dataToBind[] = $Id; // Use user ID for the WHERE clause
            $stmt = $conn->prepare($query);
            $stmt->execute($dataToBind);
            echo json_encode(['success' => true, 'message' => "User updated successfully!"]);
        } else {
            // Insert new user
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT); // Hash the password for security
            $stmt = $conn->prepare("INSERT INTO user_info (
                user_id, first_name, middle_name, last_name, patron_type, email, address, password, images, status, account_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
            $data['images'] = $imagePath; // Add image path to the data array
            $dataToBind = [
                $data['user_id_input'], // Bind the User ID input
                $data['first_name'],
                $data['middle_name'],
                $data['last_name'],
                $data['patron_type'],
                $data['email'],
                $data['address'],
                $hashedPassword, // Insert the hashed password
                $data['images'],
                $data['status'],
            ];
            $stmt->execute($dataToBind);
            echo json_encode(['success' => true, 'message' => "User added successfully!"]);
        }
    } catch (Exception $e) {
        error_log("Error inserting/updating user: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => "Failed to " . ($Id ? "update" : "add") . " user: " . $e->getMessage()]);
    }
}

// Insert or update user if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if passwords match
    if ($_POST['password'] !== $_POST['confirm_password']) {
        echo json_encode(['success' => false, 'message' => "Passwords do not match!"]);
        exit();
    }
    $Id = isset($_POST['user_id']) ? $_POST['user_id'] : null; // Check if user ID is present
    insertOrUpdateUser($_POST, $Id);
    exit(); // Exit to prevent further processing
}

include '../admin_panel/side_nav.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NwSSU : User Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../style/styleshitt.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .border-box {
            border: 1px solid #ddd; /* Border color */
            padding: 20px; /* Padding inside the box */
            margin: 20px 0; /* Margin around the box */
            border-radius: 8px; /* Rounded corners */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Subtle shadow for a 3D effect */
        }
        .table thead th {
            background-color: rgb(3, 163, 3);
            color: white;
            font-weight: bold;
            border-bottom: #1979ca;
        }
        .icon-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }
        .icon-size {
            font-size: 1.5rem;
        }
        .action-column {
            text-align: center;
        }
        .custom-btn {
            width: 140px;
            height: 30px;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .search-input-wrapper {
            width: 30%; /* Adjust width as needed */
        }
        .control {
            padding-left: 40px; /* Ensure there's enough space for the icon */
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
        <div class="border-box">
            <div class="col-md-12 d-flex justify-content-between align-items-center mb-3">
                <button class="btn btn-primary custom-btn ms-3" data-bs-toggle="modal" data-bs-target="#addUserModal" onclick="openAddUserModal()">Add user</button>
                <div class="search-input-wrapper position-relative mb-1">
                    <i class="bi bi-search position-absolute" style="left: 15px; top: 50%; transform: translateY(-50%);"></i>
                    <input type="text" id="searchInput" placeholder="Search ID or Name" class="form-control control rounded-pill ps-5" onkeyup="searchUsers()">
                </div>
            </div>
            <div class="container mt-3">
                <table class="table table-bordered">
                    <thead class="thead-light">
                        <tr>
                            <th>User ID</th>
                            <th>First Name</th>
                            <th>Middle Name</th>
                            <th>Last Name</th>
                            <th>Patron Type</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>Account Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody">
                        <?php foreach ($userinfo as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                                <td><?php echo htmlspecialchars($user['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['middle_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['patron_type']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['address']); ?></td>
                                <td><?php echo htmlspecialchars($user['account_status']); ?></td>
                                <td class="action-column">
                                    <div class="icon-container">
                                        <a href="user_info.php?user_id=<?php echo urlencode($user['user_id']); ?>" class="view-btn">
                                            <i class="bi bi-eye icon-size" title="View Details"></i>
                                        </a>
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#addUserModal" onclick="populateEditForm(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                            <i class="bi bi-pencil-square icon-size" title="Edit User"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addUserForm" method="POST" enctype="multipart/form-data">
                        <input type="hidden" id="user_id" name="user_id" value="">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="user_id_input" class="form-label">User ID</label>
                                <input type="text" class="form-control" id="user_id_input" name="user_id_input" required>
                            </div>
                            <div class="col-md-4">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                            <div class="col-md-4">
                                <label for="middle_name" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="middle_name" name="middle_name">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                            <div class="col-md-4">
                                <label for="patron_type" class="form-label">Patron Type</label>
                                <select class="form-select" id="patron_type" name="patron_type" required>
                                    <option value="student-BSA">Choose Patron type</option>
                                    <option value="student-BSA">student-BSA</option>
                                    <option value="student-BSCRIM">student-BSCRIM</option>
                                    <option value="student-BAT">student-BAT</option>
                                    <option value="student-BSIT">student-BSIT</option>
                                    <option value="student-BTLED">student-BTLED</option>
                                    <option value="student-BEED">student-BEED</option>
                                    <option value="student-BSF">student-BSF</option>
                                    <option value="student-BSABE">student-BSABE</option>
                                    <option value="Faculty">Faculty</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" class="form-control" id="address" name="address" required>
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="approved">approved</option>
                                    <option value="pending">pending</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="account_status" class="form-label">Account Status</label>
                                <select class="form-select" id="account_status" name="account_status" required>
                                    <option value="active">active</option>
                                    <option value="inactive">inactive</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label" id="passwordLabel">New Password</label>
                                <input type="password" class="form-control" id="password" name="password">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="images" class="form-label">Photos</label>
                                <input type="file" class="form-control" id="images" name="images" accept="image/*">
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label" id="confirmPasswordLabel">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="preview" class="form-label">Preview</label>
                                <img id="preview" src="../images/prof.jpg" alt="Image Preview" style="display:none; max-width: 100px; height: 100px;" />
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close">Close</button>
                            <button type="submit" class="btn btn-primary" id="submitButton">Add User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Image preview functionality
    document.getElementById('images').addEventListener('change', function(event) {
        const preview = document.getElementById('preview');
        const file = event.target.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block'; // Show the preview image
        };
        if (file) {
            reader.readAsDataURL(file);
        }
    });

    // Function to open the modal for adding a new user
    function openAddUserModal() {
        // Reset the form and labels
        document.getElementById('addUserModalLabel').innerText = 'Add New User';
        document.getElementById('submitButton').innerText = 'Add User';
        // Clear the input fields
        document.getElementById('addUserForm').reset();
        // Set User ID input to editable
        document.getElementById('user_id_input').readOnly = false;
        // Hide the image preview
        document.getElementById('preview').style.display = 'none';
        // Reset password labels
        document.getElementById('passwordLabel').innerText = 'Create Password';
        document.getElementById('confirmPasswordLabel').innerText = 'Confirm Password';
    }

    let originalValues = {}; // Object to store original values

    // Populate the edit form with user data
    function populateEditForm(user) {
        document.getElementById('user_id').value = user.user_id; // Set user ID
        document.getElementById('user_id_input').value = user.user_id; // Set user ID
        document.getElementById('first_name').value = user.first_name;
        document.getElementById('middle_name').value = user.middle_name;
        document.getElementById('last_name').value = user.last_name;
        document.getElementById('patron_type').value = user.patron_type;
        document.getElementById('email').value = user.email;
        document.getElementById('address').value = user.address;
        document.getElementById('status').value = user.status;
        document.getElementById('account_status').value = user.account_status;

        // Set image preview if needed
        const preview = document.getElementById('preview');
        preview.src = user.images ? user.images : '#'; // Use existing image URL if available
        preview.style.display = user.images ? 'block' : 'none'; // Show if image exists

        // Change modal title and button text
        document.getElementById('addUserModalLabel').innerText = 'Update User';
        document.getElementById('submitButton').innerText = 'Update User';

        // Make User ID read-only
        document.getElementById('user_id_input').readOnly = true;

        // Change password fields to New Password and Confirm Password
        document.getElementById('passwordLabel').innerText = 'New Password';
        document.getElementById('confirmPasswordLabel').innerText = 'Confirm Password';

        // Clear the password fields for security
        document.getElementById('password').value = '';
        document.getElementById('confirm_password').value = '';

        // Store original values
        originalValues = {
            user_id: user.user_id,
            first_name: user.first_name,
            middle_name: user.middle_name,
            last_name: user.last_name,
            patron_type: user.patron_type,
            email: user.email,
            address: user.address,
            status: user.status,
            account_status: user.account_status,
            images: user.images
        };

        // Disable the update button initially
        document.getElementById('submitButton').disabled = true;

        // Add event listeners to enable/disable the update button based on changes
        const inputs = document.querySelectorAll('#addUserForm input, #addUserForm select');
        inputs.forEach(input => {
            input.addEventListener('input', checkForChanges);
        });
    }

    // Function to check for changes in input fields
    function checkForChanges() {
        const firstName = document.getElementById('first_name').value;
        const middleName = document.getElementById('middle_name').value;
        const lastName = document.getElementById('last_name').value;
        const patronType = document.getElementById('patron_type').value;
        const email = document.getElementById('email').value;
        const address = document.getElementById('address').value;
        const status = document.getElementById('status').value;
        const accountStatus = document.getElementById('account_status').value;

        // Check if any input field has changed
        const isChanged = (
            firstName !== originalValues.first_name ||
            middleName !== originalValues.middle_name ||
            lastName !== originalValues.last_name ||
            patronType !== originalValues.patron_type ||
            email !== originalValues.email ||
            address !== originalValues.address ||
            status !== originalValues.status ||
            accountStatus !== originalValues.account_status
        );

        // Enable or disable the update button based on changes
        document.getElementById('submitButton').disabled = !isChanged;
    }

    // Add event listener for the form submission
    document.getElementById('addUserForm').addEventListener('submit', function (e) {
        e.preventDefault(); // Prevent default form submission
        const formData = new FormData(this);
        // Send the form data using Fetch API
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: data.message,
                }).then(() => {
                    // Reload the page or update the user table as needed
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message,
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An unexpected error occurred. Please try again later.',
            });
        });
    });

    // Search function
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