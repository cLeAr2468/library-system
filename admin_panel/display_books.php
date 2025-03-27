<?php

include '../admin_panel/ins-dis-function.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NwSSU : Catalogs</title>
    <link rel="icon" type="image/png" href="../images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../style/styleshitt.css">
</head>
<body>
    <div class="main p-3">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-10 fw-bold fs-3">
                    <p><span>Dashboard</span></p>
                </div>
            </div>
        </div>
        <div class="col-md-12 d-flex justify-content-between align-items-center mb-3">
            <button class="btn btn-primary custom-btn ms-3" data-bs-toggle="modal" data-bs-target="#addBookModal" onclick="resetModal()">Manual Add</button>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <form id="searchForm" class="input-group mt-2" method="GET" action="">
                    <select class="form-select" id="searchCategory" name="category" aria-label="Category">
                        <option value="all" <?= $search_category === 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="call_no" <?= $search_category === 'call_no' ? 'selected' : ''; ?>>Call No</option>
                        <option value="title" <?= $search_category === 'title' ? 'selected' : ''; ?>>Title</option>
                        <option value="author" <?= $search_category === 'author' ? 'selected' : ''; ?>>Author</option>
                        <option value="publisher" <?= $search_category === 'publisher' ? 'selected' : ''; ?>>Publisher</option>
                        <option value="publish_date" <?= $search_category === 'publish_date' ? 'selected' : ''; ?>>Publish Date</option>
                        <option value="category" <?= $search_category === 'category' ? 'selected' : ''; ?>>Category</option>
                        <option value="status" <?= $search_category === 'status' ? 'selected' : ''; ?>>Status</option>
                        <option value="ISBN" <?= $search_category === 'ISBN' ? 'selected' : ''; ?>>ISBN</option>
                        <option value="edition" <?= $search_category === 'edition' ? 'selected' : ''; ?>>Edition</option>
                        <option value="subject" <?= $search_category === 'subject' ? 'selected' : ''; ?>>Subject</option>
                        <option value="summary" <?= $search_category === 'summary' ? 'selected' : ''; ?>>Summary</option>
                    </select>
                    <input type="text" id="searchQuery" name="query" placeholder="Search..." value="<?php echo htmlspecialchars($search_query); ?>" class="form-control form-control-sm ps-4" style="width: 200px;">
                    <button type="submit" class="input-group-text" style="border: none; background: transparent;">
                        <i class="bi bi-search"></i>
                    </button>
                </form>
            </div>
        </div>
        <div class="container mt-3">
            <table class="table table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>Title</th>
                        <th>Authors/Editors</th>
                        <th>Publisher</th>
                        <th>Status</th>
                        <th>Copies</th>
                        <th>Material Type</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($books as $book): ?>
                        <tr>
                            <td>
                                <div class="book-title"><?php echo htmlspecialchars($book['title']); ?></div>
                                <small>Copyright: <?php echo htmlspecialchars($book['copyright']); ?></small><br>
                                <small>ISBN: <?php echo htmlspecialchars($book['ISBN']); ?></small><br>
                                <small>Call No: <?php echo htmlspecialchars($book['call_no']); ?></small>
                            </td>
                            <td class="book-info"><?php echo htmlspecialchars($book['author']); ?></td>
                            <td class="book-info"><?php echo htmlspecialchars($book['publisher']); ?></td>
                            <td class="book-info"><?php echo htmlspecialchars($book['status']); ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($book['copies']); ?></td>
                            <td class="book-info"><?php echo htmlspecialchars($book['material_type']); ?></td>
                            <td class="action-column">
                                <div class="icon-container">
                                    <a href="books_detail.php?id=<?php echo urlencode($book['id']); ?>" class="view-btn">
                                        <i class="bi bi-eye icon-size" title="View Details"></i>
                                    </a>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#addBookModal" onclick="populateEditForm(<?php echo htmlspecialchars(json_encode($book)); ?>)">
                                        <i class="bi bi-pencil-square icon-size" title="Edit Book"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($i === $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?= $i; ?>&query=<?= urlencode($search_query); ?>&category=<?= urlencode($search_category); ?>"><?= $i; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <footer class="footer">
            <div class="container text-center">
                <span class="text-muted">© 2024 NwSSU Library Sj Campus <i class="fas fa-comment-alt-plus"></i>. All rights reserved.</span>
            </div>
        </footer>
    </div>
    <!-- Add Book Modal -->
    <div class="modal fade" id="addBookModal" tabindex="-1" aria-labelledby="addBookModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addBookModalLabel">Add New Book</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addBookForm" method="POST" enctype="multipart/form-data">
                        <input type="hidden" id="book_id" name="book_id" value="">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="material_type" class="form-label">Material Type</label>
                                <select class="form-select" id="material_type" name="material_type" required>
                                    <option value="" disabled selected>Select Type</option> 
                                    <option value="Periodical">Periodical</option>
                                    <option value="Book">Book</option>
                                    <option value="E-Book">E-Book</option>
                                    <option value="Journal">Journal</option>
                                    <option value="Unpublished Material">Unpublished Material</option>
                                    <option value="Audio Visual Material">Audio Visual Material</option>
                                </select>
                                <div class="invalid-feedback">Please Select Type.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="issn" class="form-label">ISSN</label>
                                <input type="text" class="form-control" id="issn" name="issn" required>
                                <div class="invalid-feedback">Please provide a valid ISSN.</div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="sub_type" class="form-label">Sub Type</label>
                                <select class="form-select" id="sub_type" name="sub_type" required>
                                    <option value="" disabled selected>Select Sub Type</option>
                                    <option value="Reference">Reference</option>
                                    <option value="Fiction">Fiction</option>
                                    <option value="Reserve">Reserve</option>
                                    <option value="Thesis">Thesis</option>
                                    <option value="Others">Others</option>
                                </select>
                                <div class="invalid-feedback">Please select a Sub Type.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="call_no" class="form-label">Call No</label>
                                <input type="text" class="form-control" id="call_no" name="call_no" required>
                                <div class="invalid-feedback">Please provide a valid Call No.</div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="" disabled selected>Select Category</option>
                                    <option value="BAT">BAT</option>
                                    <option value="BEED">BEED</option>
                                    <option value="BSCRIM">BSCRIM</option>
                                    <option value="BSA">BSA</option>
                                    <option value="BSA-Animal Science">BSA-Animal Science</option>
                                    <option value="BSA-Horticulture">BSA-Horticulture</option>
                                    <option value="BSABE">BSABE</option>
                                    <option value="BSF">BSF</option>
                                    <option value="BSF-Fishery">BSF-Fishery</option>
                                    <option value="BSIT">BSIT</option>
                                    <option value="BSCS">BSCS</option>
                                    <option value="BSED">BSED</option>
                                    <option value="BSSW">BSSW</option>
                                    <option value="Others">Others</option>
                                </select>
                                <div class="invalid-feedback">Please select a Category.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                                <div class="invalid-feedback">Please provide a valid Title.</div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="author" class="form-label">Author</label>
                                <input type="text" class="form-control" id="author" name="author" required>
                                <div class="invalid-feedback">Please provide a valid Author.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="publisher" class="form-label">Publisher</label>
                                <input type="text" class="form-control" id="publisher" name="publisher" required>
                                <div class="invalid-feedback">Please provide a valid Publisher.</div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="" disabled selected>Select Status</option>
                                    <option value="available">Available</option>
                                    <option value="Not Available">Not Available</option>
                                    <option value="Lost">Lost</option>
                                </select>
                                <div class="invalid-feedback">Please select a Status.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="copies" class="form-label">Copies</label>
                                <input type="number" class="form-control" id="copies" name="copies" min="1" required>
                                <div class="invalid-feedback">Please provide a valid number of copies.</div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="ISBN" class="form-label">ISBN</label>
                                <input type="text" class="form-control" id="ISBN" name="ISBN" required>
                                <div class="invalid-feedback">Please provide a valid ISBN.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="edition" class="form-label">Edition</label>
                                <input type="text" class="form-control" id="edition" name="edition" required>
                                <div class="invalid-feedback">Please provide a valid Edition.</div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="copyright" class="form-label">Copyright</label>
                                <input type="text" class="form-control" id="copyright" name="copyright" required>
                                <div class="invalid-feedback">Please provide a valid Copyright year.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="page_number" class="form-label">Page Number</label>
                                <input type="number" class="form-control" id="page_number" name="page_number" min="1" required>
                                <div class="invalid-feedback">Please provide a valid Page Number.</div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="subject" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="subject" name="subject" required>
                                <div class="invalid-feedback">Please provide a valid Subject.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="date_acquired" class="form-label">Date Acquired</label>
                                <input type="date" class="form-control" id="date_acquired" name="date_acquired" required>
                                <div class="invalid-feedback">Please provide a valid Date Acquired.</div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="content" class="form-label">Content</label>
                            <textarea class="form-control" id="content" name="content" rows="3" required></textarea>
                            <div class="invalid-feedback">Please provide valid Content.</div>
                        </div>
                        <div class="mb-3">
                            <label for="summary" class="form-label">Summary</label>
                            <textarea class="form-control" id="summary" name="summary" rows="3" required></textarea>
                            <div class="invalid-feedback">Please provide a valid Summary.</div>
                        </div>
                        <div class="mb-3">
                            <label for="book_image" class="form-label">Book Image</label>
                            <input type="file" class="form-control" id="book_image" name="book_image" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <img id="preview" src="#" alt="Image Preview" style="display:none; max-width: 100%; height: auto;" />
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary" id="submitButton" disabled>Update Book</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
let originalData = {};
function resetModal() {
    document.getElementById('addBookForm').reset();
    document.getElementById('book_id').value = '';
    document.getElementById('addBookModalLabel').innerText = 'Add New Book';
    document.getElementById('submitButton').innerText = 'Add Book';
    document.getElementById('submitButton').disabled = false; // Enable the button for new book
    document.getElementById('preview').style.display = 'none'; // Hide preview
    originalData = {}; // Clear original data
}
function populateEditForm(book) {
    document.getElementById('book_id').value = book.id; // Set book ID
    document.getElementById('call_no').value = book.call_no;
    document.getElementById('title').value = book.title;
    document.getElementById('author').value = book.author;
    document.getElementById('copyright').value = book.copyright;
    document.getElementById('publisher').value = book.publisher;
    document.getElementById('category').value = book.category;
    document.getElementById('copies').value = book.copies;
    document.getElementById('status').value = book.status;
    document.getElementById('ISBN').value = book.ISBN;
    document.getElementById('edition').value = book.edition;
    document.getElementById('page_number').value = book.page_number;
    document.getElementById('subject').value = book.subject;
    document.getElementById('content').value = book.content;
    document.getElementById('summary').value = book.summary;
    document.getElementById('date_acquired').value = book.date_acquired;
    document.getElementById('material_type').value = book.material_type; // Set Material Type
    document.getElementById('sub_type').value = book.sub_type; // Set Sub Type
    document.getElementById('issn').value = book.issn; // Set ISSN
    // Set image preview if needed
    const preview = document.getElementById('preview');
    preview.src = book.books_image ? book.books_image : '#'; // Use existing image URL if available
    preview.style.display = book.books_image ? 'block' : 'none'; // Show if image exists
    // Change modal title and button text
    document.getElementById('addBookModalLabel').innerText = 'Update Book';
    document.getElementById('submitButton').innerText = 'Update Book';
    // Store original data for comparison
    originalData = { ...book };
    checkForChanges(); // Check for changes after populating the form
}
// Function to check for changes in the input fields
function checkForChanges() {
    const inputs = document.querySelectorAll('#addBookForm input, #addBookForm select, #addBookForm textarea');
    let hasChanges = false;
    inputs.forEach(input => {
        if (input.type === 'file') return; // Skip file inputs
        const originalValue = originalData[input.name]; // Get original value
        if (input.value !== originalValue) {
            hasChanges = true; // If any value is different, set hasChanges to true
        }
    });
    document.getElementById('submitButton').disabled = !hasChanges; // Enable/disable button
}
// Add event listeners to track changes
document.querySelectorAll('#addBookForm input, #addBookForm select, #addBookForm textarea').forEach(input => {
    input.addEventListener('input', checkForChanges);
    input.addEventListener('change', checkForChanges);
});
// Handle form submission
document.getElementById('addBookForm').addEventListener('submit', function(e) {
    e.preventDefault(); // Prevent default form submission
    const formData = new FormData(this); // Create FormData object
    const url = '../admin_panel/display_books.php'; // Adjust the URL as necessar
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        Swal.close(); // Close loading alert
        if (data.status === 'success') {
            Swal.fire({
                title: 'Success!',
                text: data.message,
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.reload(); // Reload the page to see the updates
            });
        } else {
            Swal.fire({
                title: 'Error!',
                text: data.message,
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    })
    .catch(error => {
        Swal.close(); // Close loading alert
        Swal.fire({
            title: 'Error!',
            text: 'An error occurred while processing your request.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
    });
});
    </script>
</body>
</html>