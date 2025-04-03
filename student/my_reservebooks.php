<?php
session_start(); // Start the session
include '../component-library/connect.php';
// Database connection
try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $user_name, $user_password);
    // Set PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}
$user_id = $_SESSION['user_id'];
// Fetch student profile data
$stud = $conn->prepare("SELECT * FROM user_info WHERE user_id = ?");
$stud->execute([$user_id]);
$student = $stud->fetch(PDO::FETCH_ASSOC);
$profile_image = $student['images'] ?? '../images/prof.jpg'; // Fallback if no image


// Handle reservation cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelReservation']) && !isset($_POST['action'])) {
    // Add a specific identifier for this function to ensure it doesn't interfere with other POST handlers
    $isReservationCancellation = true;
    
    $book_id = $_POST['cancelReservation'];
    try {
        // Start a transaction
        $conn->beginTransaction();
        // Get the current date and time for the cancel_date
        $cancel_date = date('Y-m-d H:i:s');
        // Update the status of the reserved book to 'canceled' and set the cancel_date
        $stmt = $conn->prepare("UPDATE reserve_books SET status = 'canceled', cancel_date = ? WHERE book_id = ? AND user_id = ? AND status = 'reserved'");
        $stmt->execute([$cancel_date, $book_id, $user_id]);

        // Check if any row was updated
        if ($stmt->rowCount() > 0) {
            // Get the book details to update copies
            $bookDetailsStmt = $conn->prepare("SELECT b.id, b.copies FROM books b WHERE b.id = ?");
            $bookDetailsStmt->execute([$book_id]);
            $bookDetails = $bookDetailsStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($bookDetails) {
                // Increment the copies in the books table
                $updateCopiesStmt = $conn->prepare("UPDATE books SET copies = copies + 1 WHERE id = ?");
                $updateCopiesStmt->execute([$book_id]);

                // Check if the book status needs to be updated to 'available'
                if ($bookDetails['copies'] + 1 > 0) {
                    $updateStatusStmt = $conn->prepare("UPDATE books SET status = 'available' WHERE id = ?");
                    $updateStatusStmt->execute([$book_id]);
                }
            }
            
            // Commit the transaction
            $conn->commit();
            // Successful cancellation
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Reservation canceled successfully.']);
        } else {
            // No matching reservation found
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'No reservation found to cancel.']);
        }
    } catch (PDOException $e) {
        // Rollback the transaction in case of error
        $conn->rollBack();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error canceling reservation: ' . $e->getMessage()]);
    }
    exit();
}

