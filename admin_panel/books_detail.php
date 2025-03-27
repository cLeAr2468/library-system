<?php
include '../admin_panel/bks_dtls_func.php';
include '../admin_panel/side_nav.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Details</title>
    <link rel="icon" type="image/png" href="../images/logo.png">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../style/styleshitt.css">
    <link rel="stylesheet" href="../style/details.css">
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
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-md-15">
                    <div class="border-box p-3" style="border: 1px solid #dee2e6; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); background-color: #fff;">
                        <div class="row">
                            <div class="col-md-8 media-details">
                                <div class="blue-header">BOOK DETAILS</div>
                                <table class="table book-details-table table-sm mt-3">
                                    <tr>
                                        <th>Material Type</th>
                                        <td><?php echo htmlspecialchars($book['material_type']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Sub Type</th>
                                        <td><?php echo htmlspecialchars($book['sub_type']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Author</th>
                                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Publisher</th>
                                        <td><?php echo htmlspecialchars($book['publisher']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Copy Right</th>
                                        <td><?php echo htmlspecialchars($book['copyright']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>ISBN</th>
                                        <td><?php echo htmlspecialchars($book['ISBN']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Call Number</th>
                                        <td><?php echo htmlspecialchars($book['id']); ?></td> <!-- Changed id to id -->
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td><?php echo htmlspecialchars($book['status']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Category</th>
                                        <td><?php echo htmlspecialchars($book['category']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Copy</th>
                                        <td><?php echo htmlspecialchars($book['copies']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Edition</th>
                                        <td><?php echo htmlspecialchars($book['edition']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Page Range</th>
                                        <td><?php echo htmlspecialchars($book['page_number']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Subject</th>
                                        <td><?php echo htmlspecialchars($book['subject']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Summary</th>
                                        <td><?php echo htmlspecialchars($book['summary']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Content</th>
                                        <td><?php echo htmlspecialchars($book['content']); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <!-- Cover Photo Section -->
                            <div class="col-md-4">
                                <div class="cover-photo">
                                    <?php if (!empty($book['books_image'])): ?>
                                        <img id="book-cover" src="../uploaded_file/<?php echo htmlspecialchars($book['books_image']); ?>" alt="Book Cover" style="width: 150px; height: 200px; object-fit: cover; border-radius: 1rem; box-shadow: 8px 8px 10px rgba(0, 0, 0, 0.1); cursor: pointer;" onclick="document.getElementById('file-input').click();">
                                    <?php else: ?>
                                        <div id="missing-cover" style="width: 150px; height: 200px; background-color: rgba(232, 232, 232, 0.65); display: flex; align-items: center; justify-content: center; color: #555; margin-bottom: 15px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); border-radius: 5px; cursor: pointer;" onclick="document.getElementById('file-input').click();">
                                            <b>Book Cover</b>
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" id="file-input" accept="image/*" style="display: none;" onchange="previewImage(event)">
                                </div>
                                <button class="btn btn-primary change-photo-btn mt-3" onclick="uploadImage()">Change Photo</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- JavaScript Section -->
        <script src="../jscode/function.js"></script>
        <script>
            let originalImageSrc = ""; // Variable to store the original image source
            let newImageSrc = ""; // Variable to store the new image preview
            let isCoverImageMissing = false; // Flag to check if the cover image is missing

            // Store the original image source on page load
            window.onload = function() {
                const bookCover = document.getElementById("book-cover");
                originalImageSrc = bookCover.src;
                isCoverImageMissing = bookCover.src.includes("missing-cover"); // Check if the cover is missing
            };

            // Preview the selected image
            function previewImage(event) {
                const reader = new FileReader();
                reader.onload = function() {
                    newImageSrc = reader.result; // Store the new image source
                    document.getElementById("book-cover").src = newImageSrc; // Update the image preview
                }
                reader.readAsDataURL(event.target.files[0]);
            }

            // Upload the image and update the preview
            function uploadImage() {
                const fileInput = document.getElementById('file-input');
                // Check if a file is selected
                if (!fileInput.files[0]) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No file selected!',
                        text: 'Please select an image file to upload.',
                    });
                    return;
                }
                // Show confirmation dialog
                Swal.fire({
                    title: 'Are you sure?',
                    text: "Do you want to update this cover?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, update it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const formData = new FormData();
                        formData.append('books_image', fileInput.files[0]);
                        formData.append('id', <?php echo json_encode($book['id']); ?>);
                        fetch('../admin_panel/books_detail.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                console.log(data);
                                if (data.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success!',
                                        text: data.message,
                                    });
                                    document.getElementById("book-cover").src = "../uploaded_file/" + data.fileName; // Update image preview
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error!',
                                        text: data.message,
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: 'There was an error processing your request.',
                                });
                            });
                    } else {
                        // If canceled, reset the image source back to the original
                        document.getElementById("book-cover").src = originalImageSrc;
                        document.getElementById('file-input').value = ""; // Clear the file input
                    }
                });
            }
        </script>
        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>