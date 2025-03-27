<?php
include '../component-library/connect.php'; // Ensure this file initializes $conn
include '../student/side_navbars.php';
try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $user_name, $user_password);
    // Set PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}
// Fetch book titles for suggestions
$suggestion_query = $conn->prepare("SELECT title FROM books");
$suggestion_query->execute();
$suggestions = $suggestion_query->fetchAll(PDO::FETCH_COLUMN);
// Determine the current page number and set the number of books per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$books_per_page = 10;
$offset = ($page - 1) * $books_per_page;
// Handle search query and category
$search_query = $_GET['query'] ?? '';
$search_category = $_GET['category'] ?? 'all';
$search_query_param = $search_query ? '%' . $search_query . '%' : '%'; // Add '%' for LIKE search
// Prepare the SQL query based on the selected category
if ($search_category !== 'all') {
    $query = $conn->prepare("SELECT * FROM books WHERE $search_category LIKE :search ORDER BY title ASC LIMIT :offset, :limit");
} else {
    // Search in all relevant fields including material_type and sub_type
    $query = $conn->prepare("SELECT * FROM books WHERE 
        id LIKE :search OR 
        title LIKE :search OR 
        author LIKE :search OR 
        copyright LIKE :search OR 
        publisher LIKE :search OR 
        category LIKE :search OR 
        status LIKE :search OR 
        ISBN LIKE :search OR 
        edition LIKE :search OR 
        subject LIKE :search OR 
        content LIKE :search OR 
        summary LIKE :search OR 
        material_type LIKE :search OR 
        sub_type LIKE :search 
        ORDER BY title ASC LIMIT :offset, :limit");
}
$query->bindValue(':search', $search_query_param, PDO::PARAM_STR);
$query->bindValue(':offset', $offset, PDO::PARAM_INT);
$query->bindValue(':limit', $books_per_page, PDO::PARAM_INT);
$query->execute();
$books = $query->fetchAll(PDO::FETCH_ASSOC);
// Fetch total number of books for pagination calculation
if ($search_category !== 'all') {
    $total_books_query = $conn->prepare("SELECT COUNT(*) FROM books WHERE $search_category LIKE :search");
} else {
    $total_books_query = $conn->prepare("SELECT COUNT(*) FROM books WHERE 
        id LIKE :search OR 
        title LIKE :search OR 
        author LIKE :search OR 
        copyright LIKE :search OR 
        publisher LIKE :search OR 
        category LIKE :search OR 
        status LIKE :search OR 
        ISBN LIKE :search OR 
        edition LIKE :search OR 
        subject LIKE :search OR 
        content LIKE :search OR 
        summary LIKE :search OR 
        material_type LIKE :search OR 
        sub_type LIKE :search");
}
$total_books_query->bindValue(':search', $search_query_param, PDO::PARAM_STR);
$total_books_query->execute();
$total_books = $total_books_query->fetchColumn();
$total_pages = ceil($total_books / $books_per_page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NwSSU : Catalogs</title>
    <!-- Tailwind CSS -->
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
    <!-- Line Icons -->
    <link rel="stylesheet" href="https://cdn.lineicons.com/4.0/lineicons.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-[5%] py-8 max-w-7xl">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <h2 class="text-2xl font-bold text-gray-800">Catalog</h2>
                <div class="flex flex-col w-full md:w-auto gap-3">
                    <!-- Category buttons -->
                    <div class="hidden md:block md:flex flex-wrap gap-2">
                        <a href="../student/category_books.php" class="bg-primary text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-primary-hover transition-colors">Categories</a>
                        <a href="../student/author.php" class="bg-primary text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-primary-hover transition-colors">Author</a>
                        <a href="../student/publisher.php" class="bg-primary text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-primary-hover transition-colors">Publisher</a>
                    </div>
                    <!-- Search form -->
                    <form id="searchForm" class="relative flex flex-col sm:flex-row gap-2 w-full md:w-auto" method="GET" action="">
                        <select id="searchCategory" name="category" class="rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm py-2 px-3">
                            <option value="all" <?= $search_category === 'all' ? 'selected' : ''; ?>>All</option>
                            <option value="id" <?= $search_category === 'id' ? 'selected' : ''; ?>>ID</option>
                            <option value="title" <?= $search_category === 'title' ? 'selected' : ''; ?>>Title</option>
                            <option value="author" <?= $search_category === 'author' ? 'selected' : ''; ?>>Author</option>
                            <option value="publisher" <?= $search_category === 'publisher' ? 'selected' : ''; ?>>Publisher</option>
                            <option value="copyright" <?= $search_category === 'copyright' ? 'selected' : ''; ?>>Copyright</option>
                            <option value="category" <?= $search_category === 'category' ? 'selected' : ''; ?>>Category</option>
                            <option value="status" <?= $search_category === 'status' ? 'selected' : ''; ?>>Status</option>
                            <option value="ISBN" <?= $search_category === 'ISBN' ? 'selected' : ''; ?>>ISBN</option>
                            <option value="edition" <?= $search_category === 'edition' ? 'selected' : ''; ?>>Edition</option>
                            <option value="subject" <?= $search_category === 'subject' ? 'selected' : ''; ?>>Subject</option>
                            <option value="content" <?= $search_category === 'content' ? 'selected' : ''; ?>>Content</option>
                            <option value="summary" <?= $search_category === 'summary' ? 'selected' : ''; ?>>Summary</option>
                            <option value="material_type" <?= $search_category === 'material_type' ? 'selected' : ''; ?>>Material Type</option>
                            <option value="sub_type" <?= $search_category === 'sub_type' ? 'selected' : ''; ?>>Sub Type</option>
                        </select>
                        <div class="relative">
                            <input 
                                type="text" 
                                id="searchQuery" 
                                name="query" 
                                placeholder="Search..." 
                                value="<?php echo htmlspecialchars($search_query); ?>" 
                                class="rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 pl-3 pr-10 py-2 text-sm w-full"
                                autocomplete="off"
                            >
                            <button type="submit" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Books table -->
            <div class="overflow-x-auto">
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
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="flex justify-center mt-6">
                <nav class="inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1; ?>&query=<?= urlencode($search_query); ?>&category=<?= urlencode($search_category); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <span class="sr-only">Previous</span>
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?= $i; ?>&query=<?= urlencode($search_query); ?>&category=<?= urlencode($search_category); ?>" 
                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium 
                           <?= ($i === $page) ? 'text-primary bg-primary bg-opacity-10 border-primary z-10' : 'text-gray-700 hover:bg-gray-50'; ?>">
                            <?= $i; ?>
                        </a>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1; ?>&query=<?= urlencode($search_query); ?>&category=<?= urlencode($search_category); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <span class="sr-only">Next</span>
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php include '../student/footer.php'; ?>
    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Search suggestions script -->
    <script>
        const suggestions = <?php echo json_encode($suggestions); ?>; // Pass PHP array to JavaScript
        const searchQueryInput = document.getElementById('searchQuery');
        const suggestionsList = document.getElementById('suggestions');
        searchQueryInput.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            suggestionsList.innerHTML = ''; // Clear previous suggestions
            if (query) {
                const filteredSuggestions = suggestions.filter(suggestion => 
                    suggestion.toLowerCase().includes(query)
                ).slice(0, 10); // Limit to 10 suggestions for better UX
                filteredSuggestions.forEach(suggestion => {
                    const suggestionItem = document.createElement('div');
                    suggestionItem.textContent = suggestion;
                    suggestionItem.classList.add('px-4', 'py-2', 'cursor-pointer', 'hover:bg-gray-100', 'text-sm');
                    suggestionItem.addEventListener('click', function() {
                        searchQueryInput.value = suggestion; // Set input value to clicked suggestion
                        suggestionsList.innerHTML = ''; // Clear suggestions
                        suggestionsList.classList.add('hidden'); // Hide suggestions
                    });
                    suggestionsList.appendChild(suggestionItem);
                });
                if (filteredSuggestions.length > 0) {
                    suggestionsList.classList.remove('hidden'); // Show suggestions
                } else {
                    suggestionsList.classList.add('hidden'); // Hide suggestions if none found
                }
            } else {
                suggestionsList.classList.add('hidden'); // Hide suggestions if input is empty
            }
        });
        // Hide suggestions when clicking outside
        document.addEventListener('click', function(event) {
            if (!searchQueryInput.contains(event.target) && !suggestionsList.contains(event.target)) {
                suggestionsList.classList.add('hidden');
            }
        });
        // Focus the search input
        searchQueryInput.addEventListener('focus', function() {
            if (this.value.length > 0) {
                const event = new Event('input');
                this.dispatchEvent(event);
            }
        });
    </script>
</body>
</html>