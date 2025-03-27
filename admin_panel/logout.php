<?php
session_start();
session_unset();
session_destroy();
header('Location: ../admin_panel/index.php'); // Adjust the path to your login page
exit();
?>