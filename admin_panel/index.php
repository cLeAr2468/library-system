
<?php
session_start();

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: ../admin_panel/admin_dashboard.php');
    exit();
}

$alert_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = htmlspecialchars ($_POST['username']);
    $password = htmlspecialchars($_POST['pass']);

    if ($username == 'admin' && $password == 'admin') {
        $_SESSION['admin_logged_in'] = true;
        header('Location: ../admin_panel/admin_dashboard.php');
        exit();
    } else {
        $alert_message = 'Invalid username or password';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.5.0/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../admin_style/styleshit.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .navbar-custom {
            background-color: #343a40; /* Dark gray color */
        }

        /* Style for positioning the eye icon inside the input field */
        .input-group {
            position: relative;
        }

        .input-group-text {
            cursor: pointer;
            position: absolute;
            right: 1px;
            top: 40%;
            transform: translateY(-50%);
            z-index: 2;
            background-color: transparent;
            border: none;
        }

        .form-control {
            padding-right: 2.5rem;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
    <div class="container-fluid">
        <a class="navbar-brand navbar-collapse fw-bold" href="../index.php">
            <i class="bi bi-journal-bookmark-fill icon-spacing"></i>NwSSu
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
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="bg-img">
    <div class="header-text">WELCOME TO OUR LIBRARY</div>
    <div class="login-container">
        <h3>Admin Login</h3>
        <form action="" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="pass" name="pass" required>
                    <span class="input-group-text" id="toggle-password">
                        <i class="bi bi-eye-slash" id="toggle-icon"></i>
                    </span>
                </div>
            </div>
            <button type="submit" class="btn btn-danger login-btn">Login</button>
        </form>
    </div>
</div>

<script>
    const passInput = document.getElementById('pass');
    const togglePassword = document.getElementById('toggle-password');
    const toggleIcon = document.getElementById('toggle-icon');

    passInput.addEventListener('input', function() {
        if (passInput.value.length > 0) {
            togglePassword.style.display = 'block';
        } else {
            togglePassword.style.display = 'none';
        }
    });

    togglePassword.addEventListener('click', function() {
        const type = passInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passInput.setAttribute('type', type);

        toggleIcon.classList.toggle('bi-eye');
        toggleIcon.classList.toggle('bi-eye-slash');
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Display SweetAlert message if there is an error -->
<?php if ($alert_message != ''): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '<?php echo $alert_message; ?>'
        });
    </script>
<?php endif; ?>

<!-- script -->
<script src="../js/admin_script.js"></script>
<?php include '../component-library/alert.php'; ?>

</body>
</html>
