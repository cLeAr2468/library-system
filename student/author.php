<?php
include '../component-library/connect.php';
include '../student/side_navbars.php'; // Include the side navigation bar
// Pagination setup
$limit = 10; // Number of authors per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page number
$offset = ($page - 1) * $limit; // Offset for SQL query

// Query to get authors and their book counts with pagination
$query = "SELECT author, COUNT(*) AS item_count FROM books GROUP BY author ORDER BY author ASC LIMIT :limit OFFSET :offset";
$auth = $conn->prepare($query);
$auth->bindValue(':limit', $limit, PDO::PARAM_INT);
$auth->bindValue(':offset', $offset, PDO::PARAM_INT);
$auth->execute();
$authors = $auth->fetchAll(PDO::FETCH_ASSOC);

// Query to get total authors count for pagination
$totalAuthorsQuery = "SELECT COUNT(DISTINCT author) AS total FROM books";
$totalAuthorsStmt = $conn->prepare($totalAuthorsQuery);
$totalAuthorsStmt->execute();
$totalAuthors = $totalAuthorsStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalAuthors / $limit); // Calculate total pages
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authors</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- Include SweetAlert -->
</head>

<body>
    <div class="container mx-auto px-[5%] my-8">
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 ">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                <h1 class="text-2xl font-semibold mb-4 md:mb-0">Author</h1>
                <div class="relative w-full md:w-64">
                    <i class="bi bi-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500"></i>
                    <input type="text" id="searchInput" placeholder="Search Category" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" onkeyup="searchCategories()">
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-primary text-white">
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Author</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Item(s)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody id="publisherTableBody" class="bg-white divide-y divide-gray-200">
                        <?php foreach ($authors as $author): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="selected_author.php?author=<?php echo urlencode($author['author']); ?>" class="text-[#156295] font-medium hover:underline"><?php echo urlencode($author['author']); ?></a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($author['item_count']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="selected_author.php?author=<?php echo urlencode($author['author']); ?>" class="text-blue-600 hover:underline">Browse</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Pagination Controls -->
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
    </div>
    </div>
    <?php include '../student/footer.php'; ?>
    <script>
        function searchAuthor() {
            const input = document.getElementById('searchInput').value.toLowerCase(); // Get the search input
            const rows = document.querySelectorAll('#authorTableBody tr'); // Select all rows in the category table
            rows.forEach(row => {
                const AuthorName = row.cells[0].textContent.toLowerCase(); // Get the category name from the first cell
                // Show row if input matches category name
                row.style.display = AuthorName.includes(input) ? '' : 'none'; // Show or hide row
            });
        }
    </script>
</body>

</html>