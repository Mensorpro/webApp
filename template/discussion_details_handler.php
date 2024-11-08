<?php
// Prevent any output before headers
ob_start();

include("db_connection.php");
include_once("functions.php"); // Add this line if not already included

// Add error reporting but log to file instead of output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Function to handle JSON response
function sendJsonResponse($status, $message = '', $data = null) {
    // Clear any previous output
    ob_clean();
    
    // Set headers
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    $response = [
        'status' => $status,
        'message' => $message,
        'data' => $data
    ];
    
    try {
        $json_response = json_encode($response);
        if ($json_response === false) {
            throw new Exception('JSON encoding error: ' . json_last_error_msg());
        }
        echo $json_response;
    } catch (Exception $e) {
        error_log($e->getMessage());
        // Send a clean error response
        echo json_encode([
            'status' => 'error',
            'message' => 'Internal server error'
        ]);
    }
    exit;
}

// save discussion interactions to the database
function saveInteraction($conn, $discussion_id, $user_id, $interaction_type, $content = null) {
    // Check if the interaction already exists
    $check_sql = "
        SELECT id 
        FROM interactions 
        WHERE discussion_id = ? AND user_id = ? AND interaction_type = ?
    ";

    $stmt = $conn->prepare($check_sql);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        sendJsonResponse('error', "Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("iis", $discussion_id, $user_id, $interaction_type);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Interaction exists, delete it if it's a like
        if ($interaction_type === 'like') {
            $delete_sql = "
                DELETE FROM interactions 
                WHERE discussion_id = ? AND user_id = ? AND interaction_type = ?
            ";

            $delete_stmt = $conn->prepare($delete_sql);
            if ($delete_stmt === false) {
                error_log("Prepare failed: " . $conn->error);
                sendJsonResponse('error', "Prepare failed: " . $conn->error);
            }

            $delete_stmt->bind_param("iis", $discussion_id, $user_id, $interaction_type);
            if ($delete_stmt->execute()) {
                return true;
            } else {
                error_log("Delete failed: " . $delete_stmt->error);
                sendJsonResponse('error', "Delete failed: " . $delete_stmt->error);
            }
        }
    } else {
        // Interaction does not exist, insert it
        $insert_sql = "
            INSERT INTO interactions (discussion_id, user_id, interaction_type, content)
            VALUES (?, ?, ?, ?)
        ";

        $insert_stmt = $conn->prepare($insert_sql);
        if ($insert_stmt === false) {
            error_log("Prepare failed: " . $conn->error);
            sendJsonResponse('error', "Prepare failed: " . $conn->error);
        }

        $insert_stmt->bind_param("iiss", $discussion_id, $user_id, $interaction_type, $content);
        if ($insert_stmt->execute()) {
            return true;
        } else {
            error_log("Insert failed: " . $insert_stmt->error);
            sendJsonResponse('error', "Insert failed: " . $insert_stmt->error);
        }
    }
}

// save comments in the database
function saveComment($conn, $discussion_id, $user_id, $content, $parent_comment_id = null) {
    $sql = "
        INSERT INTO comments (discussion_id, user_id, content, parent_comment_id)
        VALUES (?, ?, ?, ?)
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        $error_message = "Prepare failed: " . $conn->error;
        error_log($error_message);
        sendJsonResponse('error', $error_message);
    }

    $stmt->bind_param("iisi", $discussion_id, $user_id, $content, $parent_comment_id);
    if ($stmt->execute()) {
        sendJsonResponse('success', 'Comment saved successfully');
    } else {
        $error_message = "Execute failed: " . $stmt->error;
        error_log($error_message);
        sendJsonResponse('error', $error_message);
    }
}

// save comment like in the database
function saveCommentLike($conn, $comment_id, $user_id, $discussion_id) {
    // Check if the like already exists
    $check_sql = "
        SELECT id 
        FROM comment_likes 
        WHERE comment_id = ? AND user_id = ? AND discussion_id = ?
    ";

    $stmt = $conn->prepare($check_sql);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        sendJsonResponse('error', "Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("iii", $comment_id, $user_id, $discussion_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Like exists, delete it
        $delete_sql = "
            DELETE FROM comment_likes 
            WHERE comment_id = ? AND user_id = ? AND discussion_id = ?
        ";

        $delete_stmt = $conn->prepare($delete_sql);
        if ($delete_stmt === false) {
            error_log("Prepare failed: " . $conn->error);
            sendJsonResponse('error', "Prepare failed: " . $conn->error);
        }

        $delete_stmt->bind_param("iii", $comment_id, $user_id, $discussion_id);
        if ($delete_stmt->execute()) {
            sendJsonResponse('success', 'Comment like removed successfully');
        } else {
            error_log("Delete failed: " . $delete_stmt->error);
            sendJsonResponse('error', "Delete failed: " . $delete_stmt->error);
        }
    } else {
        // Like does not exist, insert it
        $insert_sql = "
            INSERT INTO comment_likes (comment_id, user_id, discussion_id)
            VALUES (?, ?, ?)
        ";

        $insert_stmt = $conn->prepare($insert_sql);
        if ($insert_stmt === false) {
            error_log("Prepare failed: " . $conn->error);
            sendJsonResponse('error', "Prepare failed: " . $conn->error);
        }

        $insert_stmt->bind_param("iii", $comment_id, $user_id, $discussion_id);
        if ($insert_stmt->execute()) {
            sendJsonResponse('success', 'Comment like added successfully');
        } else {
            error_log("Insert failed: " . $insert_stmt->error);
            sendJsonResponse('error', "Insert failed: " . $insert_stmt->error);
        }
    }
}


