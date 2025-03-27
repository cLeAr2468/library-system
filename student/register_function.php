<?php
// Set SMTP and smtp_port using ini_set
ini_set('SMTP', 'smtp.gmail.com');
ini_set('smtp_port', '587'); // Corrected port number
include "../component-library/connect.php";
$email = '';
$warning_msg = [];
$success_msg = [];
$playAudio = false;
if (isset($_POST['submit'])) {
    $user_id = $_POST['idNo'];
    $name = $_POST['firstName'];
    $middleName = $_POST['middleName'];
    $lastName = $_POST['lastName'];
    $patron_type = $_POST['patronType'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $pass = $_POST['password'];
    $cpass = $_POST['c_password'];
    // Check if student exists in student_list with matching details
    $check_student = $conn->prepare("SELECT * FROM student_list WHERE Student_No = ? AND Firstname = ? AND Middlename = ? AND Lastname = ? AND Gmail = ?");
    $check_student->execute([$user_id, $name, $middleName, $lastName, $email]);
    if ($check_student->rowCount() === 0) {
        $warning_msg[] = 'Student information does not match our records. Please check your details.';
    } else {
        // Fetch the student data
        $student_data = $check_student->fetch(PDO::FETCH_ASSOC);
        $course = $student_data['Course']; // Assuming 'Course' is the column name
        // Define valid courses and their corresponding patron types
        $valid_courses = [
            'BSIT' => 'student-BSIT',
            'BSCRIM' => 'student-BSCRIM',
            'BSA' => 'student-BSA',
            'BAT' => 'student-BAT',
            'BTLED' => 'student-BTLED',
            'BEED' => 'student-BEED',
            'BSF' => 'student-BSF',
            'BSABE' => 'student-BSABE'
        ];
        // Check if the selected patron type matches the course
        if (!array_key_exists($course, $valid_courses) || $valid_courses[$course] !== $patron_type) {
            $warning_msg[] = 'Selected Patron Type does not match the course of the student.';
        } else {
            // Check if user_id or email already exists in user_info
            $select_Student = $conn->prepare("SELECT * FROM user_info WHERE user_id = ? OR email = ?");
            $select_Student->execute([$user_id, $email]);
            if ($select_Student->rowCount() > 0) {
                $warning_msg[] = 'User ID or Email already exists';
            } elseif ($pass != $cpass) {
                $warning_msg[] = 'Passwords do not match!';
            } else {
                // Hash the password
                $pass = password_hash($pass, PASSWORD_DEFAULT);
                // Send email to admin
                $to = 'reyesjerald638@gmail.com';
                $subject = 'New User Registration Confirmation';
                $message = "User ID: $user_id\nFirst Name: $name\nMiddle Name: $middleName\nLast Name: $lastName\nPatron Type: $patron_type\nEmail: $email\nPlease approve this registration: http://localhost/library-system/admin_panel/admin_login.php?";
                
                if (mail($to, $subject, $message)) {
                    // Automatic reply to the user
                    $user_subject = 'Account Registration Confirmation';
                    $user_message = 'Your registration request has been received. Please wait for admin approval.';
                    $user_headers = 'From: reyesjerald638@gmail.com' . "\r\n" .
                        'Reply-To: reyesjerald638@gmail.com' . "\r\n" .
                        'X-Mailer: PHP/' . phpversion();
                    if (mail($email, $user_subject, $user_message, $user_headers)) {
                        $success_msg[] = 'Registration confirmation email sent. Please wait for admin approval.';
                        $playAudio = true;
                        // Insert student data into user_info
                        $insert_student = $conn->prepare("INSERT INTO user_info (user_id, first_name, middle_name, last_name, patron_type, email, address, password, images, status, account_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, 'pending', 'active')");
                        if ($insert_student->execute([$user_id, $name, $middleName, $lastName, $patron_type, $email, $address, $pass])) {
                            // Registration successful
                        } else {
                            $warning_msg[] = 'Failed to register student';
                        }
                    } else {
                        $warning_msg[] = 'Failed to send registration confirmation email';
                    }
                } else {
                    $warning_msg[] = 'Register Failed, please try again!';
                }
            }
        }
    }
}
?>