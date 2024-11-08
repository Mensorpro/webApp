<?php
// Prevent multiple declarations
if (!function_exists('getDiscussionDetails')) {

// Function to get discussion details
function getDiscussionDetails($conn, $discussion_id) {
    $sql = "
        SELECT 
            d.*,
            u.name as author_name,
            COALESCE(u.profile_picture_path, '') as author_avatar,
            COALESCE(di.views, 0) as view_count,
            COALESCE(di.likes, 0) as like_count
        FROM 
            discussions d
            LEFT JOIN users u ON d.user_id = u.id
            LEFT JOIN (
                SELECT 
                    discussion_id,
                    SUM(CASE WHEN interaction_type = 'view' THEN 1 ELSE 0 END) as views,
                    SUM(CASE WHEN interaction_type = 'like' THEN 1 ELSE 0 END) as likes
                FROM discussion_interactions
                GROUP BY discussion_id
            ) di ON d.id = di.discussion_id
        WHERE 
            d.id = ?
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        return null;
    }

    if (!$stmt->bind_param("i", $discussion_id)) {
        error_log("Binding parameters failed: " . $stmt->error);
        return null;
    }

    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        return null;
    }

    $result = $stmt->get_result();
    if ($result === false) {
        error_log("Getting result set failed: " . $stmt->error);
        return null;
    }

    return $result->fetch_assoc();
}
// Function to get comments
function getDiscussionComments($conn, $discussion_id) {
    $sql = "
        SELECT 
            c.*,
            u.name as author_name,
            COALESCE(u.profile_picture_path, '') as author_avatar,
            (SELECT COUNT(*) FROM comment_likes WHERE comment_id = c.id) as like_count
        FROM 
            comments c
            LEFT JOIN users u ON c.user_id = u.id
        WHERE 
            c.discussion_id = ?
        ORDER BY 
            c.created_at DESC
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        return [];
    }

    if (!$stmt->bind_param("i", $discussion_id)) {
        error_log("Binding parameters failed: " . $stmt->error);
        return [];
    }

    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        return [];
    }

    $result = $stmt->get_result();
    if ($result === false) {
        error_log("Getting result set failed: " . $stmt->error);
        return [];
    }
    
    $comments = [];
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }

    return $comments;
}

function getCommentLikes($conn, $comment_id) {
    $sql = "
        SELECT 
            COUNT(*) as likes
        FROM 
            comment_likes
        WHERE 
            comment_id = ?
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        return 0;
    }

    if (!$stmt->bind_param("i", $comment_id)) {
        error_log("Binding parameters failed: " . $stmt->error);
        return 0;
    }

    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        return 0;
    }

    $result = $stmt->get_result();
    if ($result === false) {
        error_log("Getting result set failed: " . $stmt->error);
        return 0;
    }

    $row = $result->fetch_assoc();
    return $row['likes'];
}

function getDiscussionInteractions($conn, $discussion_id) {
    $sql = "
        SELECT 
            COALESCE(SUM(CASE WHEN interaction_type = 'view' THEN 1 ELSE 0 END), 0) AS views,
            COALESCE(SUM(CASE WHEN interaction_type = 'like' THEN 1 ELSE 0 END), 0) AS likes,
            COALESCE(SUM(CASE WHEN interaction_type = 'share' THEN 1 ELSE 0 END), 0) AS shares
        FROM 
            discussion_interactions
        WHERE
            discussion_id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $discussion_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return [
            'views' => 0,
            'likes' => 0,
            'shares' => 0
        ];
    }
}

function getQuestionDetails($conn, $question_id) {
    $sql = "
        SELECT 
            q.*,
            u.name as author_name,
            COALESCE(u.profile_picture_path, '') as author_avatar,
            COALESCE(qi.views, 0) as view_count,
            COALESCE(qi.likes, 0) as like_count
        FROM 
            questions q
            LEFT JOIN users u ON q.user_id = u.id
            LEFT JOIN (
                SELECT 
                    question_id,
                    SUM(CASE WHEN interaction_type = 'view' THEN 1 ELSE 0 END) as views,
                    SUM(CASE WHEN interaction_type = 'like' THEN 1 ELSE 0 END) as likes
                FROM question_interactions
                GROUP BY question_id
            ) qi ON q.id = qi.question_id
        WHERE 
            q.id = ?
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        return null;
    }

    if (!$stmt->bind_param("i", $question_id)) {
        error_log("Binding parameters failed: " . $stmt->error);
        return null;
    }

    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        return null;
    }

    $result = $stmt->get_result();
    if ($result === false) {
        error_log("Getting result set failed: " . $stmt->error);
        return null;
    }

    return $result->fetch_assoc();
}

function getQuestionAnswers($conn, $question_id) {
    $sql = "
        SELECT 
            a.*,
            u.name as author_name,
            COALESCE(u.profile_picture_path, '') as author_avatar,
            p.author_name as parent_author_name,
            (SELECT COUNT(*) FROM answer_likes WHERE answer_id = a.id) as like_count
        FROM 
            answers a
            LEFT JOIN users u ON a.user_id = u.id
            LEFT JOIN (
                SELECT a2.id, u2.name as author_name 
                FROM answers a2 
                JOIN users u2 ON a2.user_id = u2.id
            ) p ON a.parent_answer_id = p.id
        WHERE 
            a.question_id = ?
        ORDER BY 
            a.parent_answer_id IS NULL DESC,
            a.created_at ASC
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        return [];
    }

    if (!$stmt->bind_param("i", $question_id)) {
        error_log("Binding parameters failed: " . $stmt->error);
        return [];
    }

    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        return [];
    }

    $result = $stmt->get_result();
    if ($result === false) {
        error_log("Getting result set failed: " . $stmt->error);
        return [];
    }
    
    $answers = [];
    while ($row = $result->fetch_assoc()) {
        $answers[] = $row;
    }

    return $answers;
}

