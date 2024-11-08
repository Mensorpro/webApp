<?php
// Start session must be at the very top
session_start();

include("db_connection.php");
include("discussion_details_handler.php");

// Get discussion ID from URL
$discussion_id = isset($_GET['id']) ? $_GET['id'] : 0;
include("functions.php");

$discussion = getDiscussionDetails($conn, $discussion_id);
$comments = getDiscussionComments($conn, $discussion_id);

// Track view
if (isset($_SESSION['user_id'])) {
    saveDiscussionInteraction($conn, $discussion_id, $_SESSION['user_id'], 'view');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discussion Thread</title>
    <link rel="stylesheet" href="../css/disscution_details.css">
    <link rel="stylesheet" href="../css/navbar_footer.css">
    <link rel="stylesheet" href="../css/shared_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
</head>
<body>
    <!-- Header -->
    <header class="head">
        <div class="logo">
            <a href="index.php">
                <img src="logo.png" class="logo" alt="Community Help Center Logo" />
            </a>
        </div>
        <?php include("nav_links.php"); ?>
    </header>

    <!-- Display Discussion -->
    <div class="container">
        <div class="left-column">
            <?php if ($discussion): ?>
                <div class="discussion-topic">
                    <h1><?php echo htmlspecialchars($discussion['title']); ?></h1>
                    
                    <div class="topic-metadata">
                        <div class="author-info">
                            <img src="<?php 
                                if (!empty($discussion['author_avatar'])) {
                                    echo '../' . $discussion['author_avatar'];
                                } else {
                                    echo '../images/default-avatar.png';
                                }
                            ?>" alt="Author avatar" class="avatar">
                            <div class="author-details">
                                <span class="author-name"><?php echo htmlspecialchars($discussion['author_name']); ?></span>
                                <span class="post-date">Posted on <?php echo simpleformatDate($discussion['created_at']); ?></span>
                            </div>
                        </div>
                        <div class="topic-stats">
                            <span class="views"><?php echo formatNumber(getDiscussionInteractions($conn, $discussion_id)['views']); ?> views</span>
                            <span class="comments-count"><?php echo count($comments); ?> comments</span>
                        </div>
                    </div>

                    <div class="topic-content">
                        <p><?php echo nl2br(htmlspecialchars($discussion['content'])); ?></p>
                    </div>

                    <div class="topic-footer">
                        <div class="interaction-buttons">
                            <?php 
                            $interactions = getDiscussionInteractions($conn, $discussion_id);
                            $isLiked = isDiscussionLikedByUser($conn, $discussion_id, $_SESSION['user_id'] ?? 0);
                            ?>
                            <button class="like-btn <?php echo $isLiked ? 'active' : ''; ?>" data-type="like">
                                <i class="fas fa-heart"></i>
                                <span class="count"><?php echo formatNumber($interactions['likes'] ?? 0); ?></span>
                            </button>
                            <button class="share-btn" data-type="share">
                                <i class="fas fa-share"></i>
                                <span class="share-count"><?php echo formatNumber($interactions['shares'] ?? 0); ?></span>
                            </button>
                            <span class="view-count">
                                <i class="fas fa-eye"></i>
                                <span><?php echo formatNumber($interactions['views'] ?? 0); ?></span> views
                            </span>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="error-message">Discussion not found.</div>
            <?php endif; ?>

            <!-- Add Comment Box -->
            <div class="add-comment-box">
                <h3>Join the Discussion</h3>
                <div class="comment-input-wrapper">
                    <textarea id="comment-text" placeholder="Add to the discussion"></textarea>
                    <button class="post-btn" id="post-comment-btn">Post Comment</button>
                    
                </div>
            </div>
        </div>

        <div class="right-column">
            <!-- Comments Section -->
            <div class="comments-section">
                <div class="comment-header">
                    <h2>Comments</h2>
                    <select class="sort-comments">
                        <option>Most Recent</option>
                        <option>Most Liked</option>
                        <option>Oldest</option>
                    </select>
                </div>

                <!-- Comments List -->
                <div class="comments-list">
                    <?php if (empty($comments)): ?>
                        <div class="no-comments">No comments yet. Be the first to comment!</div>
                    <?php endif; ?>
                    <?php foreach ($comments as $comment):
                        // Only show main comments (not replies) at first level
                        if ($comment['parent_comment_id'] === null):
                    ?>
                        <div class="comment" data-comment-id="<?php echo $comment['id']; ?>">
                            <div class="comment-author">
                                <img src="<?php 
                                    if (!empty($comment['author_avatar'])) {
                                        echo '../' . $comment['author_avatar'];
                                    } else {
                                        echo '../images/default-avatar.png';
                                    }
                                ?>" alt="Commenter avatar" class="avatar">
                                <div class="author-details">
                                    <span class="author-name"><?php echo htmlspecialchars($comment['author_name']); ?></span>
                                    <span class="comment-date">
                                        <span class="dot"></span>
                                        <?php echo formatDate($comment['created_at']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="comment-content">
                                <p><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                            </div>
                            <div class="comment-actions">
                                <button class="like-btn <?php echo isCommentLikedByUser($conn, $comment['id'], $_SESSION['user_id'] ?? 0) ? 'active' : ''; ?>" 
                                        data-id="<?php echo $comment['id']; ?>"
                                        data-discussion-id="<?php echo $discussion_id; ?>">
                                    <i class="fas fa-heart"></i>
                                    <span class="count"><?php echo formatNumber(getCommentLikes($conn, $comment['id'])); ?></span>
                                </button>
                                <button class="reply-btn" data-comment-id="<?php echo $comment['id']; ?>" 
                                        data-author="<?php echo htmlspecialchars($comment['author_name']); ?>">Reply</button>
                                <?php
                                // Count replies for this comment
                                $replyCount = 0;
                                foreach ($comments as $reply) {
                                    if ($reply['parent_comment_id'] === $comment['id']) $replyCount++;
                                }
                                if ($replyCount > 0):
                                ?>
                                <button class="toggle-replies" data-comment-id="<?php echo $comment['id']; ?>">
                                    <i class="fas fa-caret-down"></i> 
                                    Show <?php echo $replyCount; ?> <?php echo $replyCount === 1 ? 'reply' : 'replies'; ?>
                                </button>
                                <?php endif; ?>
                            </div>

                            <!-- Replies section -->
                            <div class="replies" id="replies-<?php echo $comment['id']; ?>" style="display: none;">
                                <?php foreach ($comments as $reply):
                                    if ($reply['parent_comment_id'] === $comment['id']):
                                ?>
                                    <div class="reply">
                                        <div class="comment-author">
                                            <img src="<?php 
                                                if (!empty($reply['author_avatar'])) {
                                                    echo '../' . $reply['author_avatar'];
                                                } else {
                                                    echo '../images/default-avatar.png';
                                                }
                                            ?>" alt="Replier avatar" class="avatar">
                                            <div class="author-details">
                                                <span class="author-name"><?php echo htmlspecialchars($reply['author_name']); ?></span>
                                                <span class="comment-date">
                                                    <span class="dot"></span>
                                                    <?php echo formatDate($reply['created_at']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="comment-content">
                                            <p><span class="replying-to">@<?php echo htmlspecialchars($comment['author_name']); ?></span> 
                                            <?php echo nl2br(htmlspecialchars($reply['content'])); ?></p>
                                        </div>
                                        <div class="comment-actions">
                                            <button class="like-btn <?php echo isCommentLikedByUser($conn, $reply['id'], $_SESSION['user_id'] ?? 0) ? 'active' : ''; ?>" 
                                                    data-id="<?php echo $reply['id']; ?>"
                                                    data-discussion-id="<?php echo $discussion_id; ?>">
                                                <i class="fas fa-heart"></i>
                                                <span class="count"><?php echo formatNumber(getCommentLikes($conn, $reply['id'])); ?></span>
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; endforeach; ?>
                            </div>
                        </div>
                    <?php endif; endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include("footer.php"); ?>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            $(document).ready(function() {
                // Handle reply button clicks
                $(document).on('click', '.reply-btn', function() {
                    const commentId = $(this).data('comment-id');
                    const authorName = $(this).data('author');
                    const $commentBox = $('#comment-text');
                    
                    $commentBox.focus();
                    $commentBox.val('');
                    $commentBox.data('parent-comment-id', commentId);
                    $('#post-comment-btn').text('Post Reply');
                    
                    $('html, body').animate({
                        scrollTop: $commentBox.offset().top - 100
                    }, 500);
                });

                // Handle comment/reply submission
                $("#post-comment-btn").click(function() {
                    const $commentBox = $("#comment-text");
                    const commentText = $commentBox.val();
                    const discussionId = <?php echo $discussion_id; ?>;
                    const userId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; ?>;
                    const parentCommentId = $commentBox.data('parent-comment-id');

                    if (!userId) {
                        alert('Please login to post a comment');
                        return;
                    }

                    if (commentText.trim() === "") {
                        alert("Comment cannot be empty");
                        return;
                    }

                    $.ajax({
                        url: "discussion_details_handler.php",
                        type: "POST",
                        dataType: 'json',
                        data: {
                            action: parentCommentId ? 'reply_comment' : 'save_comment',
                            discussion_id: discussionId,
                            user_id: userId,
                            content: commentText,
                            parent_comment_id: parentCommentId
                        },
                        success: function(response) {
                            if (response.status === "success") {
                                $commentBox.val("").data('parent-comment-id', null);
                                $('#post-comment-btn').text('Post Comment');
                                location.reload();
                            } else {
                                alert("Failed to post: " + response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("Ajax error:", error);
                            alert("An error occurred while posting. Please try again.");
                        }
                    });
                });

                // Add the same interaction handlers as in question_details.php
                // (like buttons, share button, toggle replies, etc.)
                // ... Copy the rest of the JavaScript from question_details.php ...
            });
        </script>
        <script>
$(document).ready(function() {
    // Handle discussion like button
    $('.topic-footer .like-btn').click(function(e) {
        e.preventDefault();
        const userId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; ?>;
        if (!userId) {
            alert('Please login to like discussions');
            return;
        }

        const $button = $(this);
        const discussionId = <?php echo $discussion_id; ?>;

        $.ajax({
            url: "discussion_details_handler.php",
            type: "POST",
            dataType: 'json',
            data: {
                action: 'save_interaction',
                discussion_id: discussionId,
                user_id: userId,
                interaction_type: 'like'
            },
            success: function(response) {
                if (response.status === 'success') {
                    $button.toggleClass('active');
                    animateCount($button.find('.count'), response.data.likes);
                    $('.share-count').text(formatNumber(response.data.shares));
                    $('.view-count span').text(formatNumber(response.data.views));
                    
                    $button.addClass('pulse');
                    setTimeout(() => $button.removeClass('pulse'), 500);
                } else {
                    alert('Failed to process like: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax error:', error);
                alert('An error occurred while processing your request');
            }
        });
    });

    // Handle share button
    $('.share-btn').click(function(e) {
        e.preventDefault();
        const userId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; ?>;
        if (!userId) {
            alert('Please login to share');
            return;
        }

        const $button = $(this);
        const discussionId = <?php echo $discussion_id; ?>;
        const currentUrl = window.location.href;

        // Create temporary input element
        const tempInput = document.createElement('input');
        tempInput.value = currentUrl;
        document.body.appendChild(tempInput);
        tempInput.select();
        
        try {
            // Try to copy to clipboard
            document.execCommand('copy');
            
            // Show feedback
            const $feedback = $('<span class="copy-feedback">Link copied!</span>');
            $button.append($feedback);
            setTimeout(() => $feedback.remove(), 2000);
            
            // Save share interaction
            $.ajax({
                url: "discussion_details_handler.php",
                type: "POST",
                dataType: 'json',
                data: {
                    action: 'save_interaction',
                    discussion_id: discussionId,
                    user_id: userId,
                    interaction_type: 'share'
                },
                success: function(response) {
                    if (response.status === 'success') {
                        $('.share-count').text(formatNumber(response.data.shares));
                        $button.addClass('copied');
                        setTimeout(() => $button.removeClass('copied'), 2000);
                    }
                }
            });
        } catch (err) {
            alert('Failed to copy link');
            console.error('Copy failed:', err);
        } finally {
            // Clean up
            document.body.removeChild(tempInput);
        }
    });

    // Handle comment likes
    $(document).on('click', '.comment-actions .like-btn', function(e) {
        e.preventDefault();
        const userId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; ?>;
        if (!userId) {
            alert('Please login to like comments');
            return;
        }

        const $button = $(this);
        const commentId = $button.data('id');
        const discussionId = $button.data('discussion-id');

        $.ajax({
            url: "discussion_details_handler.php",
            type: "POST",
            dataType: 'json',
            data: {
                action: 'like_comment',
                comment_id: commentId,
                user_id: userId,
                discussion_id: discussionId
            },
            success: function(response) {
                if (response.status === 'success') {
                    $button.toggleClass('active');
                    const newCount = parseInt($button.find('.count').text()) + ($button.hasClass('active') ? 1 : -1);
                    animateCount($button.find('.count'), newCount);
                    
                    $button.addClass('pulse');
                    setTimeout(() => $button.removeClass('pulse'), 500);
                }
            }
        });
    });

    // Helper function for count animation
    function animateCount($element, newValue) {
        const currentValue = parseInt($element.text().replace(/[^0-9]/g, '')) || 0;
        $({count: currentValue}).animate({count: newValue}, {
            duration: 500,
            step: function() {
                $element.text(Math.round(this.count));
            },
            complete: function() {
                $element.text(formatNumber(newValue));
            }
        });
    }

    // ... existing comment/reply handling code ...
    // Handle toggle replies
    $(document).on('click', '.toggle-replies', function() {
        const commentId = $(this).data('comment-id');
        const $repliesSection = $(`#replies-${commentId}`);
        const $button = $(this);
        const replyCount = $repliesSection.find('.reply').length;
        
        if ($repliesSection.is(':visible')) {
            $repliesSection.slideUp();
            $button.html(`<i class="fas fa-caret-down"></i> Show ${replyCount} ${replyCount === 1 ? 'reply' : 'replies'}`);
        } else {
            $repliesSection.slideDown();
            $button.html(`<i class="fas fa-caret-up"></i> Hide replies`);
        }
    });
});

function formatNumber(num) {
    if (num >= 1000000) return (num/1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num/1000).toFixed(1) + 'K';
    return num;
}
</script>

<style>
.copy-feedback {
    position: absolute;
    top: -30px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
    animation: fadeInOut 2s ease-in-out;
}

@keyframes fadeInOut {
    0% { opacity: 0; transform: translate(-50%, 10px); }
    20% { opacity: 1; transform: translate(-50%, 0); }
    80% { opacity: 1; transform: translate(-50%, 0); }
    100% { opacity: 0; transform: translate(-50%, -10px); }
}

.share-btn.copied {
    background-color: #4CAF50;
    color: white;
}
</style>

</body>
</html>
