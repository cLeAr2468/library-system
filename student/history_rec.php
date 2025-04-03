<?php
session_start(); // Start the session
include '../component-library/connect.php';
include '../student/side_navbars.php';
// Assume the logged-in student ID is stored in session
$user_id = $_SESSION['user_id'] ?? null;
if ($user_id) {
    // Fetch student profile data from the database
    $stud = $conn->prepare("SELECT * FROM user_info WHERE user_id = ?");
    $stud->execute([$user_id]);
    $student = $stud->fetch(PDO::FETCH_ASSOC);
    $profile_image = $student['images'] ?? '../images/prof.jpg'; // Fallback if no image

    // Fetch booking history from reserve_books table with join on books table
    $stmt = $conn->prepare("
        SELECT rb.*, b.copyright, b.books_image
        FROM reserve_books rb 
        JOIN books b ON rb.call_no = b.call_no
        WHERE rb.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Redirect to login if not logged in
    header('location:../index.php');
    exit();
}

function timeAgo($status, $cancel_date, $reserve_date, $borrowed_date, $return_date, $full = false)
{
    $now = new DateTime();
    $date = null;

    // Determine which date to use based on the status
    switch ($status) {
        case 'canceled':
            $date = $cancel_date;
            break;
        case 'reserved':
            $date = $reserve_date;
            break;
        case 'borrowed':
            $date = $borrowed_date;
            break;
        case 'returned':
            $date = $return_date;
            break;
        default:
            return 'Unknown status';
    }

    if ($date) {
        $ago = new DateTime($date);
        $diff = $now->diff($ago);
        $d = [];
        $units = [
            'year' => $diff->y,
            'month' => $diff->m,
            'day' => $diff->d,
            'hour' => $diff->h,
            'minute' => $diff->i,
            'second' => $diff->s,
        ];

        foreach ($units as $key => $value) {
            if ($value) {
                $d[] = $value . ' ' . $key . ($value > 1 ? 's' : '');
            }
        }

        if (!$full) $d = array_slice($d, 0, 1);
        return $d ? implode(', ', $d) . ' ago' : 'just now';
    }

    return 'Date not available';
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking History</title>
    <link rel="stylesheet" href="../admin_style/design.css">
    <link rel="stylesheet" href="../style/home.css">
</head>

<body>
    <div class="container mt-5">
        <h3>Transaction History</h3>
        <p>Your past transaction activities including returned, canceled, and other items</p>
        <?php if (empty($bookings)): ?>
            <p>No booking history found.</p>
        <?php else: ?>
            <?php foreach ($bookings as $booking): ?>
                <div class="booking-history-card d-flex align-items-center">
                    <?php if (!empty($booking['books_image'])): ?>
                        <img src="../uploaded_file/<?php echo htmlspecialchars($booking['books_image']); ?>" alt="Book Cover" class="img-thumbnail" style="width: 80px; height: 110px;">
                    <?php else: ?>
                        <div class="text-center book-image-container" style="width: 80px; height: 110px; background-color: rgba(232, 232, 232, 0.65); display: flex; align-items: center; justify-content: center; color: #555;">
                            <b>Book Cover</b>
                        </div>
                    <?php endif; ?>
                    <div class="flex-grow-1 spacing-custom">
                        <div class="book-title"><?php echo htmlspecialchars($booking['book_title']); ?></div>
                        <div class="book-details">
                            <p>Published Date: <?php echo htmlspecialchars($booking['copyright']); ?></p>
                            <p>ISBN: <?php echo htmlspecialchars($booking['ISBN']); ?></p>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="status-text"><?php echo htmlspecialchars($booking['status']); ?></div>
                        <div class="status-time">(<?php echo timeAgo(
                            $booking['status'],
                            $booking['cancel_date'],
                            $booking['reserved_date'],
                            $booking['borrowed_date'],
                            $booking['return_date']); ?>)
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php include '../student/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- Include SweetAlert2 for alerts -->
</body>

</html>