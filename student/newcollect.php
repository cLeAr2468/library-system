<?php
include '../component-library/connect.php'; // Ensure this file correctly sets $db_host, $db_name, $user_name, $user_password
include '../student/side_navbars.php';
try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $user_name, $user_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
// Pagination logic
$limit = 10; // Number of books per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
// Fetch books acquired in 2019 and later, sorted alphabetically
$stmt = $conn->prepare("SELECT * FROM books WHERE date_acquired >= '2019-01-01' ORDER BY title ASC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
try {
    $stmt->execute();
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching books: " . $e->getMessage());
}
// Count total books for pagination
$totalStmt = $conn->prepare("SELECT COUNT(*) FROM books WHERE date_acquired >= '2019-01-01'");
$totalStmt->execute();
$totalBooks = $totalStmt->fetchColumn();
$totalPages = ceil($totalBooks / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Collection</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: 'rgb(3, 163, 3)',
                            hover: 'rgb(2, 143, 2)'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 max-w-7xl">
        <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
            <h2 class="text-lg sm:text-xl font-semibold mb-4">New Collection</h2>
            
            <!-- Desktop Table (hidden on mobile) -->
            <div class="overflow-x-auto hidden md:block">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 bg-primary text-white text-left text-xs font-medium uppercase tracking-wider w-24"></th>
                            <th class="px-4 py-3 bg-primary text-white text-left text-xs font-medium uppercase tracking-wider">Title</th>
                            <th class="px-4 py-3 bg-primary text-white text-left text-xs font-medium uppercase tracking-wider">Authors/Editors</th>
                            <th class="px-4 py-3 bg-primary text-white text-left text-xs font-medium uppercase tracking-wider">Publisher</th>
                            <th class="px-4 py-3 bg-primary text-white text-left text-xs font-medium uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 bg-primary text-white text-left text-xs font-medium uppercase tracking-wider">Copies</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="bookTable">
                        <?php if (empty($books)): ?>
                            <tr>
                                <td colspan="6" class="px-4 py-4 text-center text-gray-500">No books found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($books as $book): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="flex justify-center">
                                            <?php if (!empty($book['books_image'])): ?>
                                                <img src="../uploaded_file/<?php echo htmlspecialchars($book['books_image']); ?>" alt="Book Cover" class="w-20 h-28 object-cover border border-gray-200 rounded">
                                            <?php else: ?>
                                                <div class="w-20 h-28 bg-gray-200 flex items-center justify-center text-gray-600 text-xs font-medium border border-gray-300 rounded">
                                                    Book Cover
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="space-y-1">
                                            <a href="studbook_detail.php?id=<?php echo urlencode($book['id']); ?>" class="text-[#156295] hover:text-blue-800 hover:underline font-medium">
                                                <?php echo htmlspecialchars($book['title']); ?>
                                            </a>
                                            <div class="text-xs text-gray-500">
                                                <p>Copyright: <?php echo htmlspecialchars($book['copyright']); ?></p>
                                                <p>ISBN: <?php echo htmlspecialchars($book['ISBN']); ?></p>
                                                <p>ID: <?php echo htmlspecialchars($book['id']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <a href="selected_author.php?author=<?php echo urlencode($book['author']); ?>" class="text-blue-600 hover:text-blue-800 hover:underline">
                                            <?php echo htmlspecialchars($book['author']); ?>
                                        </a>
                                    </td>
                                    <td class="px-4 py-4">
                                        <a href="publisher_browse.php?publisher=<?php echo urlencode($book['publisher']); ?>" class="text-blue-600 hover:text-blue-800 hover:underline">
                                            <?php echo htmlspecialchars($book['publisher']); ?>
                                        </a>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <span class="<?php echo $book['status'] === 'available' ? 'text-green-600' : 'text-red-600'; ?>">
                                            <?php echo htmlspecialchars($book['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <?php echo htmlspecialchars($book['copies']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

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
                                            <p><span class="font-medium">Author:</span> 
                                                <a href="selected_author.php?author=<?php echo urlencode($book['author']); ?>" class="text-blue-600">
                                                    <?php echo htmlspecialchars($book['author']); ?>
                                                </a>
                                            </p>
                                            <p><span class="font-medium">Publisher:</span> 
                                                <a href="publisher_browse.php?publisher=<?php echo urlencode($book['publisher']); ?>" class="text-blue-600">
                                                    <?php echo htmlspecialchars($book['publisher']); ?>
                                                </a>
                                            </p>
                                            <p><span class="font-medium">Copyright:</span> <?php echo htmlspecialchars($book['copyright']); ?></p>
                                            <p><span class="font-medium">ISBN:</span> <?php echo htmlspecialchars($book['ISBN']); ?></p>
                                            <p><span class="font-medium">ID:</span> <?php echo htmlspecialchars($book['id']); ?></p>
                                        </div>
                                        <div class="flex flex-wrap gap-y-1 gap-x-4 text-xs text-gray-500 mt-2">
                                            <div class="flex items-center">
                                                <?php echo $book['copies'] == 1 ? 'copy' : 'copies'; ?> : <?php echo htmlspecialchars($book['copies']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 flex justify-between text-sm">
                                    <div>
                                        <span class="font-medium">Status:</span> 
                                        <span class="px-2 py-1 rounded-full text-xs <?php echo $book['status'] === 'available' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo htmlspecialchars($book['status']); ?>
                                        </span>
                                    </div>
                                    <div>
                                        <span class="font-medium">Copies:</span> 
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

            <!-- Pagination -->
            <nav aria-label="Page navigation" class="mt-6">
                <ul class="flex flex-wrap justify-center gap-1">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li>
                            <a href="?page=<?php echo $i; ?>" class="px-3 py-1 sm:px-4 sm:py-2 border border-gray-300 rounded-md text-sm font-medium <?php echo $i === $page ? 'bg-primary text-white' : 'bg-white text-gray-700 hover:bg-gray-50'; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    </div>
    <?php include '../student/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>