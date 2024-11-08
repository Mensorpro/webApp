<?php
include("db_connection.php");
include("discussion_details_handler.php");
include("functions.php");

if (isset($_GET['discussion_id'])) {
    $discussion_id = $_GET['discussion_id'];
    $comments = getDiscussionComments($conn, $discussion_id);

    foreach ($comments as $comment) {
        echo '<div class="comment">';
        echo '<div class="comment-author">';
        echo '<img src="' . ($comment['author_avatar'] ?: 'default-avatar.png') . '" alt="Commenter avatar" class="avatar">';
        echo '<div class="author-details">';
        echo '<span class="author-name">' . htmlspecialchars($comment['author_name']) . '</span>';
        echo '<span class="comment-date"><span class="dot"></span>' . formatDate($comment['created_at']) . '</span>';
        echo '</div>';
        echo '</div>';
        echo '<div class="comment-content">';
        echo '<p>' . nl2br(htmlspecialchars($comment['content'])) . '</p>';
        echo '</div>';
        echo '<div class="comment-actions">';
        echo '<button class="like-btn" data-id="' . $comment['id'] . '">';
        echo '<i class="fas fa-heart"></i>';
        echo '<span class="count">' . formatNumber($comment['like_count']) . '</span>';
        echo '</button>';
        echo '<button class="reply-btn">Reply</button>';
        echo '</div>';
        echo '</div>';
    }
}
?>