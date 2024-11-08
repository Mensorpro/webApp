<?php
include("db_connection.php");
include("question_details_handler.php");
include("functions.php");

if (isset($_GET['question_id'])) {
    $question_id = $_GET['question_id'];
    $answers = getQuestionAnswers($conn, $question_id);

    foreach ($answers as $answer) {
        echo '<div class="comment">';
        echo '<div class="comment-author">';
        echo '<img src="' . ($answer['author_avatar'] ?: 'default-avatar.png') . '" alt="Commenter avatar" class="avatar">';
        echo '<div class="author-details">';
        echo '<span class="author-name">' . htmlspecialchars($answer['author_name']) . '</span>';
        echo '<span class="comment-date"><span class="dot"></span>' . formatDate($answer['created_at']) . '</span>';
        echo '</div>';
        echo '</div>';
        echo '<div class="comment-content">';
        echo '<p>' . nl2br(htmlspecialchars($answer['content'])) . '</p>';
        echo '</div>';
        echo '<div class="comment-actions">';
        echo '<button class="like-btn" data-id="' . $answer['id'] . '">';
        echo '<i class="fas fa-heart"></i>';
        echo '<span class="count">' . formatNumber(getAnswerLikes($conn, $answer['id'])) . '</span>';
        echo '</button>';
        echo '<button class="reply-btn">Reply</button>';
        echo '</div>';
        echo '</div>';
    }
}
?>
