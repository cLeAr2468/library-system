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
    header('location:../index.html');
    exit();
}

// Pagination variables
$limit = 10; // Number of books to display per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page
$offset = ($page - 1) * $limit; // Calculate offset
$totalPages = 0; // Initialize totalPages

try {
    // Prepare SQL to get the most reserved books
    $stmt = $conn->prepare("
        SELECT b.id, b.title, b.books_image, b.status, b.copies, b.copyright, b.publisher, b.author, b.ISBN, 
               COUNT(rb.book_id) AS reservation_count
        FROM books b
        LEFT JOIN reserve_books rb ON b.id = rb.book_id
        GROUP BY b.id, b.title, b.books_image, b.status, b.copies, b.copyright, b.publisher, b.author, b.ISBN
        ORDER BY reservation_count DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count for pagination
    $countStmt = $conn->prepare("
        SELECT COUNT(DISTINCT b.id) AS total_count
        FROM books b
        LEFT JOIN reserve_books rb ON b.id = rb.book_id
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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NwSSU : Catalogs</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1a56db',
                        'primary-dark': '#1e429f',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">

    <?php include '../student/side_navbars.php'; ?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-4 sm:p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Top Collection</h2>
                </div>
                
                <!-- Responsive Table -->
                <div class="overflow-x-auto">
                    <!-- Desktop Table (hidden on mobile) -->
                    <table class="min-w-full divide-y divide-gray-200 hidden md:table">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Authors/Editors</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Publisher</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Copies</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="bookTable">
                            <?php if (empty($books)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">No books found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($books as $book): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex justify-center items-center h-28">
                                                <?php if (!empty($book['books_image'])): ?>
                                                    <img src="../uploaded_file/<?php echo htmlspecialchars($book['books_image']); ?>" alt="Book Cover" class="h-28 w-20 object-cover rounded shadow-sm">
                                                <?php else: ?>
                                                    <div class="h-28 w-20 bg-gray-200 rounded flex items-center justify-center text-gray-500">
                                                        <span class="text-xs font-bold">Book Cover</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm">
                                                <a href="studbook_detail.php?id=<?php echo urlencode($book['id']); ?>" class="text-primary hover:text-primary-dark font-medium">
                                                    <?php echo htmlspecialchars($book['title']); ?>
                                                </a>
                                                <div class="text-gray-500 text-xs mt-1">
                                                    <p>Copy Right: <?php echo htmlspecialchars($book['copyright']); ?></p>
                                                    <p>ISBN: <?php echo htmlspecialchars($book['ISBN']); ?></p>
                                                    <p>Call No: <?php echo htmlspecialchars($book['id']); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="selected_author.php?author=<?php echo urlencode($book['author']); ?>" class="text-primary hover:text-primary-dark text-sm">
                                                <?php echo htmlspecialchars($book['author']); ?>
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="publisher_browse.php?publisher=<?php echo urlencode($book['publisher']); ?>" class="text-primary hover:text-primary-dark text-sm">
                                                <?php echo htmlspecialchars($book['publisher']); ?>
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                            <?php echo htmlspecialchars($book['status']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                            <?php echo htmlspecialchars($book['copies']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- Mobile Card View (visible only on mobile) -->
                    <div class="md:hidden space-y-4">
                        <?php if (empty($books)): ?>
                            <div class="p-4 text-center text-sm text-gray-500 bg-white rounded-lg shadow">
                                No books found
                            </div>
                        <?php else: ?>
                            <?php foreach ($books as $book): ?>
                                <div class="bg-white rounded-lg shadow overflow-hidden">
                                    <div class="p-4">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 mr-4">
                                                <?php if (!empty($book['books_image'])): ?>
                                                    <img src="../uploaded_file/<?php echo htmlspecialchars($book['books_image']); ?>" alt="Book Cover" class="h-32 w-24 object-cover rounded shadow-sm">
                                                <?php else: ?>
                                                    <div class="h-32 w-24 bg-gray-200 rounded flex items-center justify-center text-gray-500">
                                                        <span class="text-xs font-bold">Book Cover</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-1">
                                                <h3 class="text-lg font-medium text-[#156295]">
                                                    <a href="studbook_detail.php?id=<?php echo urlencode($book['id']); ?>">
                                                        <?php echo htmlspecialchars($book['title']); ?>
                                                    </a>
                                                </h3>
                                                <div class="mt-1 text-sm text-gray-500">
                                                    <p><span class="font-medium ">Author:</span> 
                                                        <a href="selected_author.php?author=<?php echo urlencode($book['author']); ?>" class="text-blue-600">
                                                            <?php echo htmlspecialchars($book['author']); ?>
                                                        </a>
                                                    </p>
                                                    <p><span class="font-medium">Publisher:</span> 
                                                        <a href="publisher_browse.php?publisher=<?php echo urlencode($book['publisher']); ?>" class="text-blue-600">
                                                            <?php echo htmlspecialchars($book['publisher']); ?>
                                                        </a>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-4 flex justify-between text-sm">
                                            <div>
                                            <span class="<?php echo $book['status'] === 'available' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> px-3 py-1 rounded-full  font-medium inline-flex items-center">
                                                <span class="<?php echo $book['status'] === 'available' ? 'bg-green-400' : 'bg-red-400'; ?> w-2 h-2 rounded-full mr-2"></span>
                                                <?php echo htmlspecialchars($book['status']); ?>
                                            </span>
                                            </div>
                                            <div>
                                                <span class="font-medium"><?php echo $book['copies'] == 1 ? 'Copy' : 'Copies'; ?>:</span> 
                                                <span class="px-2 py-1 bg-gray-100 rounded-full text-xs">
                                                    <?php echo htmlspecialchars($book['copies']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 0): ?>
                <div class="mt-6">
                    <nav class="flex justify-center" aria-label="Page navigation">
                        <ul class="flex flex-wrap justify-center gap-1">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li>
                                    <a href="?page=<?php echo $i; ?>" class="<?php echo $i === $page ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'; ?> px-3 py-1 sm:px-4 sm:py-2 border border-gray-300 rounded-md text-sm font-medium">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../student/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    </script>
</body>
</html>