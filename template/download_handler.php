
<?php
session_start();
require_once 'db_connection.php';
require_once 'functions.php';

// Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

// Verify CSRF token
if (!isset($_SERVER['HTTP_X_CSRF_TOKEN']) || $_SERVER['HTTP_X_CSRF_TOKEN'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    exit('Invalid CSRF token');
}

$user_id = $_SESSION['user_id'];

try {
    // Fetch all user activity data
    $sql = "SELECT 
                'question' as type,
                subject as title,
                details as content,
                created_at
            FROM questions 
            WHERE user_id = ?
            UNION ALL
            SELECT 
                'answer' as type,
                '' as title,
                content,
                created_at
            FROM answers 
            WHERE user_id = ?
            UNION ALL
            SELECT 
                'comment' as type,
                '' as title,
                content,
                created_at
            FROM comments 
            WHERE user_id = ?
            UNION ALL
            SELECT 
                'discussion' as type,
                title,
                content,
                created_at
            FROM discussions 
            WHERE user_id = ?
            ORDER BY created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=my_activity_data.csv');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add CSV headers
    fputcsv($output, ['Type', 'Title', 'Content', 'Created At']);
    
    // Add data rows
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['type'],
            $row['title'],
            $row['content'],
            $row['created_at']
        ]);
    }
    
    fclose($output);
    exit();

} catch (Exception $e) {
    http_response_code(500);
    error_log("Download error: " . $e->getMessage());
    exit('Failed to generate download');
}