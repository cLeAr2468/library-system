<?php 
// Include the database connection
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Start the session only if it's not already started
}
include '../component-library/connect.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header('Location: ../index.php'); // Adjust this path as necessary
    exit();
}

$user_id = $_SESSION['user_id'];
$user_first_name = $_SESSION['first_name'];
$profile_image = '../images/prof.jpg'; // Default profile image

// Fetch student profile data
$stud = $conn->prepare("SELECT first_name, middle_name, last_name, patron_type, email, address, images FROM user_info WHERE user_id = ?");
$stud->execute([$user_id]);
$student = $stud->fetch(PDO::FETCH_ASSOC);

if ($student) {
    $profile_image = $student['images'] ?? $profile_image; // Fallback if no image
}

// Handle profile update via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if this is a profile update request
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        // This is a profile update request, not a book reservation
        $first_name = trim($_POST['first_name']);
        $middle_name = trim($_POST['middle_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $address = trim($_POST['address']);
        $current_password = $_POST['current_password'] ?? null;
        $new_password = $_POST['new_password'] ?? null;
        $confirm_password = $_POST['confirm_password'] ?? null;
        
        try {
            // Validate inputs
            if (empty($first_name) || empty($last_name) || empty($email) || empty($address)) {
                throw new Exception("All fields are required.");
            }
            
            // Variable to track if password update is needed
            $updatePassword = false;
            
            // Check if new password is provided
            if (!empty($new_password)) {
                if (empty($current_password)) {
                    throw new Exception("Current password is required when updating the password.");
                }
                
                // Check if the current password is correct
                $stmt = $conn->prepare("SELECT password FROM user_info WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user && password_verify($current_password, $user['password'])) {
                    $updatePassword = true;
                } else {
                    throw new Exception("Current password is incorrect.");
                }
            }
            
            // Prepare the update query
            $updateQuery = "UPDATE user_info SET first_name = ?, middle_name = ?, last_name = ?, email = ?, address = ?";
            $params = [$first_name, $middle_name, $last_name, $email, $address];
            
            // Update password if new password is provided and matches confirmation
            if ($updatePassword && $new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $updateQuery .= ", password = ?";
                $params[] = $hashed_password;
            } elseif ($updatePassword) {
                throw new Exception("New password and confirm password do not match.");
            }
            
            $updateQuery .= " WHERE user_id = ?";
            $params[] = $user_id;
            
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->execute($params);
            
            // Update session variables
            $_SESSION['first_name'] = $first_name;
            
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }
    // If it's not a profile update, it might be a book reservation or other POST request
    // We'll ignore it here since it's handled elsewhere
}

// Logout Logic
if (isset($_POST['logout'])) {
    session_unset();
    header('Location: ../index.html');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System</title>
    <link rel="icon" type="image/png" href="../images/logo.png">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#00a000',
                        secondary: '#333333',
                    }
                }
            }
        }
    </script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-secondary text-white">
        <div class="container mx-auto px-[5%] py-3 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <img src="../images/logo.png" alt="NwSSU Logo" class="h-10 w-10 rounded-full">
                <span class="text-xl font-bold">Nwssu</span>
            </div>
            <h1 class="text-2xl font-bold hidden lg:block">LIBRARY MANAGEMENT SYSTEM</h1>
            <div class="relative">
                <button id="account-menu-button" class="flex items-center space-x-2 bg-gray-700 rounded-lg px-3 py-2 hover:bg-gray-600 transition">
                    <img src="../uploaded_file/<?php echo htmlspecialchars($profile_image); ?>" alt="User" class="h-8 w-8 rounded-full bg-white">
                    <span class="hidden md:inline">My Account</span>
                    <i class="fas fa-chevron-down text-xs"></i>
                </button>
                <div id="account-dropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-30 hidden">
                    <a href="profile.php?user_id=<?php echo htmlspecialchars($user_id); ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" id="settingsBtn">Settings</a>
                    <div class="border-t border-gray-100"></div>
                    <form method="POST" action="">
                        <button type="submit" name="logout" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Sign out</button>
                    </form>
                </div>
            </div>
        </div>
    </header>
    <!-- Navigation -->
    <nav class="bg-primary text-white font-bold">
        <div class="container mx-auto justify-content-center">
            <div class="flex flex-wrap lg:justify-center lg:items-center">
                <!-- Mobile Menu Button -->
                <button class="lg:hidden py-3 ml-[8%] text-white text-lg" id="mobile-menu-button">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="hidden md:hidden lg:block text-black lg:bg-primary lg:text-white md:flex w-full md:w-auto" id="mobile-menu">
                    <ul class="flex flex-col md:flex-row">
                        <li><a href="../student/home.php" class="block py-3 px-4 hover:bg-green-600 transition">Home</a></li>
                        <li><a href="../student/studbooks_display.php" class="block py-3 px-4 hover:bg-green-600 transition">Catalog</a></li>
                        <li><a href="../student/topcollect.php" class="block py-3 px-4 hover:bg-green-600 transition">Top Collection</a></li>
                        <li><a href="../student/newcollect.php" class="block py-3 px-4 hover:bg-green-600 transition">New Collections</a></li>
                        <li class="relative group">
                            <a href="#" class="block py-3 px-4 hover:bg-green-600 transition flex items-center">
                                About <i class="fas fa-chevron-down ml-1 text-xs"></i>
                            </a>
                            <ul class="absolute left-0 mt-0 w-48 bg-white shadow-lg py-1 z-10 hidden group-hover:block">
                                <li><a href="../student/Mission_VIsion.php" class="block px-4 py-2 hover:bg-blue-500 hover:text-white transition text-black">Mission & Vision</a></li>
                            </ul>
                        </li>
                        <li class="relative group">
                            <a href="#" class="block py-3 px-4 hover:bg-green-600 transition flex items-center">
                                Online Services <i class="fas fa-chevron-down ml-1 text-xs"></i>
                            </a>
                            <ul class="absolute left-0 mt-0 w-48 text-black shadow-lg py-1 z-10 hidden group-hover:block">
                                <li><a href="https://www.proquest.com/" target="_blank" class="block px-4 py-2 hover:bg-blue-500 hover:text-white transition">Proquest Central Database</a></li>
                                <li><a href="https://ejournals.ph/" target="_blank" class="block px-4 py-2 hover:bg-blue-500 hover:text-white transition">Philippine E-Journals</a></li>
                                <li><a href="https://starbooks.ph/" target="_blank" class="block px-4 py-2 hover:bg-blue-500 hover:text-white transition">Dost Starbooks</a></li>
                            </ul>
                        </li>
                        <li class="relative group">
                            <a href="#" class="block py-3 px-4 hover:bg-green-600 transition flex items-center">
                                Ask a Librarian? <i class="fas fa-chevron-down ml-1 text-xs"></i>
                            </a>
                            <ul class="absolute left-0 mt-0 w-48  bg-white text-black shadow-lg py-1 z-10 hidden group-hover:block">
                                <li><a href="https://mail.google.com/mail/?view=cm&fs=1&to=nwssulibrarysjcampus@gmail.com" target="_blank" class="block px-4 py-2 hover:bg-blue-500 hover:text-white transition">
                                    <i class="fas fa-envelope mr-2"></i> Email Account
                                </a></li>
                                <li><a href="https://www.facebook.com/NwSSU.sjclibrary?mibextid=LQQJ4d" target="_blank" class="block px-4 py-2 hover:bg-blue-500 hover:text-white transition">
                                    <i class="fab fa-facebook-messenger mr-2"></i> Messenger
                                </a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- Backdrop for the side sheet -->
        <div id="backdrop" class="fixed inset-0 bg-black bg-opacity-50 hidden z-40"></div>
        <!-- Mobile Slide Menu -->
        <div id="mobile-slide-menu" class="fixed top-0 left-0 h-full w-64 bg-white text-black transform -translate-x-full transition-transform ease-in-out duration-300 z-50">
            <button class="absolute top-4 right-4 text-black" id="close-mobile-menu">
                <i class="fas fa-times"></i>
            </button>
            <ul class="mt-16">
                <li><a href="../student/home.php" class="block py-3 px-4 hover:bg-blue-500 hover:text-white transition">Home</a></li>
                <li><a href="../student/studbooks_display.php" class="block py-3 px-4 hover:bg-blue-500 hover:text-white transition">Catalog</a></li>
                <li><a href="../student/topcollect.php" class="block py-3 px-4 hover:bg-blue-500 hover:text-white transition">Top Collection</a></li>
                <li><a href="../student/newcollect.php" class="block py-3 px-4 hover:bg-blue-500 hover:text-white transition">New Collections</a></li>
                <li class="relative group">
                            <a href="#" class="block py-3 px-4 hover:bg-blue-500 hover:text-white transition flex items-center">
                                About <i class="fas fa-chevron-down ml-1 text-xs"></i>
                            </a>
                            <ul class="absolute left-0 mt-0 w-48 bg-white shadow-lg py-1 z-10 hidden group-hover:block">
                                <li><a href="../student/Mission_VIsion.php" class="block px-4 py-2 hover:bg-blue-500 hover:text-white transition text-black">Mission & Vision</a></li>
                            </ul>
                        </li>
                        <li class="relative group">
                            <a href="#" class="block py-3 px-4 hover:bg-blue-500 hover:text-white transition flex items-center">
                                Online Services <i class="fas fa-chevron-down ml-1 text-xs"></i>
                            </a>
                            <ul class="absolute left-0 mt-0 w-48 bg-white text-black shadow-lg py-1 z-10 hidden group-hover:block">
                                <li><a href="https://www.proquest.com/" target="_blank" class="block px-4 py-2 hover:bg-blue-500 hover:text-white transition">Proquest Central Database</a></li>
                                <li><a href="https://ejournals.ph/" target="_blank" class="block px-4 py-2 hover:bg-blue-500 hover:text-white transition">Philippine E-Journals</a></li>
                                <li><a href="https://starbooks.ph/" target="_blank" class="block px-4 py-2 hover:bg-blue-500 hover:text-white transition">Dost Starbooks</a></li>
                            </ul>
                        </li>
                        <li class="relative group">
                            <a href="#" class="block py-3 px-4 hover:bg-blue-500 hover:text-white transition flex items-center">
                                Ask a Librarian? <i class="fas fa-chevron-down ml-1 text-xs"></i>
                            </a>
                            <ul class="absolute left-0 mt-0 w-48  bg-white text-black shadow-lg py-1 z-10 hidden group-hover:block">
                                <li><a href="https://mail.google.com/mail/?view=cm&fs=1&to=nwssulibrarysjcampus@gmail.com" target="_blank" class="block px-4 py-2 hover:bg-blue-500 hover:text-white transition">
                                    <i class="fas fa-envelope mr-2"></i> Email Account
                                </a></li>
                                <li><a href="https://www.facebook.com/NwSSU.sjclibrary?mibextid=LQQJ4d" target="_blank" class="block px-4 py-2 hover:bg-blue-500 hover:text-white transition">
                                    <i class="fab fa-facebook-messenger mr-2"></i> Messenger
                                </a></li>
                            </ul>
                        </li>
            </ul>
        </div>
    </nav>
    <!-- Modal for Settings using Tailwind CSS -->
    <div class="fixed inset-0 inset-0 bg-black bg-opacity-50 z-50 overflow-y-auto hidden" id="settingsModal">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
                <div class="flex justify-between items-center border-b p-4">
                    <h5 class="text-xl font-bold">Settings</h5>
                    <button class="text-gray-500 hover:text-gray-700" id="closeSettingsBtn">&times;</button>
                </div>
                <div class="p-4">
                    <form id="profileForm" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="mb-4">
                            <label for="userId" class="block text-sm font-medium">User ID</label>
                            <input type="text" class="w-full p-2 border rounded-lg bg-gray-100" id="userId" value="<?php echo htmlspecialchars($user_id); ?>" readonly>
                        </div>
                        <div class="mb-4">
                            <label for="first_name" class="block text-sm font-medium">First Name</label>
                            <input type="text" class="w-full p-2 border rounded-lg" id="first_name" name="first_name" value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                        </div>
                        <div class="mb-4">
                            <label for="middle_name" class="block text-sm font-medium">Middle Name</label>
                            <input type="text" class="w-full p-2 border rounded-lg" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($student['middle_name']); ?>">
                        </div>
                        <div class="mb-4">
                            <label for="last_name" class="block text-sm font-medium">Last Name</label>
                            <input type="text" class="w-full p-2 border rounded-lg" id="last_name" name="last_name" value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                        </div>
                        <div class="mb-4">
                            <label for="patron_type" class="block text-sm font-medium">Patron Type</label>
                            <input type="text" class="w-full p-2 border rounded-lg bg-gray-100" id="patron_type" name="patron_type" value="<?php echo htmlspecialchars($student['patron_type']); ?>" readonly>
                        </div>
                        <div class="mb-4">
                            <label for="email" class="block text-sm font-medium">Email</label>
                            <input type="email" class="w-full p-2 border rounded-lg" id="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                        </div>
                        <div class="mb-4">
                            <label for="address" class="block text-sm font-medium">Address</label>
                            <input type="text" class="w-full p-2 border rounded-lg" id="address" name="address" value="<?php echo htmlspecialchars($student['address']); ?>" required>
                        </div>
                        <div class="mb-4">
                            <label for="current_password" class="block text-sm font-medium">Current Password</label>
                            <input type="password" class="w-full p-2 border rounded-lg" id="current_password" name="current_password">
                        </div>
                        <div class="mb-4">
                            <label for="new_password" class="block text-sm font-medium">New Password</label>
                            <input type="password" class="w-full p-2 border rounded-lg" id="new_password" name="new_password">
                        </div>
                        <div class="mb-4">
                            <label for="confirm_password" class="block text-sm font-medium">Confirm Password</label>
                            <input type="password" class="w-full p-2 border rounded-lg" id="confirm_password" name="confirm_password">
                        </div>
                        <input type="hidden" name="action" value="update_profile">
                    </form>
                </div>
                <div class="flex justify-end border-t p-4">
                    <button class="bg-gray-500 text-white px-4 py-2 rounded-lg mr-2" id="closeSettingsBtn">Close</button>
                    <button class="bg-blue-500 text-white px-4 py-2 rounded-lg" id="saveChanges">Update</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Account dropdown toggle
        const accountButton = document.getElementById('account-menu-button');
        const accountDropdown = document.getElementById('account-dropdown');
        accountButton.addEventListener('click', function() {
            accountDropdown.classList.toggle('hidden');
        });
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!accountButton.contains(event.target) && !accountDropdown.contains(event.target)) {
                accountDropdown.classList.add('hidden');
            }
        });
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileSlideMenu = document.getElementById('mobile-slide-menu');
        const closeMobileMenu = document.getElementById('close-mobile-menu');
        const backdrop = document.getElementById('backdrop');
        mobileMenuButton.addEventListener('click', () => {
            mobileSlideMenu.classList.remove('-translate-x-full'); // Show the menu
            backdrop.classList.remove('hidden'); // Show the backdrop
        });
        closeMobileMenu.addEventListener('click', () => {
            mobileSlideMenu.classList.add('-translate-x-full'); // Hide the menu
            backdrop.classList.add('hidden'); // Hide the backdrop
        });
        // Close the menu and backdrop when clicking on the backdrop
        backdrop.addEventListener('click', () => {
            mobileSlideMenu.classList.add('-translate-x-full'); // Hide the menu
            backdrop.classList.add('hidden'); // Hide the backdrop
        });
        // Settings Modal
        const settingsBtn = document.getElementById('settingsBtn');
        const settingsModal = document.getElementById('settingsModal');
        const closeSettingsBtn = document.getElementById('closeSettingsBtn');
        const saveChangesBtn = document.getElementById('saveChanges');

        settingsBtn.addEventListener('click', function() {
            settingsModal.classList.remove('hidden');
        });

        closeSettingsBtn.addEventListener('click', function() {
            settingsModal.classList.add('hidden');
        });

        // Close modal when clicking outside
        settingsModal.addEventListener('click', function(e) {
            if (e.target === settingsModal) {
                settingsModal.classList.add('hidden');
            }
        });

        // Handle profile form submission
        saveChangesBtn.addEventListener('click', function() {
            const profileForm = document.getElementById('profileForm');
            const formData = new FormData(profileForm);
            
            fetch('side_navbars.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#3085d6'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: data.message,
                        icon: 'error',
                        confirmButtonColor: '#3085d6'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while updating your profile.',
                    icon: 'error',
                    confirmButtonColor: '#3085d6'
                });
            });
        });
    </script>
</body>
</html>