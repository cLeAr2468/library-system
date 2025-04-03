    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            primary: '#00a000',
                            secondary: '#333333',
                        }
                    }
                }
            }
        </script>
<footer class="bg-secondary text-white mt-12 py-8">
        <div class="container mx-auto px-8">
            <div class="flex grid grid-cols-1 md:grid-cols-3 gap-8 lg:mx-auto">
                <div>
                    <h3 class="text-lg font-semibold mb-4">About Us</h3>
                    <p class="text-gray-300">The NwSSU Library Management System provides access to thousands of books, journals, and digital resources to support your academic journey.</p>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-4">Contact Information</h3>
                    <ul class="space-y-2 text-gray-300">
                        <li class="flex items-center">
                            <i class="fas fa-map-marker-alt w-5 text-center mr-2"></i> Nwssu San Jorge Campus
                        </li>
                        <li class="flex items-center">
                            <i class="fab fa-facebook-f w-5 text-center mr-2"><a href="https://www.facebook.com/NwSSU.sjclibrary?mibextid=LQQJ4d" target="_blank"></i> Nwssu - San Jorge Campus Online Library
                        </li>
                        <li class="flex items-center mt-2">
                            <i class="fas fa-envelope w-5 text-center mr-2"></i> nwssulibrarysjcampus@gmail.com
                        </li>
                    </ul>
                </div>
            
            </div>
            
            <div class="border-t border-gray-700 mt-8 pt-6 text-center text-gray-300">
                <p>&copy; <?php echo date('Y'); ?> Developed By: Jerald Reyes | NwSSU Library Management System. All rights reserved.</p>
            </div>
        </div>
    </footer>