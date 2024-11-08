<?php


// Include files in correct order
include("db_connection.php");
include("functions.php");  // Add this line before any function calls

// Add error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to handle JSON response
function sendJsonResponse($status, $message = '', $data = null) {
    $response = [
        'status' => $status,
        'message' => $message,
        'data' => $data
    ];
    
    header('Content-Type: application/json');
    $json_response = json_encode($response);
    if ($json_response === false) {
        // Handle JSON encoding error
        $json_error = json_last_error_msg();
        error_log("JSON encoding error: " . $json_error);
        echo json_encode([
            'status' => 'error',
            'message' => 'JSON encoding error: ' . $json_error
        ]);
    } else {
        echo $json_response;
    }
    exit;
}

// save question interactions to the database
function saveInteraction($conn, $question_id, $user_id, $interaction_type) {
    try {
        // For views, check if user has already viewed in this session
        if ($interaction_type === 'view') {
            // Check if this view is already recorded in session
            if (!isset($_SESSION['viewed_questions']) || !in_array($question_id, $_SESSION['viewed_questions'])) {
                $insert_sql = "
                    INSERT INTO question_interactions (question_id, user_id, interaction_type, created_at)
                    VALUES (?, ?, ?, NOW())
                ";
                $insert_stmt = $conn->prepare($insert_sql);
                if ($insert_stmt === false) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                if (!$insert_stmt->bind_param("iis", $question_id, $user_id, $interaction_type)) {
                    throw new Exception("Binding parameters failed: " . $insert_stmt->error);
                }
                
                if (!$insert_stmt->execute()) {
                    throw new Exception("Execute failed: " . $insert_stmt->error);
                }

                // Record this view in session
                if (!isset($_SESSION['viewed_questions'])) {
                    $_SESSION['viewed_questions'] = array();
                }
                $_SESSION['viewed_questions'][] = $question_id;
            }
            
            return getQuestionInteractions($conn, $question_id);
        }

        // For shares, check if user has already shared and only add if they haven't
        if ($interaction_type === 'share') {
            // Check if user has already shared
            $check_sql = "SELECT id FROM question_interactions 
                         WHERE question_id = ? AND user_id = ? AND interaction_type = 'share'";
            $check_stmt = $conn->prepare($check_sql);
            if (!$check_stmt || !$check_stmt->bind_param("ii", $question_id, $user_id)) {
                throw new Exception("Failed to prepare/bind share check");
            }
            
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            // Only insert if user hasn't shared before
            if ($result->num_rows === 0) {
                $insert_sql = "INSERT INTO question_interactions (question_id, user_id, interaction_type, created_at)
                              VALUES (?, ?, 'share', NOW())";
                $insert_stmt = $conn->prepare($insert_sql);
                if (!$insert_stmt || !$insert_stmt->bind_param("ii", $question_id, $user_id) || !$insert_stmt->execute()) {
                    throw new Exception("Failed to insert share interaction");
                }
            }
            
            // Get updated counts and return
            $counts = getQuestionInteractions($conn, $question_id);
            return [
                'likes' => intval($counts['likes']),
                'views' => intval($counts['views']),
                'shares' => intval($counts['shares'])
            ];
        }

        // For other interactions (like, share)
        $check_sql = "SELECT id FROM question_interactions 
                      WHERE question_id = ? AND user_id = ? AND interaction_type = ?";
        
        $stmt = $conn->prepare($check_sql);
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        if (!$stmt->bind_param("iis", $question_id, $user_id, $interaction_type)) {
            throw new Exception("Binding parameters failed: " . $stmt->error);
        }

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Interaction exists, delete it (toggle off)
            $delete_sql = "DELETE FROM question_interactions 
                          WHERE question_id = ? AND user_id = ? AND interaction_type = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            if (!$delete_stmt || !$delete_stmt->bind_param("iis", $question_id, $user_id, $interaction_type) || !$delete_stmt->execute()) {
                throw new Exception("Failed to delete interaction");
            }
        } else {
            // Interaction doesn't exist, insert it
            $insert_sql = "INSERT INTO question_interactions (question_id, user_id, interaction_type, created_at)
                          VALUES (?, ?, ?, NOW())";
            $insert_stmt = $conn->prepare($insert_sql);
            if (!$insert_stmt || !$insert_stmt->bind_param("iis", $question_id, $user_id, $interaction_type) || !$insert_stmt->execute()) {
                throw new Exception("Failed to insert interaction");
            }
        }

        // Get updated counts
        $counts = getQuestionInteractions($conn, $question_id);
        if (!$counts) {
            throw new Exception("Failed to get interaction counts");
        }

        return [
            'likes' => intval($counts['likes']),
            'views' => intval($counts['views']),
            'shares' => intval($counts['shares'])
        ];

    } catch (Exception $e) {
        error_log("Error in saveInteraction: " . $e->getMessage());
        throw $e;
    }
}

