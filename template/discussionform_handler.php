<?php
session_start();
include 'db_connection.php';

// save the discussion in the database

$title = $_POST['subject'];
$category_id = $_POST['category_id'];
$content = $_POST['discussion-content'];
$user_id = $_SESSION['user_id'];

$sql = "INSERT INTO discussions (title, category_id, content, user_id) VALUES ('$title', '$category_id', '$content', '$user_id')";
if ($conn->query($sql) === TRUE) {
    echo "New discussion created successfully";
    header("Location: discussion.php");
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();

?>