function getQuestionInteractions($conn, $question_id) {
    $sql = "
        SELECT 
            COALESCE(SUM(CASE WHEN interaction_type = 'view' THEN 1 ELSE 0 END), 0) AS views,
            COALESCE(SUM(CASE WHEN interaction_type = 'like' THEN 1 ELSE 0 END), 0) AS likes,
            COALESCE(SUM(CASE WHEN interaction_type = 'share' THEN 1 ELSE 0 END), 0) AS shares
        FROM question_interactions
        WHERE question_id = ?
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return ['views' => 0, 'likes' => 0, 'shares' => 0];
    }

    if (!$stmt->bind_param("i", $question_id)) {
        error_log("Bind failed: " . $stmt->error);
        return ['views' => 0, 'likes' => 0, 'shares' => 0];
    }

    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        return ['views' => 0, 'likes' => 0, 'shares' => 0];
    }

    $result = $stmt->get_result();
    if (!$result) {
        error_log("Get result failed: " . $stmt->error);
        return ['views' => 0, 'likes' => 0, 'shares' => 0];
    }

    $row = $result->fetch_assoc();
    return [
        'views' => intval($row['views'] ?? 0),
        'likes' => intval($row['likes'] ?? 0),
        'shares' => intval($row['shares'] ?? 0)
    ];
}

function getAnswerLikes($conn, $answer_id) {
    $sql = "
        SELECT 
            COUNT(*) as likes
        FROM 
            answer_likes
        WHERE 
            answer_id = ?
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        return 0;
    }

    if (!$stmt->bind_param("i", $answer_id)) {
        error_log("Binding parameters failed: " . $stmt->error);
        return 0;
    }

    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        return 0;
    }

    $result = $stmt->get_result();
    if ($result === false) {
        error_log("Getting result set failed: " . $stmt->error);
        return 0;
    }

    $row = $result->fetch_assoc();
    return $row['likes'];
}

function isQuestionLikedByUser($conn, $question_id, $user_id) {
    if (!$user_id) return false;
    
    $sql = "SELECT COUNT(*) as liked 
            FROM question_interactions 
            WHERE question_id = ? AND user_id = ? AND interaction_type = 'like'";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("ii", $question_id, $user_id);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        return false;
    }
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['liked'] > 0;
}

// Add this new function after isQuestionLikedByUser

function isAnswerLikedByUser($conn, $answer_id, $user_id) {
    if (!$user_id) return false;
    
    $sql = "SELECT COUNT(*) as liked 
            FROM answer_likes 
            WHERE answer_id = ? AND user_id = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("ii", $answer_id, $user_id);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        return false;
    }
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['liked'] > 0;
}

function isDiscussionLikedByUser($conn, $discussion_id, $user_id) {
    if (!$user_id) return false;
    
    $sql = "SELECT COUNT(*) as liked 
            FROM discussion_interactions 
            WHERE discussion_id = ? AND user_id = ? AND interaction_type = 'like'";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("ii", $discussion_id, $user_id);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        return false;
    }
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['liked'] > 0;
}

function isCommentLikedByUser($conn, $comment_id, $user_id) {
    if (!$user_id) return false;
    
    $sql = "SELECT COUNT(*) as liked 
            FROM comment_likes 
            WHERE comment_id = ? AND user_id = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("ii", $comment_id, $user_id);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        return false;
    }
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['liked'] > 0;
}

// Format numbers function
function formatNumber($num) {
    if($num >= 1000) {
        return round($num/1000, 1) . 'K';
    }else if($num >= 1000000) {
        return round($num/1000000, 1) . 'M';
    }else if($num >= 1000000000) {
        return round($num/1000000000, 1) . 'B';
    }

    return $num;
}

function simpleformatDate($date) {
    $timestamp = strtotime($date);
    $diff = time() - $timestamp;
    
    if($diff < 60) {
        return $diff . " seconds ago";
    } elseif($diff < 3600) {
        return round($diff/60) . " minutes ago";
    } elseif($diff < 86400) {
        return round($diff/3600) . " hours ago";
    } else {
        return date("M j, Y", $timestamp);
    }
}

function formatDate($date) {
    // Set the default timezone to East African Time (EAT)
    date_default_timezone_set('Africa/Nairobi');
    
    // Convert the date to a timestamp
    $timestamp = strtotime($date);
    
    // Get the current time
    $current_time = time();
    
    // Calculate the time difference
    $time_difference = $current_time - $timestamp;

    // Check if the date is in the future
    if ($time_difference < 0) {
        return 'In the future';
    }

    // Calculate the time difference in human-readable format
    if ($time_difference < 60) {
        return $time_difference . ' seconds ago';
    } elseif ($time_difference < 3600) {
        $minutes = floor($time_difference / 60);
        return $minutes . ' minutes ago';
    } elseif ($time_difference < 86400) {
        $hours = floor($time_difference / 3600);
        return $hours . ' hours ago';
    } elseif ($time_difference < 604800) {
        $days = floor($time_difference / 86400);
        return $days . ' days ago';
    } elseif ($time_difference < 2592000) {
        $weeks = floor($time_difference / 604800);
        return $weeks . ' weeks ago';
    } elseif ($time_difference < 31536000) {
        $months = floor($time_difference / 2592000);
        return $months . ' months ago';
    } else {
        $years = floor($time_difference / 31536000);
        return $years . ' years ago';
    }
}

} // End of function_exists check
?>