<?php
include '../component-library/connect.php';
include '../student/side_navbars.php';

// Query to get categories and their book counts
$query = "SELECT category, COUNT(*) AS item_count FROM books GROUP BY category";
$categ = $conn->prepare($query);
$categ->execute();
$categories = $categ->fetchAll(PDO::FETCH_ASSOC);

function searchBooksByCategory($searchTerm, $conn) {
    // Prepare the SQL statement with a placeholder
    $sql = "SELECT * FROM books WHERE category LIKE :searchTerm";
    // Prepare the statement
    $stmt = $conn->prepare($sql);
    // Bind the parameter with wildcards for LIKE
    $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%', PDO::PARAM_STR);
    // Execute the statement
    $stmt->execute();
    // Fetch all results
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $results;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- Include SweetAlert -->
</head>
        
<body class="bg-gray-50">
 <!-- Main Content -->
    <div class="container mx-auto px-[5%] my-8">
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                <h1 class="text-2xl font-semibold mb-4 md:mb-0">Categories</h1>
                <div class="relative w-full md:w-64">
                    <i class="bi bi-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500"></i>
                    <input type="text" id="searchInput" placeholder="Search Category" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" onkeyup="searchCategories()">
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-primary text-white">
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Item(s)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody id="categoriesTableBody" class="bg-white divide-y divide-gray-200">
                        <?php foreach ($categories as $category): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="search_categ.php?category=<?php echo urlencode($category['category']); ?>" class="text-primary hover:underline"><?php echo htmlspecialchars($category['category']); ?></a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($category['item_count']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="search_categ.php?category=<?php echo urlencode($category['category']); ?>" class="text-primary hover:underline">Browse</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-6 mt-8">
        <div class="container mx-auto text-center">
            <span>© 2024 NwSSU Library. All rights reserved.</span>
        </div>
    </footer>

    <script>
        // Search categories function
        function searchCategories() {
            const input = document.getElementById('searchInput').value.toLowerCase(); // Get the search input
            const rows = document.querySelectorAll('#categoriesTableBody tr'); // Select all rows in the category table
            rows.forEach(row => {
                const categoryName = row.cells[0].textContent.toLowerCase(); // Get the category name from the first cell
                // Show row if input matches category name
                row.style.display = categoryName.includes(input) ? '' : 'none'; // Show or hide row
            });
        }
    </script>
</body>

</html>