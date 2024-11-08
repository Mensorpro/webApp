<?php
include("db_connection.php");

function fetchAll($conn, $table) {
    $sql = "SELECT * FROM $table";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        return $result->fetch_all(MYSQLI_ASSOC);
    } else {
        return [];
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

function getCategory($conn, $category_id) {
    $sql = "
        SELECT 
            name
        FROM 
            categories
        WHERE
            id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return ['name' => 'Unknown'];
    }
}

function getUser($conn, $user_id) {
    $sql = "
        SELECT 
            name
        FROM 
            users
        WHERE
            id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return 'Unknown';
    }
}

function getCommentsCount($conn, $discussion_id) {
    $sql = "
        SELECT 
            COUNT(*) AS comments
        FROM 
            comments
        WHERE
            discussion_id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $discussion_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['comments'];
    } else {
        return 0;
    }
}


function getAnswersCount($conn, $question_id) {
    $sql = "
        SELECT 
            COUNT(*) AS answers
        FROM 
            answers
        WHERE
            question_id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $question_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['answers'];
    } else {
        return 0;
    }
}

function getLastActivity_D($conn, $discussion_id) {
    $sql = "
        SELECT 
            MAX(created_at) AS last_activity
        FROM (
            SELECT created_at FROM comments WHERE discussion_id = ?
            UNION ALL
            SELECT created_at FROM discussions WHERE id = ?
        ) AS combined
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $discussion_id, $discussion_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['last_activity'];
    } else {
        return 0;
    }
}

function getLastActivity_Q($conn, $question_id) {
    $sql = "
        SELECT 
            MAX(created_at) AS last_activity
        FROM (
            SELECT created_at FROM answers WHERE question_id = ?
            UNION ALL
            SELECT created_at FROM questions WHERE id = ?
        ) AS combined
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $question_id, $question_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['last_activity'];
    } else {
        return 0;
    }
}

function getQuestionInteractions($conn, $question_id) {
    $sql = "
        SELECT 
            COALESCE(SUM(CASE WHEN interaction_type = 'view' THEN 1 ELSE 0 END), 0) AS views,
            COALESCE(SUM(CASE WHEN interaction_type = 'like' THEN 1 ELSE 0 END), 0) AS likes,
            COALESCE(SUM(CASE WHEN interaction_type = 'share' THEN 1 ELSE 0 END), 0) AS shares
        FROM 
            question_interactions
        WHERE
            question_id = ?
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die('Prepare failed: ' . htmlspecialchars($conn->error));
    }

    $stmt->bind_param("i", $question_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result === false) {
        die('Execute failed: ' . htmlspecialchars($stmt->error));
    }

    $interactions = $result->fetch_assoc();
    if ($interactions === null) {
        return [
            'views' => 0,
            'likes' => 0,
            'shares' => 0
        ];
    }

    return $interactions;
}

// Fetch all questions from the database
$questions = fetchAll($conn, 'questions');


// Fetch all discussions from the database
$discussions = fetchAll($conn, 'discussions');

// Fetch all comments from the database
$comments = fetchAll($conn, 'comments');

// Fetch all answers from the database
$answers = fetchAll($conn, 'answers');

$conn->close();
?>
 

