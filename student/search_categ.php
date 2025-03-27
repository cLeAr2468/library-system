<?php
define('BOOKS_PER_PAGE', 10);
include '../component-library/connect.php';
include '../student/side_navbars.php';
try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $user_name, $user_password);
    // Set PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * BOOKS_PER_PAGE;
// Get the selected category from URL parameter
$selected_category = isset($_GET['category']) ? $_GET['category'] : '';
// Validate category to prevent SQL injection
$selected_category = htmlspecialchars($selected_category);
// Fetch books data based on selected category with sorting and pagination
$query = $conn->prepare("SELECT * FROM books WHERE category = :category ORDER BY title ASC LIMIT :offset, :limit");
$query->bindValue(':category', $selected_category, PDO::PARAM_STR);
$query->bindValue(':offset', $offset, PDO::PARAM_INT);
$query->bindValue(':limit', BOOKS_PER_PAGE, PDO::PARAM_INT);
$query->execute();
$books = $query->fetchAll(PDO::FETCH_ASSOC);
// Fetch total number of books for pagination calculation
$total_books_query = $conn->prepare("SELECT COUNT(*) FROM books WHERE category = :category");
$total_books_query->bindValue(':category', $selected_category, PDO::PARAM_STR);
$total_books_query->execute();
$total_books = $total_books_query->fetchColumn();
$total_pages = ceil($total_books / BOOKS_PER_PAGE);
// Fetch distinct categories for display
$categories_query = $conn->query("SELECT DISTINCT category FROM books");
$categories = $categories_query->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <div class="bg-white rounded-lg shadow-md">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold text-gray-800">Search</h2>
                </div>

                <?php if ($selected_category): ?>
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 text-primary">
                        <span class="mb-2 md:mb-0">
                            Selected Category: <strong><?php echo htmlspecialchars($selected_category); ?></strong>
                            [ <a href="../student/category_books.php" class="hover:underline">All</a> ]
                        </span>
                        <div class="relative w-full md:w-auto">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input type="text" id="searchInput" placeholder="Search Book"
                                class="pl-10 pr-4 py-2 w-full md:w-72 rounded-full border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                onkeyup="searchBooks()">
                        </div>
                    </div>
                <?php endif; ?>

                <div class="hidden md:block overflow-x-auto">
                    <table class=" min-w-full divide-y divide-gray-200">
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
                                                    <div class="w-20 h-28 flex items-center justify-center bg-gray-200 text-gray-600 text-xs font-medium border border-gray-300 rounded">
                                                        Book Cover
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>

                                        <td class="px-4 py-4">
                                            <div class="space-y-1">
                                                <a href="studbook_detail.php?id=<?php echo urlencode($book['id']); ?>" class="text-blue-600 hover:text-blue-800 hover:underline font-medium">
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
                <!-- Mobile view for books (visible only on small screens) -->
                <div class="md:hidden space-y-4 mt-4">
                    <?php if (!empty($books)): ?>
                        <?php foreach ($books as $book): ?>
                            <div class="border rounded-md p-4 bg-white shadow-sm">
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
                                        <div class="text-xs text-gray-600">Call No: <?php echo htmlspecialchars($book['id']); ?></div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    <div>
                                        <span class="font-medium text-gray-700">Author:</span>
                                        <a href="selected_author.php?author=<?php echo urlencode($book['author']); ?>" class="books-link block">
                                            <?php echo htmlspecialchars($book['author']); ?>
                                        </a>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-700">Publisher:</span>
                                        <a href="publisher_browse.php?publisher=<?php echo urlencode($book['publisher']); ?>" class="books-link block">
                                            <?php echo htmlspecialchars($book['publisher']); ?>
                                        </a>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-700">Status:</span>
                                        <span><?php echo htmlspecialchars($book['status']); ?></span>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-700">Copies:</span>
                                        <span><?php echo htmlspecialchars($book['copies']); ?></span>
                                    </div>
                                    <div class="col-span-2">
                                        <span class="font-medium text-gray-700">ISBN:</span>
                                        <span><?php echo htmlspecialchars($book['ISBN']); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500">No books found</div>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <div class="flex justify-center mt-6">
                    <nav class="inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?= $i; ?>&category=<?php echo urlencode($selected_category); ?>"
                                class="<?= ($i === $page) ? 'bg-primary text-white' : 'bg-white text-gray-500 hover:bg-gray-50' ?> relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium">
                                <?= $i; ?>
                            </a>
                        <?php endfor; ?>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <footer class="py-6 mt-8 bg-white border-t border-gray-200">
        <div class="container mx-auto text-center">
            <span class="text-gray-600">© 2024 NwSSU Library. All rights reserved.</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function searchBooks() {
            const input = document.getElementById('searchInput').value.toLowerCase();

            // Search in desktop table view
            const tableRows = document.querySelectorAll('#bookTable tr');
            tableRows.forEach(row => {
                if (row.cells && row.cells.length > 1) {
                    const title = row.cells[1].textContent.toLowerCase();
                    row.style.display = title.includes(input) ? '' : 'none';
                }
            });

            // Search in mobile card view
            const mobileCards = document.querySelectorAll('.md\\:hidden > div');
            mobileCards.forEach(card => {
                const title = card.querySelector('.book-title').textContent.toLowerCase();
                card.style.display = title.includes(input) ? '' : 'none';
            });
        }

        document.getElementById('saveChanges')?.addEventListener('click', function() {
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
    </script>
</body>

</html>