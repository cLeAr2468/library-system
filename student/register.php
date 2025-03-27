<?php
include "../component-library/connect.php";
include "../student/register_function.php";
?>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <link rel="icon" type="image/png" href="../images/logo.png" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Registration Form</title>
  <link href="/src/output.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-teal-500">
  <div class="w-full max-w-4xl p-8 bg-white rounded-md shadow-lg">
    <h3 class="text-4xl text-center font-bold mb-8">
      <span class="text-black">Create</span> <span class="text-teal-600">Account!</span>
    </h3>
    <form action="" method="post" enctype="multipart/form-data" class="register">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="mb-4">
          <label class="block text-lg font-bold text-gray-700" for="idNo">ID No</label>
          <input name="idNo" type="text" placeholder="ID Number" required
            class="w-full px-3 py-2 text-lg leading-tight text-black bg-teal-50 border rounded shadow focus:outline-none focus:shadow-outline" />
        </div>
        <div class="mb-4">
          <label class="block text-lg font-bold text-gray-700" for="firstName">First Name</label>
          <input name="firstName" type="text" placeholder="First Name" required
            class="w-full px-3 py-2 text-lg leading-tight text-black bg-teal-50 border rounded shadow focus:outline-none focus:shadow-outline" />
        </div>
        <div class="mb-4">
          <label class="block text-lg font-bold text-gray-700" for="middleName">Middle Name</label>
          <input name="middleName" type="text" placeholder="Middle Name"
            class="w-full px-3 py-2 text-lg leading-tight text-black bg-teal-50 border rounded shadow focus:outline-none focus:shadow-outline" />
        </div>
        <div class="mb-4">
          <label class="block text-lg font-bold text-gray-700" for="lastName">Last Name</label>
          <input name="lastName" type="text" placeholder="Last Name" required
            class="w-full px-3 py-2 text-lg leading-tight text-black bg-teal-50 border rounded shadow focus:outline-none focus:shadow-outline" />
        </div>
      </div>
      <div class="mb-4">
        <label class="block text-lg font-bold text-gray-700" for="patronType">Patron Type</label>
        <select name="patronType" required
          class="w-full px-3 py-2 text-lg leading-tight text-black bg-teal-50 border rounded shadow focus:outline-none focus:shadow-outline">
          <option value="" disabled selected>Choose Patron type</option>
          <option value="student-BSA">student-BSA</option>
          <option value="student-BSCRIM">student-BSCRIM</option>
          <option value="student-BAT">student-BAT</option>
          <option value="student-BSIT">student-BSIT</option>
          <option value="student-BTLED">student-BTLED</option>
          <option value="student-BEED">student-BEED</option>
          <option value="student-BSF">student-BSF</option>
          <option value="student-BSABE">student-BSABE</option>
          <option value="Faculty">Faculty</option>
        </select>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="mb-4">
          <label class="block text-lg font-bold text-gray-700" for="email">Email</label>
          <input name="email" type="email" placeholder="Email" required
            class="w-full px-3 py-2 text-lg leading-tight text-black bg-teal-50 border rounded shadow focus:outline-none focus:shadow-outline" />
        </div>
        <div class="mb-4">
          <label class="block text-lg font-bold text-gray-700" for="address">Address</label>
          <input name="address" type="text" placeholder="Address" required
            class="w-full px-3 py-2 text-lg leading-tight text-black bg-teal-50 border rounded shadow focus:outline-none focus:shadow-outline" />
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="mb-4">
          <label class="block text-lg font-bold text-gray-700" for="password">Password</label>
          <input name="password" type="password" placeholder="******************" required
            class="w-full px-3 py-2 text-lg leading-tight text-black bg-teal-50 border rounded shadow focus:outline-none focus:shadow-outline" />
        </div>
        <div class="mb-4">
          <label class="block text-lg font-bold text-gray-700" for="c_password">Confirm Password</label>
          <input name="c_password" type="password" placeholder="******************" required
            class="w-full px-3 py-2 text-lg leading-tight text-black bg-teal-50 border rounded shadow focus:outline-none focus:shadow-outline" />
        </div>
      </div>
      <div class="text-center">
        <button type="submit" name="submit"
          class="w-full px-4 py-2 font-bold text-white bg-teal-500 rounded-full hover:bg-teal-600 focus:outline-none focus:shadow-outline">
          Register Account
        </button>
      </div>
      <hr class="my-6 border-t" />
      <div class="text-center">
        <a class="inline text-lg text-black" href="./index.html">Already have an account?</a>
        <a class="inline text-lg text-teal-500 hover:underline ml-1" href="./stud_login.php"><strong>Login!</strong></a>
      </div>
    </form>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($warning_msg)): ?>
                Swal.fire({
                    icon: 'warning',
                    title: 'Oops...',
                    text: '<?php echo implode(', ', $warning_msg); ?>',
                    confirmButtonText: 'OK'
                });
            <?php elseif (!empty($success_msg)): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: '<?php echo implode(', ', $success_msg); ?>',
                    confirmButtonText: 'OK'
                }).then(() => {
                    // Play audio if the admin notification was sent
                    <?php if ($playAudio): ?>
                        setTimeout(() => {
                            document.getElementById("notifSound").play();
                        },); // 500 milliseconds delay
                    <?php endif; ?>
                });
            <?php endif; ?>
        });
    </script>
</body>
</html>