<?php
session_start();
include("db_connection.php");

// Check if category_id is set and valid
if (!isset($_POST['category_id']) || empty($_POST['category_id'])) {
    die("Error: Category is required.");
}

// Save the question in the database
$title = $_POST['subject'];
$category_id = $_POST['category_id'];
$content = $_POST['question-details'];
$user_id = $_SESSION['user_id'];

$sql = "INSERT INTO questions (subject, category_id, details, user_id) VALUES ('$title', '$category_id', '$content', '$user_id')";

if ($conn->query($sql) === TRUE) {
    echo "New question created successfully";
    header("Location: index.php");
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>