<?php
session_start();
require_once 'db_connection.php';
require_once 'functions.php';

// Create profile pictures directory if it doesn't exist
$uploadDir = '../uploads/profile_pictures/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $user_id = $_SESSION['user_id'] ?? null;
    
    if (!$user_id) {
        echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
        exit;
    }

    $file = $_FILES['profile_picture'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileError = $file['error'];
    $fileSize = $file['size'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Allowed file types
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($fileExt, $allowedExtensions)) {
        if ($fileError === 0) {
            if ($fileSize < 5000000) { // 5MB max
                // Create unique filename
                $newFileName = "profile_" . $user_id . "_" . time() . "." . $fileExt;
                $destination = $uploadDir . $newFileName;

                // Remove old profile picture if exists
                $sql = "SELECT profile_picture_path FROM users WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $oldPicture = $row['profile_picture_path'];
                    if ($oldPicture && file_exists("../" . $oldPicture)) {
                        unlink("../" . $oldPicture);
                    }
                }

                if (move_uploaded_file($fileTmpName, $destination)) {
                    // Update database with new profile picture path
                    $relativePath = 'uploads/profile_pictures/' . $newFileName;
                    $sql = "UPDATE users SET profile_picture_path = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("si", $relativePath, $user_id);
                    
                    if ($stmt->execute()) {
                        // Update session with new profile picture path
                        $_SESSION['profile_picture'] = $relativePath;
                        echo json_encode([
                            'status' => 'success', 
                            'message' => 'Profile picture updated successfully',
                            'path' => '../' . $relativePath
                        ]);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Database update failed']);
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to move uploaded file']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'File is too large']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error uploading file']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid file type']);
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'updateProfile') {
    $user_id = $_SESSION['user_id'] ?? null;
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';

    if (!$user_id) {
        echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
        exit;
    }

    $sql = "UPDATE users SET name = ?, email = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $name, $email, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update profile']);
    }
    exit;
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'changePassword') {
    $user_id = $_SESSION['user_id'] ?? null;
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$user_id) {
        echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
        exit;
    }

    // Verify current password
    $sql = "SELECT password FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!password_verify($current_password, $user['password'])) {
        echo json_encode(['status' => 'error', 'message' => 'Current password is incorrect']);
        exit;
    }

    if ($new_password !== $confirm_password) {
        echo json_encode(['status' => 'error', 'message' => 'New passwords do not match']);
        exit;
    }

    // Update password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $sql = "UPDATE users SET password = ? WHERE id = ?";
    $stmt->prepare($sql);
    $stmt->bind_param("si", $hashed_password, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Password updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update password']);
    }
    exit;
}
?>
