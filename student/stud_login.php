<?php
session_start();
include "../component-library/connect.php"; // Include database connection

// Check if the student is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect to dashboard if already logged in
    header('location: ./home.php');
    exit();
}

// Initialize an empty array to store messages
$alert_messages = [];
if (isset($_POST['submit'])) {
    // Extract login credentials
    $email = htmlspecialchars($_POST['email']);
    $pass = htmlspecialchars($_POST['password']);
    
    // Prepare and execute query to select student by email
    $select_student = $conn->prepare("SELECT * FROM user_info WHERE email = ?");
    $select_student->execute([$email]);
    $row = $select_student->fetch(PDO::FETCH_ASSOC);
    
    // Check if student exists and password matches
    if ($select_student->rowCount() > 0) {
        if (password_verify($pass, $row['password'])) {
            if ($row['status'] == 'approved') {
                // Set student session variables
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['first_name'] = $row['first_name'];
                $_SESSION['middle_name'] = $row['middle_name'];
                $_SESSION['last_name'] = $row['last_name'];
                $_SESSION['patron_type'] = $row['patron_type'];
                
                // Redirect to dashboard
                header('location: ./home.php');
                exit();
            } else {
                $alert_messages[] = ['type' => 'warning', 'title' => 'Account Pending', 'text' => 'Your account is pending approval.'];
            }
        } else {
            $alert_messages[] = ['type' => 'error', 'title' => 'Login Failed', 'text' => 'Incorrect email or password!'];
        }
    } else {
        $alert_messages[] = ['type' => 'error', 'title' => 'Login Failed', 'text' => 'Incorrect email or password!'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System - Login</title>
    <link rel="icon" type="image/png" href="../library-system/images/logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        teal: {
                            50: '#f0fdfa',
                            100: '#ccfbf1',
                            200: '#99f6e4',
                            300: '#5eead4',
                            400: '#2dd4bf',
                            500: '#14b8a6',
                            600: '#0d9488',
                            700: '#0f766e',
                            800: '#115e59',
                            900: '#134e4a',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Include SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Include Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="grid grid-cols-1 md:grid-cols-2 h-screen">
        <!-- Left section: Login Form -->
        <div class="flex flex-col justify-center items-center px-4 md:px-8 py-10 order-2 md:order-1 bg-white">
            <div class="w-full max-w-md bg-white rounded-xl p-6 md:p-8 shadow-lg">
                <div class="flex flex-col items-center mb-6">
                    <h2 class="font-bold text-2xl md:text-3xl text-center">
                        Login <span class="text-teal-600">Account</span>
                    </h2>
                    <p class="text-gray-500 mt-2 text-center">
                        Enter your credentials to access your account
                    </p>
                </div>
                
                <form method="POST" class="space-y-4">
                    <div class="space-y-2">
                        <label for="email" class="text-sm font-medium text-gray-700">
                            Email
                        </label>
                        <div class="relative">
                            <input
                                id="email"
                                name="email"
                                type="email"
                                placeholder="Enter your email"
                                class="pl-4 pr-4 py-2 h-11 bg-gray-50 border border-gray-200 rounded-lg w-full focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none"
                                required
                            />
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <label for="password" class="text-sm font-medium text-gray-700">
                                Password
                            </label>
                            <a 
                                href="../library-system/auth/recovery-password.php" 
                                class="text-sm text-teal-600 hover:text-teal-800 hover:underline"
                            >
                                Forgot password?
                            </a>
                        </div>
                        <div class="relative">
                            <input
                                id="password"
                                name="password"
                                type="password"
                                placeholder="Enter your password"
                                class="pl-4 pr-10 py-2 h-11 bg-gray-50 border border-gray-200 rounded-lg w-full focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none"
                                required
                            />
                            <button
                                type="button"
                                id="togglePassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
                            >
                                <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="hidden">
                                    <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg id="eyeOffIcon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path>
                                    <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path>
                                    <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path>
                                    <line x1="2" x2="22" y1="2" y2="22"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <button
                        type="submit"
                        name="submit"
                        class="w-full h-11 mt-6 bg-teal-600 hover:bg-teal-700 text-white font-medium rounded-lg text-base transition-colors duration-200 ease-in-out"
                    >
                        Log In
                    </button>
                </form>
                
                <div class="mt-6 text-center">
                    <p class="text-gray-600">
                        Don't have an account?
                        <a href="./register.php" class="text-teal-600 hover:text-teal-800 hover:underline font-medium ml-1">
                            Create account
                        </a>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Right section: Welcome Message -->
        <div class="flex flex-col items-center justify-center bg-teal-600 text-white p-8 order-1 md:order-2">
            <div class="max-w-md text-center">
                <div class="mb-6 flex justify-center">
                    <div class="bg-white p-4 rounded-full">
                        <!-- Book icon or logo -->
                        <?php if (file_exists("../library-system/images/logo.png")): ?>
                            <img src="../library-system/images/logo.png" alt="Library Logo" class="h-12 w-12">
                        <?php else: ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-teal-600">
                                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                            </svg>
                        <?php endif; ?>
                    </div>
                </div>
                <h1 class="text-3xl md:text-4xl font-bold mb-4">Welcome!</h1>
                <h2 class="text-xl md:text-2xl font-semibold mb-6">
                    Library Management System
                </h2>
                <p class="text-teal-100 max-w-sm mx-auto">
                    Access your library account to borrow books, check due dates, and explore our collection.
                </p>
            </div>
        </div>
    </div>

    <!-- JavaScript for password visibility toggle -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.getElementById('togglePassword');
            const eyeIcon = document.getElementById('eyeIcon');
            const eyeOffIcon = document.getElementById('eyeOffIcon');
            
            // Show the eye-off icon by default
            eyeOffIcon.classList.remove('hidden');
            eyeIcon.classList.add('hidden');
            
            toggleButton.addEventListener('click', function() {
                // Toggle password visibility
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    eyeIcon.classList.remove('hidden');
                    eyeOffIcon.classList.add('hidden');
                } else {
                    passwordInput.type = 'password';
                    eyeIcon.classList.add('hidden');
                    eyeOffIcon.classList.remove('hidden');
                }
            });
        });
    </script>

    <!-- JavaScript for SweetAlert -->
    <script>
        // Check if there are alert messages from PHP
        <?php if (!empty($alert_messages)): ?>
            <?php foreach ($alert_messages as $message): ?>
                Swal.fire({
                    icon: '<?php echo $message['type']; ?>', // 'success', 'error', 'warning', etc.
                    title: '<?php echo $message['title']; ?>',
                    text: '<?php echo $message['text']; ?>',
                });
            <?php endforeach; ?>
        <?php endif; ?>
    </script>
</body>
</html>