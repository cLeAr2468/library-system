<?php include '../component-library/connect.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link rel="stylesheet" href="../style/styleshitt.css">
    <title>Dashboard</title>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand navbar-collapse fw-bold" href="#">
                <i class="bi bi-journal-bookmark-fill icon-spacing"></i>NwSSU
            </a>
            <a class="navbar-brand fw-bold text-uppercase" href="#">Library Management System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <form class="d-flex ms-auto" role="search"></form>
                <ul class="navbar-nav mb-2 mb-lg-0">
                    <li class="nav-item dropdown nav-item-dark">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-fill"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../admin_panel/logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- -----------Navbar----------- -->
    <div class="wrapper d-flex">
        <aside id="sidebar" class="expand sidebar-custom">
            <div class="profile-admin">
                <img src="../images/logo.png" class="logos1">
                <p>Administrator</p>
            </div>
            <div class="d-flex">
                <button class="toggle-btn" type="button">
                    <i class="lni lni-grid-alt"></i>
                </button>
                <div class="sidebar-logo">
                    <a href="../admin_panel/admin_dashboard.php">Dashboard</a>
                </div>
            </div>
            <?php
            // Get the current page filename
            $current_page = basename($_SERVER['PHP_SELF']);
            ?>
            <ul class="list-unstyled">
                <li class="sidebar-item">
                    <a href="#" class="sidebar-link collapsed has-dropdown <?= ($current_page == 'books_detail.php' || $current_page == 'display_books.php' || $current_page == 'categories.php' || $current_page == 'categbooks.php') ? 'active' : '' ?>" id="catalog-toggle" data-bs-toggle="collapse"
                        data-bs-target="#manageBooks" aria-expanded="<?= ($current_page == 'books_detail.php' || $current_page == 'display_books.php' || $current_page == 'categories.php' || $current_page == 'categbooks.php') ? 'true' : 'false' ?>" aria-controls="manageBooks">
                        <i class="bi bi-kanban"></i>
                        <span>Catalog</span>
                    </a>
                    <ul id="manageBooks" class="sidebar-dropdown list-unstyled collapse <?= ($current_page == 'books_detail.php' || $current_page == 'display_books.php' || $current_page == 'categories.php' || $current_page == 'categbooks.php') ? 'show' : '' ?>" data-bs-parent="#sidebar">
                        <li class="sidebar-item">
                            <a href="../admin_panel/display_books.php" class="sidebar-link <?= ($current_page == 'books_detail.php' || $current_page == 'display_books.php') ? 'active' : '' ?>">
                                <i class="bi bi-eye"></i> Catalog Item
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a href="categories.php" class="sidebar-link <?= ($current_page == 'categories.php' || $current_page == 'categbooks.php') ? 'active' : '' ?>">
                                <i class="bi bi-tags"></i> Categories
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="sidebar-item">
                    <a href="../admin_panel/circulation.php" class="sidebar-link <?= ($current_page == 'circulation.php') ? 'active' : '' ?>">
                        <i class="lni lni-popup"></i>
                        <span>Circulations</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="../admin_panel/Student_list.php" class="sidebar-link <?= ($current_page == 'student_list.php' || $current_page == 'user_info.php' || $current_page == 'Student_list.php') ? 'active' : '' ?>">
                        <i class="bi bi-person-add"></i>
                        <span>Manage User</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="../admin_panel/confirm.php" id="pendingAccountLink" class="sidebar-link <?= ($current_page == 'confirm.php') ? 'active' : '' ?>">
                        <i class="bi bi-person-exclamation"></i>
                        <span>Pending Account</span>
                    </a>
                </li>
            </ul>
            <div class="sidebar-footer">
                <a href="../admin_panel/logout.php" class="sidebar-link">
                    <i class="lni lni-exit"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>
        <script src="../js/function.js"></script>
    </div>
</body>
</html>