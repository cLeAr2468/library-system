<?php
ob_start(); // Start output buffering
// Database connection
include '../component-library/connect.php';
include '../student/side_navbars.php';
// Check if the student is logged in
$user_id = $_SESSION['user_id'] ?? null;
if ($user_id) {
    // Fetch student profile data from the database
    $stud = $conn->prepare("SELECT * FROM user_info WHERE user_id = ?");
    $stud->execute([$user_id]);
    $student = $stud->fetch(PDO::FETCH_ASSOC);
    // Fetch count of reserved, borrowed, and overdue books
    $reservedBooksCount = fetchBookCount($conn, $user_id, 'reserved');
    $borrowedBooksCount = fetchBookCount($conn, $user_id, 'borrowed');
    $overdueBooksCount = fetchBookCount($conn, $user_id, 'overdue');
    // Fallback if no image
    $profile_image = $student['images'] ?? '../images/prof.jpg';
} else {
    // Redirect to login if not logged in
    header('Location: ../index.php');
    exit();
}
// Handle image upload and update
$message = '';
$message_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    $targetDir = "../uploaded_file/";
    $fileName = uniqid() . '-' . basename($_FILES["profile_image"]["name"]); // Unique file name
    $targetFilePath = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
    // Allow certain file formats
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
    if (in_array($fileType, $allowedTypes) && $_FILES["profile_image"]["size"] <= 5000000) { // Limit file size to 5MB
        // Upload file to server
        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $targetFilePath)) {
            // Update the database with the new image path
            try {
                $update = $conn->prepare("UPDATE user_info SET images = ? WHERE user_id = ?");
                $update->execute([$fileName, $user_id]);
                
                // Update session profile image
                $_SESSION['profile_image'] = $fileName;
                $message = "Profile picture updated successfully!";
                $message_type = "success";
            } catch (PDOException $e) {
                $message = "Failed to update profile picture: " . $e->getMessage();
                $message_type = "error";
            }
        } else {
            $message = "Sorry, there was an error uploading your file.";
            $message_type = "error";
        }
    } else {
        $message = "Sorry, only JPG, JPEG, PNG, and GIF files are allowed, and the file size must be less than 5MB.";
        $message_type = "error";
    }
    // Redirect to avoid resubmission on refresh
    header("Location: profile.php?user_id=$user_id&message=" . urlencode($message) . "&message_type=" . urlencode($message_type));
    exit();
}
function fetchBookCount($conn, $user_id, $status) {
    $countQuery = $conn->prepare("SELECT COUNT(*) as total FROM reserve_books WHERE user_id = ? AND status = ?");
    $countQuery->execute([$user_id, $status]);
    return $countQuery->fetch(PDO::FETCH_ASSOC)['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NwSSU : <?php echo htmlspecialchars($student['user_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800">
    <!-- Main Content Container -->
    <div class="container mx-auto px-4 py-8 flex justify-center items-center min-h-[50vh]">
        <!-- Profile Card -->
        <div class="w-full max-w-4xl bg-white rounded-lg shadow-md p-4 sm:p-6 md:p-8">
            <!-- Alert Messages -->
            <?php if (isset($_SESSION['message'])): ?>
                <div class="<?php echo $_SESSION['message_type'] === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?> p-3 sm:p-4 mb-4 sm:mb-6 rounded-md text-center text-sm sm:text-base">
                    <?php echo $_SESSION['message'];
                    unset($_SESSION['message'], $_SESSION['message_type']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Profile Content -->
            <div class="flex flex-col md:flex-row gap-6">
                <!-- Profile Image Section (Order changed for mobile) -->
                <div class="w-full md:w-1/3 flex flex-col items-center order-first md:order-last">
                    <div class="w-full flex justify-center">
                        <img 
                            src="../uploaded_file/<?php echo htmlspecialchars($profile_image); ?>" 
                            alt="Student Profile" 
                            class="w-32 h-40 sm:w-40 sm:h-52 object-cover border border-gray-300 rounded-2xl shadow-lg cursor-pointer transition-transform hover:scale-105" 
                            id="studentImage"
                        >
                    </div>
                    <form method="POST" enctype="multipart/form-data" class="w-full flex flex-col items-center">
                        <input type="file" name="profile_image" id="fileInput" class="hidden" accept="image/*">
                        <button 
                            type="submit" 
                            id="changePhotoBtn" 
                            class="mt-3 sm:mt-4 bg-blue-600 hover:bg-blue-700 text-white py-1.5 sm:py-2 px-3 sm:px-4 rounded-full hidden text-sm sm:text-base transition-colors duration-200 w-32 sm:w-40"
                        >
                            Change Photo
                        </button>
                    </form>
                    <p class="text-xs text-gray-500 mt-2 text-center">Click on image to change</p>
                </div>

                <!-- Student Information Section -->
                <div class="w-full md:w-2/3 order-last md:order-first">
                    <h2 class="text-xl sm:text-2xl font-bold mb-3 sm:mb-4 text-center md:text-left">
                        <?php echo htmlspecialchars($student['first_name'] . ' ' . ($student['middle_name'] ?? '') . ' ' . $student['last_name']); ?>
                    </h2>
                    
                    <div class="overflow-x-auto -mx-4 sm:mx-0">
                        <table class="w-full border-collapse text-sm sm:text-base">
                            <tbody>
                                <tr class="border-b hover:bg-gray-50">
                                    <th class="py-2 sm:py-3 px-4 sm:px-2 text-left font-medium">Student ID</th>
                                    <td class="py-2 sm:py-3 px-4 sm:px-2"><?php echo htmlspecialchars($student['user_id']); ?></td>
                                </tr>
                                <tr class="border-b hover:bg-gray-50">
                                    <th class="py-2 sm:py-3 px-4 sm:px-2 text-left font-medium">Patron Type</th>
                                    <td class="py-2 sm:py-3 px-4 sm:px-2"><?php echo htmlspecialchars($student['patron_type']); ?></td>
                                </tr>
                                <tr class="border-b hover:bg-gray-50">
                                    <th class="py-2 sm:py-3 px-4 sm:px-2 text-left font-medium">Email</th>
                                    <td class="py-2 sm:py-3 px-4 sm:px-2 break-all"><?php echo htmlspecialchars($student['email']); ?></td>
                                </tr>
                                <tr class="border-b hover:bg-gray-50">
                                    <th class="py-2 sm:py-3 px-4 sm:px-2 text-left font-medium">Address</th>
                                    <td class="py-2 sm:py-3 px-4 sm:px-2"><?php echo htmlspecialchars($student['address']); ?></td>
                                </tr>
                                <tr class="border-b hover:bg-gray-50">
                                    <th class="py-2 sm:py-3 px-4 sm:px-2 text-left font-medium">Account Status</th>
                                    <td class="py-2 sm:py-3 px-4 sm:px-2">
                                        <span class="<?php echo $student['account_status'] === 'Active' ? 'text-green-600' : 'text-red-600'; ?> font-medium">
                                            <?php echo htmlspecialchars($student['account_status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr class="border-b hover:bg-gray-50">
                                    <th class="py-2 sm:py-3 px-4 sm:px-2 text-left font-medium">Reserved Books</th>
                                    <td class="py-2 sm:py-3 px-4 sm:px-2">
                                        <span class="font-medium"><?php echo htmlspecialchars($reservedBooksCount); ?></span>
                                        [ <a href="my_reservebooks.php?student_id=<?php echo urlencode($user_id); ?>" class="text-blue-600 hover:text-blue-800 underline">Browse</a> ]
                                    </td>
                                </tr>
                                <tr class="border-b hover:bg-gray-50">
                                    <th class="py-2 sm:py-3 px-4 sm:px-2 text-left font-medium">Borrowed Books</th>
                                    <td class="py-2 sm:py-3 px-4 sm:px-2">
                                        <span class="font-medium"><?php echo htmlspecialchars($borrowedBooksCount); ?></span>
                                        [ <a href="my_borrowed.php" class="text-blue-600 hover:text-blue-800 underline">Browse</a> ]
                                    </td>
                                </tr>
                                <tr class="border-b hover:bg-gray-50">
                                    <th class="py-2 sm:py-3 px-4 sm:px-2 text-left font-medium">Overdue</th>
                                    <td class="py-2 sm:py-3 px-4 sm:px-2">
                                        <span class="font-medium <?php echo $overdueBooksCount > 0 ? 'text-red-600' : ''; ?>"><?php echo htmlspecialchars($overdueBooksCount); ?></span>
                                        [ <a href="overdue.php?student_id=<?php echo urlencode($user_id); ?>" class="text-blue-600 hover:text-blue-800 underline">Browse</a> ]
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-50">
                                    <th class="py-2 sm:py-3 px-4 sm:px-2 text-left font-medium">Transaction History</th>
                                    <td class="py-2 sm:py-3 px-4 sm:px-2">
                                        <a href="history_rec.php?student_id=<?php echo urlencode($user_id); ?>" class="text-blue-600 hover:text-blue-800 underline">View History</a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="py-4 mt-4 sm:mt-8 bg-white shadow-inner">
        <div class="container mx-auto text-center px-4">
            <span class="text-gray-600 text-sm sm:text-base">© 2024 NwSSU Library. All rights reserved.</span>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    // Image upload handling
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

    // Form submission handling
    document.getElementById('saveChanges') && document.getElementById('saveChanges').addEventListener('click', function() {
        // Get form data
        let formData = new FormData(document.getElementById('updateForm'));
        // Send AJAX request
        fetch('profile.php', {
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

    // Add responsive menu toggle if needed
    const toggleMenu = document.getElementById('toggleMenu');
    const mobileMenu = document.getElementById('mobileMenu');
    
    if (toggleMenu && mobileMenu) {
        toggleMenu.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
        });
    }
    </script>
</body>
</html>