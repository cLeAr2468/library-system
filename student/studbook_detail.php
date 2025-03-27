<?php
include '../component-library/connect.php';
include '../student/side_navbars.php';
try {
    // Fetch the user_id from the user_info table
    $user_id = null; // Initialize user_id
    if (isset($_SESSION['user_id'])) {
        $session_user_id = $_SESSION['user_id']; // Get user_id from session
        // Fetch user_id from user_info table
        $userInfoSql = "SELECT id FROM user_info WHERE id = :user_id";
        $userInfoStmt = $conn->prepare($userInfoSql);
        $userInfoStmt->execute([':user_id' => $session_user_id]);
        $user = $userInfoStmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $user_id = $user['id']; // Assign user_id
        }
    }
    // Check if the request method is POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Check if account status is available (assuming you have a way to check this)
        if (isset($accountStatus) && $accountStatus === 'inactive') {
            echo json_encode(['success' => false, 'message' => 'Your account is inactive! Book reservation unavailable.']);
            exit();
        }
        $book_id = $_POST['id'] ?? ''; // Get book_id from POST request
        if ($user_id) {
            // Check if the book is already reserved by the user
            $checkReservationSql = "SELECT * FROM reserve_books WHERE user_id = :user_id AND book_id = :book_id AND status = 'reserved'";
            $checkReservationStmt = $conn->prepare($checkReservationSql);
            $checkReservationStmt->execute([':user_id' => $user_id, ':book_id' => $book_id]);
            $existingReservation = $checkReservationStmt->fetch(PDO::FETCH_ASSOC);
            if ($existingReservation) {
                echo json_encode(['success' => false, 'message' => 'This book is already reserved in your account.']);
                exit();
            }
            // Check if the book is available
            $checkSql = "SELECT copies, status FROM books WHERE id = :book_id";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->execute([':book_id' => $book_id]);
            $book = $checkStmt->fetch(PDO::FETCH_ASSOC);
            if ($book) {
                if ($book['copies'] > 0 && $book['status'] === 'available') {
                    // Decrement the copies in the books table
                    $updateSql = "UPDATE books SET copies = copies - 1 WHERE id = :book_id";
                    $updateStmt = $conn->prepare($updateSql);
                    $updateStmt->execute([':book_id' => $book_id]);
                    // Insert reservation into the reserve_books table
                    $sql = "INSERT INTO reserve_books (user_id, book_id, reserved_date, status) 
                            VALUES (:user_id, :book_id, NOW(), 'reserved')";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([
                        ':user_id' => $user_id,
                        ':book_id' => $book_id
                    ]);
                    echo json_encode(['success' => true, 'message' => 'Reservation Successful!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'This book is not available.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Book not found.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'User information not available.']);
        }
        exit();
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit();
}
// Fetch book details based on the id
$book_id = $_GET['id'] ?? null; // Get book_id from GET request
$book = null;
$relatedBooks = [];
if ($book_id) {
    try {
        // Fetch the main book details
        $details = $conn->prepare("SELECT * FROM books WHERE id = :book_id");
        $details->execute([':book_id' => $book_id]);
        $book = $details->fetch(PDO::FETCH_ASSOC);
        // Fetch related books
        if ($book) {
            $booksRelated = $conn->prepare("SELECT * FROM books WHERE category = :category AND id != :book_id LIMIT 4");
            $booksRelated->execute([':category' => $book['category'], ':book_id' => $book_id]);
            $relatedBooks = $booksRelated->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = 'Failed to fetch book details: ' . $e->getMessage();
        $_SESSION['message_type'] = 'error';
    }
}
// Check if a related book is clicked
$relatedId = $_GET['related_id'] ?? null;
if ($relatedId) {
    try {
        $callno = $conn->prepare("SELECT * FROM books WHERE id = :related_id");
        $callno->execute([':related_id' => $relatedId]);
        $book = $callno->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $_SESSION['message'] = 'Failed to fetch related book details: ' . $e->getMessage();
        $_SESSION['message_type'] = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Details: <?php echo htmlspecialchars($book['title'] ?? 'Not Found'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    keyframes: {
                        marquee: {
                            '0%': {
                                transform: 'translateX(0)'
                            },
                            '100%': {
                                transform: 'translateX(-50%)'
                            }
                        }
                    },
                    animation: {
                        marquee: 'marquee 2s linear infinite',
                    }
                }
            }
        }
    </script>
</head>
<style>
    .hover\:pause:hover {
        animation-play-state: paused;
    }
</style>

<body class="bg-gray-50">
    <div class="container hidden md:block mx-auto px-[10%] md:px-[5%] py-8">
        <?php if ($book): ?>
            <!-- Book Details Container -->
            <div class="bg-white rounded-lg shadow-lg p-6 md:p-10 mb-8 md:mt-0">
                <div class="flex flex-col md:flex-row">
                    <!-- Book Cover and Reserve Button - Appears first on mobile -->
                    <div class="w-full md:w-1/3 flex flex-col items-center mb-6 md:mb-0 md:order-last">
                        <?php if (!empty($book['books_image'])): ?>
                            <img src="../uploaded_file/<?php echo htmlspecialchars($book['books_image']); ?>" alt="Book Cover"
                                class="w-[150px] h-[200px] object-cover border border-gray-300 rounded-md shadow-custom mb-4">
                        <?php else: ?>
                            <div class="w-[150px] h-[200px] bg-gray-200 flex items-center justify-center text-gray-500 font-bold rounded-md shadow-custom mb-4">
                                Book Cover
                            </div>
                        <?php endif; ?>
                        <button class="reserve-btn bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-full transition duration-300 mt-2"
                            data-book-title="<?php echo htmlspecialchars($book['title']); ?>"
                            data-call-no="<?php echo htmlspecialchars($book['call_no']); ?>"
                            data-isbn="<?php echo htmlspecialchars($book['ISBN']); ?>"
                            data-book-id="<?php echo htmlspecialchars($book['id']); ?>">
                            Reserve
                        </button>
                    </div>
                    <!-- Book Information - Appears second on mobile -->
                    <div class="w-full md:w-2/3 pr-0 md:pr-8 md:order-first">
                        <h2 class="text-2xl font-bold mb-4"><?php echo htmlspecialchars($book['title']); ?></h2>
                        <table class="w-full border-collapse">
                            <tbody>
                                <tr class="border-b">
                                    <th class="py-2 text-left w-1/3">Material Type</th>
                                    <td class="py-2"><?php echo htmlspecialchars($book['material_type']); ?></td>
                                </tr>
                                <tr class="border-b">
                                    <th class="py-2 text-left">Sub Type</th>
                                    <td class="py-2"><?php echo htmlspecialchars($book['sub_type']); ?></td>
                                </tr>
                                <tr class="border-b">
                                    <th class="py-2 text-left">Author</th>
                                    <td class="py-2">
                                        <?php echo htmlspecialchars($book['author']); ?>
                                        [ <a href="selected_author.php?author=<?php echo urlencode($book['author']); ?>" class="text-blue-600 hover:underline">Browse</a> ]
                                    </td>
                                </tr>
                                <tr class="border-b">
                                    <th class="py-2 text-left">Publisher</th>
                                    <td class="py-2"><?php echo htmlspecialchars($book['publisher']); ?></td>
                                </tr>
                                <tr class="border-b">
                                    <th class="py-2 text-left">Copy Right</th>
                                    <td class="py-2"><?php echo htmlspecialchars($book['copyright']); ?></td>
                                </tr>
                                <tr class="border-b">
                                    <th class="py-2 text-left">ISBN</th>
                                    <td class="py-2"><?php echo htmlspecialchars($book['ISBN']); ?></td>
                                </tr>
                                <tr class="border-b">
                                    <th class="py-2 text-left">Call Number</th>
                                    <td class="py-2"><?php echo htmlspecialchars($book['call_no']); ?></td>
                                </tr>
                                <tr class="border-b">
                                    <th class="py-2 text-left">Status</th>
                                    <td class="py-2">
                                        <span class="<?php echo $book['status'] === 'available' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> px-2 py-1 rounded-full text-sm">
                                            <?php echo htmlspecialchars($book['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr class="border-b">
                                    <th class="py-2 text-left">Category</th>
                                    <td class="py-2">
                                        <?php echo htmlspecialchars($book['category']); ?>
                                        [ <a href="search_categ.php?category=<?php echo urlencode($book['category']); ?>" class="text-blue-600 hover:underline">Browse</a> ]
                                    </td>
                                </tr>
                                <tr class="border-b">
                                    <th class="py-2 text-left">Copy</th>
                                    <td class="py-2"><?php echo htmlspecialchars($book['copies']); ?></td>
                                </tr>
                                <tr class="border-b">
                                    <th class="py-2 text-left">Edition</th>
                                    <td class="py-2"><?php echo htmlspecialchars($book['edition']); ?></td>
                                </tr>
                                <tr class="border-b">
                                    <th class="py-2 text-left">Page Range</th>
                                    <td class="py-2"><?php echo htmlspecialchars($book['page_number']); ?></td>
                                </tr>
                                <tr class="border-b">
                                    <th class="py-2 text-left">Subject</th>
                                    <td class="py-2"><?php echo htmlspecialchars($book['subject']); ?></td>
                                </tr>
                                <tr class="border-b">
                                    <th class="py-2 text-left">Summary</th>
                                    <td class="py-2"><?php echo htmlspecialchars($book['summary']); ?></td>
                                </tr>
                                <tr class="border-b">
                                    <th class="py-2 text-left">Content</th>
                                    <td class="py-2"><?php echo htmlspecialchars($book['content']); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Related Books Section -->
            <div class="bg-white rounded-lg shadow-lg p-6 md:p-10 mb-8">
                <h4 class="text-xl font-semibold mb-6 text-center">Related Items</h4>
                <!-- Small Screen Marquee Effect -->
                <div class="block md:hidden overflow-hidden">
                    <div class="flex whitespace-nowrap animate-marquee hover:pause">
                        <?php
                        // Display each book twice for continuous scrolling effect
                        for ($i = 0; $i < 2; $i++):
                            foreach ($relatedBooks as $relatedBook):
                        ?>
                                <div class="inline-block flex-shrink-0 p-3 mx-2">
                                    <a href="?id=<?php echo urlencode($relatedBook['id']); ?>" title="Click to view details">
                                        <div class="w-[100px] h-[150px] rounded-md shadow-lg overflow-hidden">
                                            <?php if (!empty($relatedBook['books_image'])): ?>
                                                <img src="../uploaded_file/<?php echo htmlspecialchars($relatedBook['books_image']); ?>"
                                                    alt="Related Book Cover"
                                                    class="w-full h-full object-cover hover:opacity-90 transition duration-300">
                                            <?php else: ?>
                                                <div class="w-full h-full bg-gray-200 flex items-center justify-center text-gray-500 font-bold hover:opacity-90 transition duration-300">
                                                    Book Cover
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                </div>
                        <?php
                            endforeach;
                        endfor;
                        ?>
                    </div>
                </div>
                <!-- Medium Screen (Centered) -->
                <div class="hidden md:block lg:hidden">
                    <div class="flex flex-wrap justify-center">
                        <?php foreach ($relatedBooks as $relatedBook): ?>
                            <div class="p-3 mx-2">
                                <a href="?id=<?php echo urlencode($relatedBook['id']); ?>" title="Click to view details">
                                    <div class="w-[100px] h-[150px] rounded-md shadow-lg overflow-hidden">
                                        <?php if (!empty($relatedBook['books_image'])): ?>
                                            <img src="../uploaded_file/<?php echo htmlspecialchars($relatedBook['books_image']); ?>"
                                                alt="Related Book Cover"
                                                class="w-full h-full object-cover hover:opacity-90 transition duration-300">
                                        <?php else: ?>
                                            <div class="w-full h-full bg-gray-200 flex items-center justify-center text-gray-500 font-bold hover:opacity-90 transition duration-300">
                                                Book Cover
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <!-- Large Screen (Grid) -->
                <div class="hidden lg:block">
                    <div class="flex flex-wrap justify-center">
                        <?php foreach ($relatedBooks as $relatedBook): ?>
                            <div class="p-3 mx-2">
                                <a href="?id=<?php echo urlencode($relatedBook['id']); ?>" title="Click to view details">
                                    <div class="w-[100px] h-[150px] rounded-md shadow-lg overflow-hidden">
                                        <?php if (!empty($relatedBook['books_image'])): ?>
                                            <img src="../uploaded_file/<?php echo htmlspecialchars($relatedBook['books_image']); ?>"
                                                alt="Related Book Cover"
                                                class="w-full h-full object-cover hover:opacity-90 transition duration-300">
                                        <?php else: ?>
                                            <div class="w-full h-full bg-gray-200 flex items-center justify-center text-gray-500 font-bold hover:opacity-90 transition duration-300">
                                                Book Cover
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mt-8" role="alert">
                <p>Book not found.</p>
            </div>
        <?php endif; ?>
    </div>
    <!-- Mobile view for books (visible only on small screens) -->
    <div class="md:hidden space-y-4 mt-4">
        <?php if (!empty($book)): ?>
            <div class="border rounded-md p-6    bg-white shadow-sm">
                <div class="flex items-center mb-3">
                    <div class="mr-4">
                        <?php if (!empty($book['books_image'])): ?>
                            <img src="../uploaded_file/<?php echo htmlspecialchars($book['books_image']); ?>" alt="Book Cover"
                                class="object-cover border border-gray-200" style="width: 60px; height: 80px;">
                        <?php else: ?>
                            <div class="flex items-center justify-center bg-gray-200 bg-opacity-65 text-gray-600 text-xs text-center"
                                style="width: 60px; height: 80px;">
                                No Cover
                            </div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <a href="studbook_detail.php?id=<?php echo urlencode($book['id']); ?>" class="book-title font-medium">
                            <?php echo htmlspecialchars($book['title']); ?>
                        </a>
                        <div class="text-xs text-gray-600">ID: <?php echo htmlspecialchars($book['id']); ?></div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div>
                        <span class="font-medium text-gray-700">Material Type:</span>
                        <a href="selected_author.php?author=<?php echo urlencode($book['author']); ?>" class="books-link block">
                            <?php echo htmlspecialchars($book['material_type']); ?>
                        </a>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Sub Type:</span>
                        <a href="selected_author.php?author=<?php echo urlencode($book['author']); ?>" class="books-link block">
                            <?php echo htmlspecialchars($book['sub_type']); ?>
                        </a>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Author:</span>
                        <?php echo htmlspecialchars($book['author']); ?>
                        [ <a href="selected_author.php?author=<?php echo urlencode($book['author']); ?>" class="text-blue-600 hover:underline">Browse</a> ]
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Publisher:</span>
                        <a href="publisher_browse.php?publisher=<?php echo urlencode($book['publisher']); ?>" class="books-link block">
                            <?php echo htmlspecialchars($book['publisher']); ?>
                        </a>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Status:</span>
                        <span class="<?php echo $book['status'] === 'available' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> px-2 py-1 rounded-full text-sm">
                            <?php echo htmlspecialchars($book['status']); ?>
                        </span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Copies:</span>
                        <span><?php echo htmlspecialchars($book['copies']); ?></span>
                    </div>
                    <div class="col-span-2">
                        <span class="font-medium text-gray-700">ISBN:</span>
                        <span><?php echo htmlspecialchars($book['ISBN']); ?></span>
                    </div>
                    <div>
                        <button class="reserve-btn bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-full mt-2"
                            data-book-title="<?php echo htmlspecialchars($book['title']); ?>"
                            data-call-no="<?php echo htmlspecialchars($book['call_no']); ?>"
                            data-isbn="<?php echo htmlspecialchars($book['ISBN']); ?>"
                            data-book-id="<?php echo htmlspecialchars($book['id']); ?>">
                            Reserve
                        </button>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center py-8 text-gray-500">No books found</div>
        <?php endif; ?>
        <div class="bg-white rounded-lg shadow-lg p-6 md:p-10 mb-8">
                <h4 class="text-xl font-semibold mb-6 text-center">Related Items</h4>
                <!-- Small Screen Marquee Effect -->
                <div class="block md:hidden overflow-hidden">
                    <div class="flex whitespace-nowrap animate-marquee hover:pause">
                        <?php
                        // Display each book twice for continuous scrolling effect
                        for ($i = 0; $i < 2; $i++):
                            foreach ($relatedBooks as $relatedBook):
                        ?>
                                <div class="inline-block flex-shrink-0 p-3 mx-2">
                                    <a href="?id=<?php echo urlencode($relatedBook['id']); ?>" title="Click to view details">
                                        <div class="w-[100px] h-[150px] rounded-md shadow-lg overflow-hidden">
                                            <?php if (!empty($relatedBook['books_image'])): ?>
                                                <img src="../uploaded_file/<?php echo htmlspecialchars($relatedBook['books_image']); ?>"
                                                    alt="Related Book Cover"
                                                    class="w-full h-full object-cover hover:opacity-90 transition duration-300">
                                            <?php else: ?>
                                                <div class="w-full h-full bg-gray-200 flex items-center justify-center text-gray-500 font-bold hover:opacity-90 transition duration-300">
                                                    Book Cover
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                </div>
                        <?php
                            endforeach;
                        endfor;
                        ?>
                    </div>
                </div>
            </div>
    </div>
    <?php include '../student/footer.php'; ?>
    <!-- SweetAlert2 for notifications -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.querySelectorAll('.reserve-btn').forEach(button => {
            button.addEventListener('click', function() {
                const bookTitle = this.getAttribute('data-book-title');
                const callNo = this.getAttribute('data-call-no');
                const isbn = this.getAttribute('data-isbn');
                const bookId = this.getAttribute('data-book-id');
                Swal.fire({
                    title: 'Confirm Reservation',
                    text: `Do you want to reserve "${bookTitle}"?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, reserve it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Create form data
                        const formData = new FormData();
                        formData.append('id', bookId); // Use bookId for reservation
                        // Send AJAX request
                        fetch(window.location.href, {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Reserved!',
                                        text: data.message
                                    }).then(() => {
                                        location.reload(); // Reload the page after reservation
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Reservation Failed',
                                        text: data.message
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Reservation Failed',
                                    text: 'There was an issue reserving the book. Please try again later.'
                                });
                            });
                    }
                });
            });
        });
    </script>
</body>

</html>