// save answers in the database
function saveAnswer($conn, $question_id, $user_id, $content, $parent_answer_id = null) {
    $sql = "
        INSERT INTO answers (question_id, user_id, content, parent_answer_id)
        VALUES (?, ?, ?, ?)
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        sendJsonResponse('error', "Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("iisi", $question_id, $user_id, $content, $parent_answer_id);
    if ($stmt->execute()) {
        sendJsonResponse('success', 'Answer saved successfully');
    } else {
        error_log("Insert failed: " . $stmt->error);
        sendJsonResponse('error', "Insert failed: " . $stmt->error);
    }
}

// save comment like in the database
function saveCommentLike($conn, $comment_id, $user_id, $question_id) {
    // Check if the like already exists
    $check_sql = "
        SELECT id 
        FROM comment_likes 
        WHERE comment_id = ? AND user_id = ? AND question_id = ?
    ";

    $stmt = $conn->prepare($check_sql);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        sendJsonResponse('error', "Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("iii", $comment_id, $user_id, $question_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Like exists, delete it
        $delete_sql = "
            DELETE FROM comment_likes 
            WHERE comment_id = ? AND user_id = ? AND question_id = ?
        ";

        $delete_stmt = $conn->prepare($delete_sql);
        if ($delete_stmt === false) {
            error_log("Prepare failed: " . $conn->error);
            sendJsonResponse('error', "Prepare failed: " . $conn->error);
        }

        $delete_stmt->bind_param("iii", $comment_id, $user_id, $question_id);
        if ($delete_stmt->execute()) {
            sendJsonResponse('success', 'Comment like removed successfully');
        } else {
            error_log("Delete failed: " . $delete_stmt->error);
            sendJsonResponse('error', "Delete failed: " . $delete_stmt->error);
        }
    } else {
        // Like does not exist, insert it
        $insert_sql = "
            INSERT INTO comment_likes (comment_id, user_id, question_id)
            VALUES (?, ?, ?)
        ";

        $insert_stmt = $conn->prepare($insert_sql);
        if ($insert_stmt === false) {
            error_log("Prepare failed: " . $conn->error);
            sendJsonResponse('error', "Prepare failed: " . $conn->error);
        }

        $insert_stmt->bind_param("iii", $comment_id, $user_id, $question_id);
        if ($insert_stmt->execute()) {
            sendJsonResponse('success', 'Comment like added successfully');
        } else {
            error_log("Insert failed: " . $insert_stmt->error);
            sendJsonResponse('error', "Insert failed: " . $insert_stmt->error);
        }
    }
}

function saveAnswerlike($conn, $answer_id, $user_id, $question_id) {
    // Check if the like already exists
    $check_sql = "
        SELECT id 
        FROM answer_likes 
        WHERE answer_id = ? AND user_id = ? AND question_id = ?
    ";

    $stmt = $conn->prepare($check_sql);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        sendJsonResponse('error', "Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("iii", $answer_id, $user_id, $question_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Like exists, delete it
        $delete_sql = "
            DELETE FROM answer_likes 
            WHERE answer_id = ? AND user_id = ? AND question_id = ?
        ";

        $delete_stmt = $conn->prepare($delete_sql);
        if ($delete_stmt === false) {
            error_log("Prepare failed: " . $conn->error);
            sendJsonResponse('error', "Prepare failed: " . $conn->error);
        }

        $delete_stmt->bind_param("iii", $answer_id, $user_id, $question_id);
        if ($delete_stmt->execute()) {
            sendJsonResponse('success', 'Answer like removed successfully');
        } else {
            error_log("Delete failed: " . $delete_stmt->error);
            sendJsonResponse('error', "Delete failed: " . $delete_stmt->error);
        }
    } else {
        // Like does not exist, insert it
        $insert_sql = "
            INSERT INTO answer_likes (answer_id, user_id, question_id)
            VALUES (?, ?, ?)
        ";

        $insert_stmt = $conn->prepare($insert_sql);
        if ($insert_stmt === false) {
            error_log("Prepare failed: " . $conn->error);
            sendJsonResponse('error', "Prepare failed: " . $conn->error);
        }

        $insert_stmt->bind_param("iii", $answer_id, $user_id, $question_id);
        if ($insert_stmt->execute()) {
            sendJsonResponse('success', 'Answer like added successfully');
        } else {
            error_log("Insert failed: " . $insert_stmt->error);
            sendJsonResponse('error', "Insert failed: " . $insert_stmt->error);
        }
    }
}

function getParentAnswerId($conn, $comment_id) {
    $sql = "
        SELECT parent_answer_id
        FROM answers
        WHERE id = ?
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        return null;
    }

    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['parent_answer_id'];
}