function saveDiscussionInteraction($conn, $discussion_id, $user_id, $interaction_type) {
    try {
        // Begin transaction
        $conn->begin_transaction();

        // Check for existing interaction
        $check_sql = "SELECT id FROM discussion_interactions 
                      WHERE discussion_id = ? AND user_id = ? AND interaction_type = ?";
        
        $check_stmt = $conn->prepare($check_sql);
        if (!$check_stmt) {
            throw new Exception("Failed to prepare check statement: " . $conn->error);
        }
        
        $check_stmt->bind_param("iis", $discussion_id, $user_id, $interaction_type);
        if (!$check_stmt->execute()) {
            throw new Exception("Failed to execute check statement: " . $check_stmt->error);
        }
        
        $result = $check_stmt->get_result();
        $exists = $result->num_rows > 0;
        $check_stmt->close();

        if ($exists && $interaction_type === 'like') {
            // Delete existing interaction
            $delete_sql = "DELETE FROM discussion_interactions 
                          WHERE discussion_id = ? AND user_id = ? AND interaction_type = ?";
            
            $delete_stmt = $conn->prepare($delete_sql);
            if (!$delete_stmt) {
                throw new Exception("Failed to prepare delete statement: " . $conn->error);
            }
            
            $delete_stmt->bind_param("iis", $discussion_id, $user_id, $interaction_type);
            if (!$delete_stmt->execute()) {
                throw new Exception("Failed to execute delete statement: " . $delete_stmt->error);
            }
            $delete_stmt->close();
        } else if (!$exists) {
            // Insert new interaction
            $insert_sql = "INSERT INTO discussion_interactions 
                          (discussion_id, user_id, interaction_type, created_at)
                          VALUES (?, ?, ?, NOW())";
            
            $insert_stmt = $conn->prepare($insert_sql);
            if (!$insert_stmt) {
                throw new Exception("Failed to prepare insert statement: " . $conn->error);
            }
            
            $insert_stmt->bind_param("iis", $discussion_id, $user_id, $interaction_type);
            if (!$insert_stmt->execute()) {
                throw new Exception("Failed to execute insert statement: " . $insert_stmt->error);
            }
            $insert_stmt->close();
        }

        // Commit transaction
        $conn->commit();

        // Get updated counts
        $sql = "SELECT 
                    COALESCE(SUM(CASE WHEN interaction_type = 'view' THEN 1 ELSE 0 END), 0) AS views,
                    COALESCE(SUM(CASE WHEN interaction_type = 'like' THEN 1 ELSE 0 END), 0) AS likes,
                    COALESCE(SUM(CASE WHEN interaction_type = 'share' THEN 1 ELSE 0 END), 0) AS shares
                FROM discussion_interactions 
                WHERE discussion_id = ?";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Failed to prepare count statement: " . $conn->error);
        }
        
        $stmt->bind_param("i", $discussion_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute count statement: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $counts = $result->fetch_assoc();
        
        // Add the liked status
        $counts['liked'] = !$exists;
        
        return $counts;

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Error in saveDiscussionInteraction: " . $e->getMessage());
        throw $e;
    }
}

// Handle incoming requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['action'])) {
            throw new Exception('No action specified');
        }

        // Validate database connection
        if (!isset($conn) || $conn->connect_error) {
            throw new Exception('Database connection failed: ' . ($conn->connect_error ?? 'Unknown error'));
        }

        switch ($_POST['action']) {
            case 'save_interaction':
                $discussion_id = filter_input(INPUT_POST, 'discussion_id', FILTER_VALIDATE_INT);
                $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
                $interaction_type = filter_input(INPUT_POST, 'interaction_type', FILTER_SANITIZE_STRING);

                if (!$discussion_id || !$user_id || !$interaction_type) {
                    throw new Exception('Missing or invalid parameters');
                }

                $result = saveDiscussionInteraction($conn, $discussion_id, $user_id, $interaction_type);
                if (!$result) {
                    throw new Exception('Failed to save interaction');
                }
                sendJsonResponse('success', 'Interaction saved successfully', $result);
                break;

            case 'save_comment':
            case 'reply_comment':
                $discussion_id = intval($_POST['discussion_id'] ?? 0);
                $user_id = intval($_POST['user_id'] ?? 0);
                $content = trim($_POST['content'] ?? '');
                $parent_comment_id = $_POST['action'] === 'reply_comment' ? intval($_POST['parent_comment_id']) : null;

                if (!$discussion_id || !$user_id || !$content) {
                    sendJsonResponse('error', 'Missing required parameters');
                }

                saveComment($conn, $discussion_id, $user_id, $content, $parent_comment_id);
                break;

            case 'like_comment':
                $comment_id = intval($_POST['comment_id'] ?? 0);
                $user_id = intval($_POST['user_id'] ?? 0);
                $discussion_id = intval($_POST['discussion_id'] ?? 0);

                if (!$comment_id || !$user_id || !$discussion_id) {
                    sendJsonResponse('error', 'Missing required parameters');
                }

                saveCommentLike($conn, $comment_id, $user_id, $discussion_id);
                break;

            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        error_log("Error in discussion_details_handler: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        sendJsonResponse('error', 'An error occurred: ' . $e->getMessage());
    }
}
?>