// Fetch reserved books for the student
$reservedBooksQuery = $conn->prepare("
    SELECT rb.*, b.title, b.books_image, b.author, b.publisher, b.copyright, b.category, b.ISBN, b.id as book_id 
    FROM reserve_books rb 
    JOIN books b ON rb.book_id = b.id 
    WHERE rb.user_id = ? AND rb.status = 'reserved'  -- Ensure you're checking for the correct reserved status
");
$reservedBooksQuery->execute([$user_id]);
$reservedBooks = $reservedBooksQuery->fetchAll(PDO::FETCH_ASSOC);

// Get total reserved books count
$totalReservedBooks = count($reservedBooks);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reserved Books</title>
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
</head>

<body class="bg-gray-50">
    <?php include 'side_navbars.php'; ?>
    <div class="container mx-auto px-4 py-6">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4">
                    <h2 class="text-2xl font-bold text-gray-800 mb-2 md:mb-0">My Reserved Books</h2>
                    <div class="text-sm text-gray-600">
                        Student ID: <span class="font-semibold"><?php echo htmlspecialchars($user_id); ?></span>
                    </div>
                </div>
                
                <?php if (empty($reservedBooks)): ?>
                    <div class="text-center py-8">
                        <p class="text-gray-500 text-lg">No reserved books found</p>
                    </div>
                <?php else: ?>
                    <!-- Desktop view (hidden on mobile) -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cover</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Publisher</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Copies</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reserved Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($reservedBooks as $book): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if (!empty($book['books_image'])): ?>
                                                <img src="../uploaded_file/<?php echo htmlspecialchars($book['books_image']); ?>" alt="Book Cover" class="h-24 w-16 object-cover rounded shadow-sm">
                                            <?php else: ?>
                                                <div class="h-24 w-16 bg-gray-200 rounded flex items-center justify-center text-gray-500 text-xs text-center">
                                                    No Cover
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <a href="studbook_detail.php?id=<?php echo urlencode($book['book_id']); ?>" class="hover:text-primary">
                                                    <?php echo htmlspecialchars($book['title']); ?>
                                                </a>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <p>Publish Date: <?php echo htmlspecialchars($book['copyright']); ?></p>
                                                <p>ISBN: <?php echo htmlspecialchars($book['ISBN']); ?></p>
                                                <p>Book ID: <?php echo htmlspecialchars($book['book_id']); ?></p>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($book['author']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($book['publisher']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                <?php echo htmlspecialchars($book['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($book['copies']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($book['reserved_date']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <form class="cancel-form" method="POST" action="">
                                                <input type="hidden" name="cancelReservation" value="<?php echo htmlspecialchars($book['book_id']); ?>">
                                                <button type="button" class="cancel-btn text-red-600 hover:text-red-900 bg-red-100 hover:bg-red-200 px-3 py-1 rounded-md transition duration-150 ease-in-out">
                                                    Cancel
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Mobile view (hidden on desktop) -->
                    <div class="md:hidden space-y-4">
                        <?php foreach ($reservedBooks as $book): ?>
                            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                                <div class="p-4">
                                    <div class="flex items-start space-x-4">
                                        <div class="flex-shrink-0">
                                            <?php if (!empty($book['books_image'])): ?>
                                                <img src="../uploaded_file/<?php echo htmlspecialchars($book['books_image']); ?>" alt="Book Cover" class="h-24 w-16 object-cover rounded shadow-sm">
                                            <?php else: ?>
                                                <div class="h-24 w-16 bg-gray-200 rounded flex items-center justify-center text-gray-500 text-xs text-center">
                                                    No Cover
                                                </div>
                                            <?php endif; ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 mt-2">
                                                    <?php echo htmlspecialchars($book['status']); ?>
                                            </span>
                                        </div>
                                        <div class="flex-1 min-w-0 ">
                                            <h3 class="text-sm font-medium text-[#156295] truncate">
                                                <a href="studbook_detail.php?id=<?php echo urlencode($book['book_id']); ?>" class="hover:text-primary">
                                                    <?php echo htmlspecialchars($book['title']); ?>
                                                </a>
                                            </h3>
                                            <p class="text-xs text-gray-500 mt-2">
                                                Author : <a href="selected_author.php?author=<?php echo urlencode($book['author']); ?>" class="text-blue-600 hover:underline"><?php echo htmlspecialchars($book['author']); ?></a>
                                            </p>
                                            <p class="text-xs text-gray-500 mt-2">
                                            Publisher :  <a href="publisher_browse.php?publisher=<?php echo urlencode($book['publisher']); ?>" class="text-blue-600 hover:underline"><?php echo htmlspecialchars($book['publisher']); ?></a>
                                            </p>
                                            <p class="text-xs text-gray-500 mt-2">
                                                Category : <a href="search_categ.php?category=<?php echo urlencode($book['category']); ?>" class="text-blue-600 hover:underline"><?php echo htmlspecialchars($book['category']); ?></a>
                                            </p>
                                            <div class="flex items-center mt-2">
                                                <span class="text-xs text-gray-500">
                                                    <?php echo $book['copies'] == 1 ? 'Copy' : 'Copies'; ?>: <?php echo htmlspecialchars($book['copies']); ?>
                                                </span>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-2">Reserved: <?php echo htmlspecialchars($book['reserved_date']); ?></p>
                                            <div class="mt-2">
                                                <form class="cancel-form" method="POST" action="">
                                                    <input type="hidden" name="cancelReservation" value="<?php echo htmlspecialchars($book['book_id']); ?>">
                                                    <button type="button" class="cancel-btn text-red-600 hover:text-red-900 bg-red-100 hover:bg-red-200 px-3 py-1 rounded-full text-xs transition duration-150 ease-in-out">
                                                        Cancel Reservation
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include '../student/footer.php'; ?>

    <script>
    document.querySelectorAll('.cancel-btn').forEach(button => {
        button.addEventListener('click', function() {
            const form = this.closest('.cancel-form');
            const bookId = form.querySelector('input[name="cancelReservation"]').value;
            
            // Improved selector to find the book title in both desktop and mobile views
            let bookTitle;
            const parentRow = this.closest('tr');
            if (parentRow) {
                // Desktop view - find the title in the table row
                const titleCell = parentRow.querySelector('td:nth-child(2) a');
                bookTitle = titleCell ? titleCell.textContent.trim() : 'this book';
            } else {
                // Mobile view - find the title in the card
                const titleElement = this.closest('div').querySelector('h3 a');
                bookTitle = titleElement ? titleElement.textContent.trim() : 'this book';
            }

            Swal.fire({
                title: 'Cancel Reservation',
                html: `<p>Are you sure you want to cancel your reservation for:</p><p class="font-bold mt-2">${bookTitle}</p>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes',
                cancelButtonText: 'No',
                customClass: {
                    popup: 'rounded-lg',
                    title: 'text-xl font-bold',
                    htmlContainer: 'text-gray-700',
                    confirmButton: 'px-4 py-2 rounded-md',
                    cancelButton: 'px-4 py-2 rounded-md'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    Swal.fire({
                        title: 'Processing...',
                        html: 'Please wait while we cancel your reservation',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Send AJAX request to cancel reservation
                    fetch('', {
                        method: 'POST',
                        body: new URLSearchParams(new FormData(form))
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Reservation Canceled',
                                html: `<p class="text-green-600">${data.message}</p><p class="mt-2"></p>`,
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#3085d6',
                                customClass: {
                                    popup: 'rounded-lg',
                                    title: 'text-xl font-bold',
                                    htmlContainer: 'text-gray-700',
                                    confirmButton: 'px-4 py-2 rounded-md'
                                }
                            }).then(() => {
                                // Reload the page to reflect changes
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Cancellation Failed',
                                html: `<p class="text-red-600">${data.message}</p><p class="mt-2">Please try again or contact the library staff for assistance.</p>`,
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#3085d6',
                                customClass: {
                                    popup: 'rounded-lg',
                                    title: 'text-xl font-bold',
                                    htmlContainer: 'text-gray-700',
                                    confirmButton: 'px-4 py-2 rounded-md'
                                }
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Network Error',
                            html: `<p class="text-red-600">Unable to communicate with the server.</p><p class="mt-2">Please check your internet connection and try again.</p>`,
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#3085d6',
                            customClass: {
                                popup: 'rounded-lg',
                                title: 'text-xl font-bold',
                                htmlContainer: 'text-gray-700',
                                confirmButton: 'px-4 py-2 rounded-md'
                            }
                        });
                    });
                }
            });
        });
    });
    </script>
</body>
</html>