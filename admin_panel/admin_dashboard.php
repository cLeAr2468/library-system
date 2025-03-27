
<?php 
include '../admin_panel/dash-function.php';
include '../admin_panel/side_nav.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="icon" type="image/png" href="../images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
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
        <!-- -----------card dashboard----------- -->
        <div class="container mt-2" id="cardArea">
            <div class="row">
                <div class="col-md-4">
                    <a href="../admin_panel/Student_list.php" class="text-decoration-none" id="Student-card-area">
                        <div class="card text-white custom-bg mb-3 clickable-card">
                            <div class="card-header">User List</div>
                            <div class="card-body">
                                <h5 class="card-title mb-5"><?= $approvedCount ?> Users</h5>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="../admin_panel/confirm.php" class="text-decoration-none" id="Pending-card-area">
                        <div class="card text-white bg-cust mb-3 clickable-card">
                            <div class="card-header">Pending Accounts</div>
                            <div class="card-body">
                                <h5 class="card-title mb-5"><?= $pendingCount ?> Accounts</h5>
                            </div>
                        </div>
                    </a>
                </div>
                <!-- Existing cards for Total Books, Reserved Books, etc. -->
                <div class="col-md-4">
                    <a href="../admin_panel/display_books.php" class="text-decoration-none">
                        <div class="card text-white bg-suc mb-3 clickable-card">
                            <div class="card-header">Total Books</div>
                            <div class="card-body">
                                <h5 class="card-title mb-2"><?= $totalBooksCount ?> Books</h5>
                                <p>Total Copies: <?= $totalCopiesCount ?></p> <!-- Display total copies here -->
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="reserve.php" class="text-decoration-none">
                        <div class="card text-white bg-dan mb-3 clickable-card">
                            <div class="card-header">Reserved Books</div>
                            <div class="card-body">
                                <h5 class="card-title mb-5"><?= $totalReservedBooksCount ?> Books</h5>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="return.php" class="text-decoration-none">
                        <div class="card text-white bg-inf mb-3 clickable-card">
                            <div class="card-header">Borrowed Books</div>
                            <div class="card-body">
                                <h5 class="card-title mb-5"><?= $totalBorrowedBooksCount ?> Books</h5>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="../admin_panel/fine_rec.php" class="text-decoration-none">
                        <div class="card text-white bg-fine mb-3 clickable-card">
                            <div class="card-header">Fine</div>
                            <div class="card-body">
                                <h5 class="card-title mb-5"><?= $totalUsersWithFinesCount ?> User</h5>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    
</div>
  <!-- Modal for Adding Announcement -->
  <div class="modal fade" id="addAnnouncementModal" tabindex="-1" aria-labelledby="addAnnouncementModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addAnnouncementModalLabel">Add Announcement</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="announcementForm">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Announcement Message</label>
                                <textarea class="form-control" id="message" name="message" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="image" class="form-label">Image</label>
                                <input type="file" class="form-control" id="image" name="image">
                            </div>
                            <input type="hidden" name="addAnnouncement" value="1"> <!-- Hidden field to identify add request -->
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="postAnnouncement">Post</button>
                    </div>
                </div>
            </div>
    </div>
    <script src="../jscode/function.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>