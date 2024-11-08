<?php
include("db_connection.php");

$email = $_POST['email'];
$password = $_POST['password'];

// Prepare the response array
$response = array();

// Fetch user from the database
$sql = "SELECT * FROM users WHERE email='$email'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    // Verify password
    if (password_verify($password, $user['password'])) {
        // Start session and set user session variables
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];

        // Successful login response
        $response['status'] = 'success';
        $response['message'] = 'Login successful';
    } else {
        // Incorrect password response
        $response['status'] = 'error';
        $response['message'] = 'Invalid password.';
    }
} else {
    // User not found response
    $response['status'] = 'error';
    $response['message'] = 'No user found with this email.';
}

$conn->close();

// Output response as JSON
echo json_encode($response);
?>
