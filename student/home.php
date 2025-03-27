<?php
include '../component-library/connect.php';
include '../student/side_navbars.php';
try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $user_name, $user_password);
    // Set PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Fetch announcements
    $home = $conn->query("SELECT * FROM announcement ORDER BY id DESC");
    $announcements = $home->fetchAll(PDO::FETCH_ASSOC);
    // Fetch books for carousel
    $sql = "SELECT title, books_image, id FROM books"; // Changed books_title to title and call_no to id
    $home = $conn->query($sql);
    $books = $home->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.lineicons.com/4.0/lineicons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .custom-bg {
            background-image: url('../images/bg.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            min-height: 100vh;
            background-color: #f0f0f0;
        }

        @keyframes marquee {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(-50%);
            }
        }

        .animate-marquee-slow {
            animation: marquee 30s linear infinite;
        }

        .animate-marquee-medium {
            animation: marquee 25s linear infinite;
        }

        .animate-marquee-fast {
            animation: marquee 20s linear infinite;
        }

        .hover-pause:hover {
            animation-play-state: paused;
        }
    </style>
</head>

<body class="custom-bg">
    <div class="container mx-auto py-8 px-[5%]">
        <div class="flex flex-col md:flex-row md:space-x-4">
            <!-- Left side: Announcements section -->
            <div class="w-full md:w-1/2">
                <div class="border border-gray-200 rounded p-3 bg-gray-100/10">
                    <h2 class="text-gray-800 text-center font-bold text-2xl mb-5 mt-2">Announcements</h2>
                    <div class="max-h-96 overflow-y-auto">
                        <?php if (!empty($announcements)): ?>
                            <?php foreach ($announcements as $announcement): ?>
                                <div class="border border-gray-200 rounded p-3 mb-4 relative">
                                    <div class="flex justify-between">
                                        <div class="text-left">
                                            <p class="posted-by"><strong> <?php echo htmlspecialchars($announcement['title']); ?></strong></p>
                                        </div>
                                        <span class="absolute top-2 right-3 text-sm text-gray-600">
                                            <?php
                                            $post_time = new DateTime($announcement['date']);
                                            $now = new DateTime();
                                            $interval = $post_time->diff($now);
                                            if ($interval->y > 0) {
                                                echo $interval->y . ' year' . ($interval->y > 1 ? 's' : '') . ' ago';
                                            } elseif ($interval->m > 0) {
                                                echo $interval->m . ' month' . ($interval->m > 1 ? 's' : '') . ' ago';
                                            } elseif ($interval->d > 0) {
                                                echo $interval->d . ' day' . ($interval->d > 1 ? 's' : '') . ' ago';
                                            } elseif ($interval->h > 0) {
                                                echo $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' ago';
                                            } elseif ($interval->i > 0) {
                                                echo $interval->i . ' minute' . ($interval->i > 1 ? 's' : '') . ' ago';
                                            } else {
                                                echo 'Just now';
                                            }
                                            ?>
                                        </span>
                                    </div>
                                    <p class="announcement-text text-left mt-5"><?php echo htmlspecialchars($announcement['message']); ?></p>
                                    <?php if (!empty($announcement['image'])): ?>
                                        <div class="announcement-image text-left">
                                            <img src="../uploaded_file/<?php echo htmlspecialchars($announcement['image']); ?>" alt="Announcement Image" class="w-[150px] w-[130px] rounded">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No announcements available.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- Right side: Service Hours section -->
            <div class="w-full md:w-1/2 flex justify-center items-center">
                <div class="border border-transparent bg-gray-100/10 rounded p-3 backdrop-blur-md w-full max-w-md">
                    <div class="text-center mt-3 font-semibold">
                        <div class="bg-gray-100/10 px-3 py-2" id="datetime-date"></div>
                        <div class="bg-gray-100/10 px-3 py-2" id="datetime-time"></div>
                    </div>
                    <div class="flex justify-center mt-4 border-t border-gray-300/50 py-2">
                        <div class="text-center">
                            <h2 id="service-hours" class="mb-4 text-2xl font-bold text-red-600">SERVICE HOURS</h2>
                            <h5 class="font-bold">Monday - Friday</h5>
                            <p>9:00 AM - 5:00 PM</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- New Arrivals Section with Marquee Effect -->
        <div class="container mx-auto mt-5">
            <div class="border border-transparent bg-white rounded p-3 backdrop-blur-md">
                <h2 class="text-lg font-bold mb-4 text-center text-black">New Arrivals</h2>

                <!-- Large screens (xl) -->
                <div class="hidden xl:block overflow-hidden relative">
                    <div class="flex whitespace-nowrap animate-marquee-slow hover-pause">
                        <?php
                        // Display all books twice for continuous scrolling effect
                        for ($i = 0; $i < 2; $i++):
                            foreach ($books as $book):
                        ?>
                                <div class="inline-block flex-shrink-0 w-[100px] h-[140px] mx-3">
                                    <a href="studbook_detail.php?id=<?php echo urlencode($book['id']); ?>" title="<?php echo htmlspecialchars($book['title']); ?>">
                                        <?php if (!empty($book['books_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($book['books_image']); ?>"
                                                alt="<?php echo htmlspecialchars($book['title']); ?>"
                                                class="w-full h-full object-cover rounded shadow-sm hover:shadow-md transition-all duration-300" />
                                        <?php else: ?>
                                            <div class="w-full h-full border border-gray-300 rounded bg-gray-200 flex items-center justify-center text-gray-500 hover:bg-gray-300 transition-all duration-300">
                                                <b>Book Cover</b>
                                            </div>
                                        <?php endif; ?>
                                    </a>
                                </div>
                        <?php
                            endforeach;
                        endfor;
                        ?>
                    </div>
                </div>

                <!-- Medium screens (md) -->
                <div class="hidden md:block xl:hidden overflow-hidden relative">
                    <div class="flex whitespace-nowrap animate-marquee-medium hover-pause">
                        <?php
                        // Display all books twice for continuous scrolling effect
                        for ($i = 0; $i < 2; $i++):
                            foreach ($books as $book):
                        ?>
                                <div class="inline-block flex-shrink-0 w-[100px] h-[140px] mx-2">
                                    <a href="studbook_detail.php?id=<?php echo urlencode($book['id']); ?>" title="<?php echo htmlspecialchars($book['title']); ?>">
                                        <?php if (!empty($book['books_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($book['books_image']); ?>"
                                                alt="<?php echo htmlspecialchars($book['title']); ?>"
                                                class="w-full h-full object-cover rounded shadow-sm hover:shadow-md transition-all duration-300" />
                                        <?php else: ?>
                                            <div class="w-full h-full border border-gray-300 rounded bg-gray-200 flex items-center justify-center text-gray-500 hover:bg-gray-300 transition-all duration-300">
                                                <b>Book Cover</b>
                                            </div>
                                        <?php endif; ?>
                                    </a>
                                </div>
                        <?php
                            endforeach;
                        endfor;
                        ?>
                    </div>
                </div>

                <!-- Small screens (mobile) -->
                <div class="md:hidden overflow-hidden relative">
                    <div class="flex whitespace-nowrap animate-marquee-fast hover-pause">
                        <?php
                        // Display all books twice for continuous scrolling effect
                        for ($i = 0; $i < 2; $i++):
                            foreach ($books as $book):
                        ?>
                                <div class="inline-block flex-shrink-0 w-[100px] h-[140px] mx-1.5">
                                    <a href="studbook_detail.php?id=<?php echo urlencode($book['id']); ?>" title="<?php echo htmlspecialchars($book['title']); ?>">
                                        <?php if (!empty($book['books_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($book['books_image']); ?>"
                                                alt="<?php echo htmlspecialchars($book['title']); ?>"
                                                class="w-full h-full object-cover rounded shadow-sm hover:shadow-md transition-all duration-300" />
                                        <?php else: ?>
                                            <div class="w-full h-full border border-gray-300 rounded bg-gray-200 flex items-center justify-center text-gray-500 hover:bg-gray-300 transition-all duration-300">
                                                <b>Book Cover</b>
                                            </div>
                                        <?php endif; ?>
                                    </a>
                                </div>
                        <?php
                            endforeach;
                        endfor;
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include '../student/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Animation for service hours title
            const serviceHoursTitle = document.getElementById("service-hours");
            const titleText = serviceHoursTitle.textContent;
            serviceHoursTitle.textContent = ''; // Clear the title text initially
            let titleIndex = 0;
            // Function to display the title one letter at a time
            function displayTitle() {
                if (titleIndex < titleText.length) {
                    serviceHoursTitle.textContent += titleText[titleIndex];
                    titleIndex++;
                } else {
                    clearInterval(titleInterval);
                    serviceHoursTitle.style.display = 'block'; // Show the title after it's fully displayed
                    // Set a timeout to clear the title and restart the display
                    setTimeout(() => {
                        serviceHoursTitle.textContent = ''; // Clear the title
                        titleIndex = 0; // Reset index to start from the beginning
                        titleInterval = setInterval(displayTitle, 500); // Restart the display
                    }, 500); // Delay before clearing the title (1 second)
                }
            }

            // Start displaying the title letter by letter
            let titleInterval = setInterval(displayTitle, 500); // Display each letter every 0.5 seconds

            // Update the current date and time
            function updateTime() {
                const date = new Date();
                const options = {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                };
                const time = date.toLocaleTimeString();
                const formattedDate = date.toLocaleDateString(undefined, options);
                // Update the content of the separate IDs
                document.getElementById('datetime-date').innerHTML = formattedDate;
                document.getElementById('datetime-time').innerHTML = time;
            }

            // Update the time every second
            setInterval(updateTime, 1000);
        });
    </script>
</body>

</html>