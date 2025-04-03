    <?php
    session_start(); // Start the session
    include '../component-library/connect.php'; // Ensure this file initializes $conn
    include '../student/side_navbars.php';
    try {
        $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $user_name, $user_password);
        // Set PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die('Connection failed: ' . $e->getMessage());
    }

    // Get user's existing reservations
    $userReservations = [];
    if (isset($user_id)) {
        $reservationQuery = $conn->prepare("SELECT book_id FROM reserve_books WHERE user_id = :user_id AND status = 'reserved'");
        $reservationQuery->execute([':user_id' => $user_id]);
        $userReservations = $reservationQuery->fetchAll(PDO::FETCH_COLUMN);
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
        <style>
            /* Hide scrollbar for Chrome, Safari and Opera */
            .scrollbar-hide::-webkit-scrollbar {
                display: none;
            }

            /* Hide scrollbar for IE, Edge and Firefox */
            .scrollbar-hide {
                -ms-overflow-style: none;
                /* IE and Edge */
                scrollbar-width: none;
                /* Firefox */
            }

            @media (max-width: 768px) {
                .mobile-shadow {
                    box-shadow: 0 -1px 10px rgba(0, 0, 0, 0.05);
                }
            }
        </style>
    </head>

    <body class="bg-gray-50">
        <div class="container mx-auto max-w-7xl">
            <!-- Desktop Header (hidden on mobile) -->
            <div class="hidden md:block px-[5%] py-8">
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
                                <div class="relative flex-1">
                                    <input
                                        type="text"
                                        id="searchQuery"
                                        name="query"
                                        placeholder="Search..."
                                        value="<?php echo htmlspecialchars($search_query); ?>"
                                        class="rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 pl-3 pr-10 py-2 text-sm w-full"
                                        autocomplete="off">
                                    <button type="submit" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </button>
                                    <!-- Desktop Suggestions dropdown -->
                                    <div id="desktopSuggestions" class="absolute w-full bg-white mt-1 rounded-lg shadow-lg border border-gray-200 hidden z-50 max-h-60 overflow-y-auto">
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Desktop Table View -->
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

                    <!-- Desktop Pagination -->
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

            <!-- Mobile View -->
            <div class="md:hidden min-h-screen flex flex-col">
                <!-- Mobile Header -->
                <div class="sticky top-0 bg-white z-20 mobile-shadow">
                    <div class="px-4 py-4">
                        <div class="flex items-center justify-between mb-4">
                            <h1 class="text-xl font-bold text-gray-800">Catalog</h1>
                            <div class="flex gap-2">
                                <button id="mobileMenuToggle" class="p-2 text-gray-600 hover:text-primary">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Search and Filters -->
                        <div class="space-y-3">
                            <form id="mobileSearchForm" method="GET" action="" class="relative">
                                <input
                                    type="text"
                                    name="query"
                                    id="mobileSearchInput"
                                    placeholder="Search books..."
                                    value="<?php echo htmlspecialchars($search_query); ?>"
                                    class="w-full rounded-full border-gray-300 pl-10 pr-4 py-2 text-sm focus:border-primary focus:ring-primary"
                                    autocomplete="off">
                                <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                
                                <!-- Suggestions dropdown -->
                                <div id="mobileSuggestions" class="absolute w-full bg-white mt-1 rounded-lg shadow-lg border border-gray-200 hidden z-50 max-h-60 overflow-y-auto">
                                </div>
                            </form>

                            <!-- Category Navigation -->
                            <select id="mobileCategorySelect" name="category" class="w-full rounded-lg border-gray-300 text-sm">
                                <option value="all" <?= $search_category === 'all' ? 'selected' : ''; ?>>All Categories</option>
                                <option value="title" <?= $search_category === 'title' ? 'selected' : ''; ?>>Title</option>
                                <option value="author" <?= $search_category === 'author' ? 'selected' : ''; ?>>Author</option>
                                <option value="publisher" <?= $search_category === 'publisher' ? 'selected' : ''; ?>>Publisher</option>
                                <option value="ISBN" <?= $search_category === 'ISBN' ? 'selected' : ''; ?>>ISBN</option>
                                <option value="subject" <?= $search_category === 'subject' ? 'selected' : ''; ?>>Subject</option>
                                <option value="content" <?= $search_category === 'content' ? 'selected' : ''; ?>>Content</option>
                                <option value="material_type" <?= $search_category === 'material_type' ? 'selected' : ''; ?>>Material Type</option>
                                <option value="sub_type" <?= $search_category === 'sub_type' ? 'selected' : ''; ?>>Sub Type</option>
                            </select>
                        </div>
                    </div>
                    <div id="mobileMenu" class="hidden px-4 pb-4">
                    <div class="flex flex-col gap-2">
                        <a href="../student/category_books.php" class="bg-primary text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-primary-hover transition-colors text-center">
                            Categories
                        </a>
                        <a href="../student/author.php" class="bg-primary text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-primary-hover transition-colors text-center">
                            Author
                        </a>
                        <a href="../student/publisher.php" class="bg-primary text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-primary-hover transition-colors text-center">
                            Publisher
                        </a>
                    </div>
                </div>
                </div>


                <!-- Mobile Content -->
                <div class="flex-1 px-4 py-4">
                    <div class="space-y-4">
                        <?php if (empty($books)): ?>
                            <!-- Empty State -->
                            <div class="flex flex-col items-center justify-center py-12">
                                <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                                <p class="mt-4 text-gray-500 text-center">No books found</p>
                                <a href="?clear=1" class="mt-2 text-primary hover:text-primary-hover">Clear filters</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($books as $book): ?>
                                <!-- Book Card -->
                                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                    <div class="flex p-4">
                                        <!-- Book Cover -->
                                        <div class="flex-shrink-0">
                                            <!-- Book Cover -->
                                            <div class="relative">
                                                <?php if (!empty($book['books_image'])): ?>
                                                    <img src="../uploaded_file/<?php echo htmlspecialchars($book['books_image']); ?>"
                                                        alt="Book Cover"
                                                        class="w-20 h-28 object-cover border border-gray-200 rounded-lg shadow-sm">
                                                <?php else: ?>
                                                    <div class="w-20 h-28 bg-gray-200 flex items-center justify-center text-gray-600 text-xs font-medium border border-gray-300 rounded-lg">
                                                        Book Cover
                                                    </div>
                                                <?php endif; ?>


                                            </div>

                                            <!-- Material Type (below book cover) -->
                                            <div class="mt-2 text-center">
                                                <span class="text-xs text-gray-500  px-2 py-1 italic">
                                                    <?php echo htmlspecialchars($book['material_type']); ?>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Book Info -->
                                        <div class="ml-4 flex-1">
                                            <div class="flex flex-col h-full">
                                                <div>
                                                    <a href="studbook_detail.php?id=<?php echo urlencode($book['id']); ?>"
                                                        class="text-base font-semibold text-gray-900 hover:text-primary line-clamp-2">
                                                        <?php echo htmlspecialchars($book['title']); ?>
                                                    </a>
                                                    <p class="mt-1 text-sm text-gray-600">
                                                        By : <a href="selected_author.php?author=<?php echo urlencode($book['author']); ?>"
                                                            class="text-primary hover:text-primary-hover">
                                                            <?php echo htmlspecialchars($book['author']); ?>
                                                        </a>
                                                    </p>
                                                    <div class="mt-auto">
                                                    <div class="flex flex-wrap gap-y-1 gap-x-4 text-xs text-gray-500 mt-2">
                                                        <div class="flex items-center">
                                                            <?php echo htmlspecialchars($book['copies']); ?> copies
                                                        </div>
                                                        <div class="flex items-center">
                                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                                                            </svg>
                                                            ISBN: <?php echo htmlspecialchars($book['ISBN']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="border-t border-gray-100 bg-gray-50/50 px-4 py-3 flex justify-between items-center">
                                        <div class="text-xs text-gray-500">
                                            <span class="<?php echo $book['status'] === 'available' ? 'bg-green-100 text-green-800 ring-green-200' : 'bg-red-100 text-red-800 ring-red-200'; ?> text-xs font-medium px-2 py-1 rounded-full ring-1">
                                                <?php echo htmlspecialchars($book['status']); ?>
                                            </span>
                                        </div>
                                        <div class="flex gap-2">
                                            <a href="studbook_detail.php?id=<?php echo urlencode($book['id']); ?>"
                                                class="text-xs bg-primary text-white px-3 py-1.5 rounded-full hover:bg-primary-hover transition-colors">
                                                View Details
                                            </a>
                                            <?php if ($book['status'] === 'available'): ?>
                                                <button class="reserve-btn text-xs bg-blue-600 text-white px-3 py-1.5 rounded-full hover:bg-blue-700 transition-colors <?php echo in_array($book['id'], $userReservations) ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                                                    data-book-id="<?php echo htmlspecialchars($book['id']); ?>"
                                                    <?php echo in_array($book['id'], $userReservations) ? 'disabled' : ''; ?>>
                                                    <?php echo in_array($book['id'], $userReservations) ? 'Reserved' : 'Reserve'; ?>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <!-- Mobile Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <div class="mt-6">
                                    <div class="flex items-center justify-between bg-white px-4 py-3 rounded-lg shadow-sm">
                                        <?php if ($page > 1): ?>
                                            <a href="?page=<?= $page - 1; ?>&query=<?= urlencode($search_query); ?>&category=<?= urlencode($search_category); ?>"
                                                class="flex items-center text-sm text-gray-700 hover:text-primary">
                                                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                                </svg>
                                                Previous
                                            </a>
                                        <?php endif; ?>

                                        <span class="text-sm text-gray-700">
                                            Page <?= $page ?> of <?= $total_pages ?>
                                        </span>

                                        <?php if ($page < $total_pages): ?>
                                            <a href="?page=<?= $page + 1; ?>&query=<?= urlencode($search_query); ?>&category=<?= urlencode($search_category); ?>"
                                                class="flex items-center text-sm text-gray-700 hover:text-primary">
                                                Next
                                                <svg class="w-5 h-5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php include '../student/footer.php'; ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Mobile elements
                const mobileSearchInput = document.getElementById('mobileSearchInput');
                const mobileSuggestions = document.getElementById('mobileSuggestions');
                const mobileCategorySelect = document.getElementById('mobileCategorySelect');
                
                // Desktop elements
                const desktopSearchInput = document.getElementById('searchQuery');
                const desktopSuggestions = document.getElementById('desktopSuggestions');
                const desktopCategorySelect = document.getElementById('searchCategory');

                // Function to handle search input
                async function handleSearchInput(searchInput, suggestionsDiv, categorySelect) {
                    const searchTerm = searchInput.value;
                    const category = categorySelect.value;
                    
                    if (searchTerm.length < 2) {
                        suggestionsDiv.innerHTML = '';
                        suggestionsDiv.classList.add('hidden');
                        return;
                    }

                    try {
                        const response = await fetch(`get_suggestions.php?term=${encodeURIComponent(searchTerm)}&category=${encodeURIComponent(category)}`);
                        const suggestions = await response.json();
                        
                        if (suggestions.length > 0) {
                            displaySuggestions(suggestions, searchInput, suggestionsDiv);
                        } else {
                            suggestionsDiv.innerHTML = '<div class="px-4 py-2 text-gray-500">No suggestions found</div>';
                            suggestionsDiv.classList.remove('hidden');
                        }
                    } catch (error) {
                        console.error('Error fetching suggestions:', error);
                    }
                }

                // Function to display suggestions
                function displaySuggestions(suggestions, searchInput, suggestionsDiv) {
                    suggestionsDiv.innerHTML = '';
                    suggestionsDiv.classList.remove('hidden');

                    suggestions.forEach((suggestion) => {
                        const div = document.createElement('div');
                        div.className = 'px-4 py-2 hover:bg-gray-100 cursor-pointer';
                        div.innerHTML = suggestion;
                        div.addEventListener('click', () => {
                            searchInput.value = suggestion;
                            suggestionsDiv.classList.add('hidden');
                            searchInput.closest('form').submit();
                        });
                        suggestionsDiv.appendChild(div);
                    });
                }

                // Debounce function
                function debounce(func, wait) {
                    let timeout;
                    return function executedFunction(...args) {
                        const later = () => {
                            clearTimeout(timeout);
                            func(...args);
                        };
                        clearTimeout(timeout);
                        timeout = setTimeout(later, wait);
                    };
                }

                // Add event listeners for mobile
                if (mobileSearchInput) {
                    mobileSearchInput.addEventListener('input', debounce(() => {
                        handleSearchInput(mobileSearchInput, mobileSuggestions, mobileCategorySelect);
                    }, 300));

                    mobileCategorySelect.addEventListener('change', () => {
                        if (mobileSearchInput.value.length >= 2) {
                            handleSearchInput(mobileSearchInput, mobileSuggestions, mobileCategorySelect);
                        }
                    });
                }

                // Add event listeners for desktop
                if (desktopSearchInput) {
                    desktopSearchInput.addEventListener('input', debounce(() => {
                        handleSearchInput(desktopSearchInput, desktopSuggestions, desktopCategorySelect);
                    }, 300));

                    desktopCategorySelect.addEventListener('change', () => {
                        if (desktopSearchInput.value.length >= 2) {
                            handleSearchInput(desktopSearchInput, desktopSuggestions, desktopCategorySelect);
                        }
                    });
                }

                // Close suggestions when clicking outside
                document.addEventListener('click', function(e) {
                    // Handle mobile suggestions
                    if (mobileSuggestions && !mobileSearchInput?.contains(e.target) && !mobileSuggestions.contains(e.target)) {
                        mobileSuggestions.classList.add('hidden');
                    }
                    
                    // Handle desktop suggestions
                    if (desktopSuggestions && !desktopSearchInput?.contains(e.target) && !desktopSuggestions.contains(e.target)) {
                        desktopSuggestions.classList.add('hidden');
                    }
                });

                // Handle keyboard navigation
                function handleKeyboard(e, searchInput, suggestionsDiv) {
                    const suggestions = suggestionsDiv.getElementsByTagName('div');
                    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                        e.preventDefault();
                        let currentFocus = Array.from(suggestions).findIndex(div => div.classList.contains('bg-gray-100'));
                        
                        if (e.key === 'ArrowDown') {
                            currentFocus = currentFocus < suggestions.length - 1 ? currentFocus + 1 : 0;
                        } else {
                            currentFocus = currentFocus > 0 ? currentFocus - 1 : suggestions.length - 1;
                        }

                        Array.from(suggestions).forEach((div, index) => {
                            if (index === currentFocus) {
                                div.classList.add('bg-gray-100');
                                searchInput.value = div.textContent;
                            } else {
                                div.classList.remove('bg-gray-100');
                            }
                        });
                    }
                }

                // Add keyboard navigation
                if (mobileSearchInput) {
                    mobileSearchInput.addEventListener('keydown', (e) => handleKeyboard(e, mobileSearchInput, mobileSuggestions));
                }
                if (desktopSearchInput) {
                    desktopSearchInput.addEventListener('keydown', (e) => handleKeyboard(e, desktopSearchInput, desktopSuggestions));
                }

                // Add this to your existing DOMContentLoaded function
                const mobileMenuToggle = document.getElementById('mobileMenuToggle');
                const mobileMenu = document.getElementById('mobileMenu');

                if (mobileMenuToggle && mobileMenu) {
                    mobileMenuToggle.addEventListener('click', () => {
                        mobileMenu.classList.toggle('hidden');
                    });
                }

                // Handle book reservation
                document.querySelectorAll('.reserve-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const bookId = this.dataset.bookId;
                        if (this.disabled) return;

                        Swal.fire({
                            title: 'Confirm Reservation',
                            text: 'Do you want to reserve this book?',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, reserve it!',
                            cancelButtonText: 'No, cancel',
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                fetch('studbook_detail.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded',
                                    },
                                    body: `action=reserve_book&id=${bookId}`
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire({
                                            title: 'Reservation Successful!',
                                            text: data.message,
                                            icon: 'success',
                                            confirmButtonColor: '#3085d6'
                                        }).then(() => {
                                            location.reload();
                                        });
                                    } else {
                                        Swal.fire({
                                            title: 'Reservation Failed',
                                            text: data.message,
                                            icon: 'error',
                                            confirmButtonColor: '#3085d6'
                                        });
                                    }
                                })
                                .catch(error => {
                                    Swal.fire({
                                        title: 'Reservation Failed',
                                        text: 'An error occurred while processing your request.',
                                        icon: 'error',
                                        confirmButtonColor: '#3085d6'
                                    });
                                });
                            }
                        });
                    });
                });
            });
        </script>
    </body>

    </html>