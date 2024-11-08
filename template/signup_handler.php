<?php
include("db_connection.php");

// Always set the content type for JSON responses
// header('Content-Type: application/json');

$name = $_POST['name'];
$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

// Prepare an SQL statement to prevent SQL injection
$stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $name, $email, $password);

$response = [];

if ($stmt->execute()) {
    // Success response
    $response = [
        'status' => 'success',
        'message' => 'Registration successful. Redirecting to login...',
        'redirect' => 'login.php'
    ];
} else {
    // Error response
    $response = [
        'status' => 'error',
        'message' => 'Error: ' . $stmt->error
    ];
}

$stmt->close();
$conn->close();

// Send the JSON response
echo json_encode($response);
?>