function getparentUserName($conn, $comment_id) {
    $sql = "
        SELECT users.username
        FROM answers
        JOIN users ON answers.user_id = users.id
        WHERE answers.id = ?
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        return null;
    }

    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['username'];
}

// Handle incoming requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['action'])) {
        sendJsonResponse('error', 'No action specified');
    }

    try {
        switch ($_POST['action']) {
            case 'save_answer':
                $question_id = $_POST['question_id'] ?? null;
                $user_id = $_POST['user_id'] ?? null;
                $content = $_POST['content'] ?? null;
                $parent_answer_id = $_POST['parent_answer_id'] ?? null;

                if (!$question_id) {
                    sendJsonResponse('error', 'Question ID is required');
                }
                if (!$user_id) {
                    sendJsonResponse('error', 'User ID is required');
                }
                if (!$content) {
                    sendJsonResponse('error', 'Content is required');
                }

                // If this is a reply, verify parent answer exists
                if ($parent_answer_id) {
                    $check_sql = "SELECT id FROM answers WHERE id = ?";
                    $check_stmt = $conn->prepare($check_sql);
                    $check_stmt->bind_param("i", $parent_answer_id);
                    $check_stmt->execute();
                    if (!$check_stmt->get_result()->num_rows) {
                        sendJsonResponse('error', 'Parent answer not found');
                    }
                }

                $sql = "INSERT INTO answers (question_id, user_id, content, parent_answer_id, created_at) 
                        VALUES (?, ?, ?, ?, NOW())";
                
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    sendJsonResponse('error', 'Failed to prepare statement');
                }

                $stmt->bind_param("iisi", $question_id, $user_id, $content, $parent_answer_id);
                if ($stmt->execute()) {
                    sendJsonResponse('success', 'Answer saved successfully');
                } else {
                    sendJsonResponse('error', 'Failed to save answer');
                }
                break;
            case 'save_interaction':
                if (!isset($_POST['question_id'], $_POST['user_id'], $_POST['interaction_type'])) {
                    sendJsonResponse('error', 'Missing required parameters');
                }

                $question_id = intval($_POST['question_id']);
                $user_id = intval($_POST['user_id']);
                $interaction_type = $_POST['interaction_type'];

                if (!$question_id || !$user_id) {
                    sendJsonResponse('error', 'Invalid question ID or user ID');
                }

                try {
                    $result = saveInteraction($conn, $question_id, $user_id, $interaction_type);
                    sendJsonResponse('success', 'Interaction saved successfully', [
                        'likes' => intval($result['likes']),
                        'views' => intval($result['views']),
                        'shares' => intval($result['shares'])
                    ]);
                } catch (Exception $e) {
                    error_log("Interaction error: " . $e->getMessage());
                    sendJsonResponse('error', 'Failed to process interaction: ' . $e->getMessage());
                }
                break;

            case 'reply_answer':
                $question_id = $_POST['question_id'] ?? null;
                $user_id = $_POST['user_id'] ?? null;
                $content = $_POST['content'] ?? null;
                $parent_answer_id = $_POST['parent_answer_id'] ?? null;

                if (!$question_id) {
                    throw new Exception('Question ID is required');
                } elseif (!$user_id) {
                    throw new Exception('User ID is required');
                } elseif (!$content) {
                    throw new Exception('Content is required');
                } elseif (!$parent_answer_id) {
                    throw new Exception('Parent answer ID is required');
                }

                saveAnswer($conn, $question_id, $user_id, $content, $parent_answer_id);
                break;
            case 'like_answer': 
    try {
        // Validate and sanitize inputs
        $answer_id = isset($_POST['answer_id']) ? intval($_POST['answer_id']) : 0;
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $question_id = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;

        if (!$answer_id || !$user_id || !$question_id) {
            throw new Exception('Missing required parameters');
        }

        // Check if like exists
        $check_sql = "SELECT id FROM answer_likes WHERE answer_id = ? AND user_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        if (!$check_stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $check_stmt->bind_param("ii", $answer_id, $user_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Like exists, remove it
            $delete_sql = "DELETE FROM answer_likes WHERE answer_id = ? AND user_id = ?";
            $stmt = $conn->prepare($delete_sql);
            $stmt->bind_param("ii", $answer_id, $user_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to remove like");
            }
            
            sendJsonResponse('success', 'Like removed', ['liked' => false]);
        } else {
            // Like doesn't exist, add it
            $insert_sql = "INSERT INTO answer_likes (answer_id, user_id, question_id) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("iii", $answer_id, $user_id, $question_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to add like: " . $stmt->error);
            }
            
            sendJsonResponse('success', 'Like added', ['liked' => true]);
        }
    } catch (Exception $e) {
        error_log("Like error: " . $e->getMessage());
        sendJsonResponse('error', 'Failed to process like: ' . $e->getMessage());
    }
    break;

            case 'get_parent_answer_id':
                $comment_id = $_POST['comment_id'] ?? null;

                if (!$comment_id) {
                    throw new Exception('Comment ID is required');
                }

                $parent_answer_id = getParentAnswerId($conn, $comment_id);
                sendJsonResponse('success', '', $parent_answer_id);
                break;

            case 'get_parent_user_name':
                $comment_id = $_POST['comment_id'] ?? null;

                if (!$comment_id) {
                    throw new Exception('Comment ID is required');
                }

                $parent_user_name = getparentUserName($conn, $comment_id);
                sendJsonResponse('success', '', $parent_user_name);
                break;

            case 'get_answer_likes':
                try {
                    $answer_id = isset($_POST['answer_id']) ? intval($_POST['answer_id']) : 0;
                    
                    if (!$answer_id) {
                        throw new Exception('Answer ID is required');
                    }

                    $likes = getAnswerLikes($conn, $answer_id);
                    sendJsonResponse('success', '', ['likes' => $likes]);
                } catch (Exception $e) {
                    error_log("Error getting likes: " . $e->getMessage());
                    sendJsonResponse('error', 'Failed to get likes count: ' . $e->getMessage());
                }
                break;
                
                default:
                throw new Exception('Invalid action');


        }
    } catch (Exception $e) {
        sendJsonResponse('error', $e->getMessage());
    }
}
?>
