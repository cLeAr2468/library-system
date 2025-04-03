<?php
include '../component-library/connect.php';
include '../student/side_navbars.php';
// Query to get publishers and their book counts
$query = "SELECT publisher, COUNT(*) AS item_count FROM books GROUP BY publisher";
$publish = $conn->prepare($query);
$publish->execute();
$publisher = $publish->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publisher</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- Include SweetAlert -->
</head>
<body>
    <div class="container mx-auto px-[5%] my-8">
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 ">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                <h1 class="text-2xl font-semibold mb-4 md:mb-0">Publisher</h1>
                <div class="relative w-full md:w-64">
                    <i class="bi bi-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500"></i>
                    <input type="text" id="searchInput" placeholder="Search Category" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" onkeyup="searchCategories()">
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-primary text-white">
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Publisher</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Item(s)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody id="publisherTableBody" class="bg-white divide-y divide-gray-200">
                        <?php foreach ($publisher as $pub): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="publisher_browse.php?publisher=<?php echo urlencode($pub['publisher']); ?>" class="text-primary hover:underline"><?php echo urlencode($pub['publisher']); ?></a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($pub['item_count']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                   <a href="publisher_browse.php?publisher=<?php echo urlencode($pub['publisher']); ?>" class="text-primary hover:underline">Browse</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php include '../student/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function searchPublisher() {
            const input = document.getElementById('searchInput').value.toLowerCase(); // Get the search input
            const rows = document.querySelectorAll('#publisherTableBody tr'); // Select all rows in the category table
            rows.forEach(row => {
                const PublisherName = row.cells[0].textContent.toLowerCase(); // Get the category name from the first cell
                // Show row if input matches category name
                row.style.display = PublisherName.includes(input) ? '' : 'none'; // Show or hide row
            });
        }
    </script>
</body>

</html>