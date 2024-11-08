<?php
// Start session must be at the very top
session_start();

include("db_connection.php");
include("question_details_handler.php");

// Get question ID from URL
$question_id = isset($_GET['id']) ? $_GET['id'] : 0;
include("functions.php");

$question = getQuestionDetails($conn, $question_id);
$answers = getQuestionAnswers($conn, $question_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Question Details</title>
    <link rel="stylesheet" href="../css/question_details.css">
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

    <!-- Add this after header -->
    <?php
    // Track view
    if (isset($_SESSION['user_id'])) {
        saveInteraction($conn, $question_id, $_SESSION['user_id'], 'view');
    }
    ?>

    <!-- Display Question -->
    <div class="container">
        <div class="left-column">
            <?php if ($question): ?>
                <div class="question-topic">
                    <h1><?php echo htmlspecialchars($question['subject']); ?></h1>
                    
                    <div class="topic-metadata">
                        <div class="author-info">
                            <img src="<?php 
                                if (!empty($question['author_avatar'])) {
                                    echo '../' . $question['author_avatar'];
                                } else {
                                    echo '../images/default-avatar.png';
                                }
                            ?>" alt="Author avatar" class="avatar">
                            <div class="author-details">
                                <span class="author-name"><?php echo htmlspecialchars($question['author_name']); ?></span>
                                <span class="post-date">Posted on <?php echo simpleformatDate($question['created_at']); ?></span>
                            </div>
                        </div>
                        <div class="topic-stats">
                            <span class="views"><?php echo formatNumber(getQuestionInteractions($conn, $question_id)['views']); ?> views</span>
                            <span class="answers-count"><?php echo count($answers); ?> answers</span>
                        </div>
                    </div>

                    <div class="topic-content">
                        <p><?php echo nl2br(htmlspecialchars($question['details'])); ?></p>
                    </div>

                    <!-- Update the interaction buttons section -->
                    <div class="topic-footer">
                        <div class="interaction-buttons">
                            <?php 
                            $interactions = getQuestionInteractions($conn, $question_id);
                            $isLiked = isQuestionLikedByUser($conn, $question_id, $_SESSION['user_id'] ?? 0);
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
                <div class="error-message">Question not found.</div>
            <?php endif; ?>

            <!-- Add Answer Box -->
            <div class="add-answer-box">
                <h3>Answer this question</h3>
                <div class="answer-input-wrapper">
                    <textarea id="answer-text" placeholder="Add to the discussion"></textarea>
                    <button class="post-btn" id="post-answer-btn">Post Answer</button>
                </div>
            </div>
        </div>

        <div class="right-column">
            <!-- Answers Section -->
            <div class="answers-section">
                <div class="answer-header">
                    <h2>Answers</h2>
                    <select class="sort-answers">
                        <option>Most Recent</option>
                        <option>Most Liked</option>
                        <option>Oldest</option>
                    </select>
                </div>

                <!-- Answers List -->
                <div class="answers-list">
                    <?php if (empty($answers)): ?>
                        <div class="no-answers">No answers yet. Be the first to answer!</div>
                    <?php endif; ?>
                    <?php foreach ($answers as $answer):
                        // Only show main answers (not replies) at first level
                        if ($answer['parent_answer_id'] === null):
                    ?>
                        <div class="answer" data-answer-id="<?php echo $answer['id']; ?>">
                            <div class="answer-author">
                                <img src="<?php 
                                    if (!empty($answer['author_avatar'])) {
                                        echo '../' . $answer['author_avatar'];
                                    } else {
                                        echo '../images/default-avatar.png';
                                    }
                                ?>" alt="Answerer avatar" class="avatar">
                                <div class="author-details">
                                    <span class="author-name"><?php echo htmlspecialchars($answer['author_name']); ?></span>
                                    <span class="answer-date">
                                        <span class="dot"></span>
                                        <?php echo formatDate($answer['created_at']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="answer-content">
                                <p><?php echo nl2br(htmlspecialchars($answer['content'])); ?></p>
                            </div>
                            <div class="answer-actions">
                                <button class="like-btn <?php echo isAnswerLikedByUser($conn, $answer['id'], $_SESSION['user_id'] ?? 0) ? 'active' : ''; ?>" 
                                        data-id="<?php echo $answer['id']; ?>" 
                                        data-question-id="<?php echo $question_id; ?>">
                                    <i class="fas fa-heart"></i>
                                    <span class="count"><?php echo formatNumber(getAnswerLikes($conn, $answer['id'])); ?></span>
                                </button>
                                <button class="reply-btn" data-answer-id="<?php echo $answer['id']; ?>" 
                                        data-author="<?php echo htmlspecialchars($answer['author_name']); ?>">Reply</button>
                                <?php
                                // Count replies for this answer
                                $replyCount = 0;
                                foreach ($answers as $reply) {
                                    if ($reply['parent_answer_id'] === $answer['id']) $replyCount++;
                                }
                                if ($replyCount > 0):
                                ?>
                                <button class="toggle-replies" data-answer-id="<?php echo $answer['id']; ?>">
                                    <i class="fas fa-caret-down"></i> 
                                    Show <?php echo $replyCount; ?> <?php echo $replyCount === 1 ? 'reply' : 'replies'; ?>
                                </button>
                                <?php endif; ?>
                            </div>

                            <!-- Replies section -->
                            <div class="replies" id="replies-<?php echo $answer['id']; ?>" style="display: none;">
                                <?php foreach ($answers as $reply):
                                    if ($reply['parent_answer_id'] === $answer['id']):
                                ?>
                                    <div class="reply">
                                        <div class="answer-author">
                                            <img src="<?php 
                                                if (!empty($reply['author_avatar'])) {
                                                    echo '../' . $reply['author_avatar'];
                                                } else {
                                                    echo '../images/default-avatar.png';
                                                }
                                            ?>" alt="Replier avatar" class="avatar">
                                            <div class="author-details">
                                                <span class="author-name"><?php echo htmlspecialchars($reply['author_name']); ?></span>
                                                <span class="answer-date">
                                                    <span class="dot"></span>
                                                    <?php echo formatDate($reply['created_at']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="answer-content">
                                            <p><span class="replying-to">@<?php echo htmlspecialchars($answer['author_name']); ?></span> 
                                            <?php echo nl2br(htmlspecialchars($reply['content'])); ?></p>
                                        </div>
                                        <div class="answer-actions">
                                            <button class="like-btn <?php echo isAnswerLikedByUser($conn, $reply['id'], $_SESSION['user_id'] ?? 0) ? 'active' : ''; ?>" 
                                                    data-id="<?php echo $reply['id']; ?>"
                                                    data-question-id="<?php echo $question_id; ?>">
                                                <i class="fas fa-heart"></i>
                                                <span class="count"><?php echo formatNumber(getAnswerLikes($conn, $reply['id'])); ?></span>
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

    <!-- Replace the existing JavaScript section with this -->
<script>
$(document).ready(function() {
    // Handle reply button clicks
    $(document).on('click', '.reply-btn', function() {
        const answerId = $(this).data('answer-id');
        const authorName = $(this).data('author');
        const $answerBox = $('#answer-text');
        
        // Scroll to answer box and focus
        $answerBox.focus();
        
        // Clear previous content and set reply prefix
        $answerBox.val('');
        
        // Store parent answer ID
        $answerBox.data('parent-answer-id', answerId);
        
        // Update button text
        $('#post-answer-btn').text('Post Reply');
        
        // Scroll to answer box
        $('html, body').animate({
            scrollTop: $answerBox.offset().top - 100
        }, 500);
    });

    // Handle answer/reply submission
    $("#post-answer-btn").click(function() {
        const $answerBox = $("#answer-text");
        const answerText = $answerBox.val();
        const questionId = <?php echo $question_id; ?>;
        const userId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; ?>;
        const parentAnswerId = $answerBox.data('parent-answer-id');

        if (!userId) {
            alert('Please login to post an answer');
            return;
        }

        if (answerText.trim() === "") {
            alert("Answer cannot be empty");
            return;
        }

        $.ajax({
            url: "question_details_handler.php",
            type: "POST",
            dataType: 'json',
            data: {
                action: parentAnswerId ? 'reply_answer' : 'save_answer',
                question_id: questionId,
                user_id: userId,
                content: answerText,
                parent_answer_id: parentAnswerId
            },
            success: function(response) {
                if (response.status === "success") {
                    // Clear the answer box and reset
                    $answerBox.val("").data('parent-answer-id', null);
                    $('#post-answer-btn').text('Post Answer');
                    
                    // Reload the page to show new answer/reply
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

    // Single handler for question like button
    $(document).on('click', '.topic-footer .like-btn', function(e) {
        e.preventDefault();
        const userId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; ?>;
        if (!userId) {
            alert('Please login to like questions');
            return;
        }

        const $button = $(this);
        const questionId = <?php echo $question_id; ?>;

        $.ajax({
            url: "question_details_handler.php",
            type: "POST",
            dataType: 'json',
            data: {
                action: 'save_interaction',
                question_id: questionId,
                user_id: userId,
                interaction_type: 'like'
            },
            success: function(response) {
                if (response.status === 'success') {
                    // Toggle the active class immediately for visual feedback
                    $button.toggleClass('active');
                    
                    // Update all interaction counts with animated transition
                    const $likeCount = $('.topic-footer .like-btn .count');
                    const $shareCount = $('.topic-footer .share-count');
                    const $viewCount = $('.topic-footer .view-count span');
                    
                    animateCount($likeCount, response.data.likes);
                    $shareCount.text(formatNumber(response.data.shares));
                    $viewCount.text(formatNumber(response.data.views));
                    
                    // Add pulse animation class
                    $button.addClass('pulse');
                    setTimeout(() => $button.removeClass('pulse'), 500);
                } else {
                    console.error('Error:', response.message);
                    alert('Failed to process like: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax error:', error);
                alert('An error occurred while processing your request');
            }
        });
    });

    // Single handler for answer like button
    $(document).on('click', '.answer-actions .like-btn', function(e) {
        e.preventDefault();
        const userId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; ?>;
        if (!userId) {
            alert('Please login to like answers');
            return;
        }

        const $button = $(this);
        const answerId = $button.data('id');
        const questionId = $button.data('question-id');

        if (!answerId) {
            console.error('No answer ID found');
            return;
        }

        $.ajax({
            url: "question_details_handler.php",
            type: "POST",
            dataType: 'json',
            data: {
                action: 'like_answer',
                answer_id: answerId,
                user_id: userId,
                question_id: questionId
            },
            success: function(response) {
                if (response.status === 'success') {
                    // Toggle the active class immediately
                    $button.toggleClass('active');
                    
                    // Get updated like count
                    const $count = $button.find('.count');
                    const newCount = parseInt($count.text()) + (response.data.liked ? 1 : -1);
                    animateCount($count, newCount);
                    
                    // Add pulse animation
                    $button.addClass('pulse');
                    setTimeout(() => $button.removeClass('pulse'), 500);
                } else {
                    console.error('Error:', response.message);
                    alert('Failed to process like: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax error:', error);
                alert('An error occurred while processing your request');
            }
        });
    });

    // Helper function to animate count changes
    function animateCount($element, newValue) {
        const currentValue = parseInt($element.text().replace(/[^0-9]/g, '')) || 0;
        const duration = 500; // Animation duration in milliseconds
        const start = Date.now();
        
        const timer = setInterval(function() {
            const timePassed = Date.now() - start;
            let progress = timePassed / duration;
            
            if (progress > 1) {
                progress = 1;
                clearInterval(timer);
            }
            
            const currentCount = Math.round(currentValue + (newValue - currentValue) * progress);
            $element.text(formatNumber(currentCount));
            
            if (progress === 1) {
                clearInterval(timer);
            }
        }, 16);
    }

    // Handle toggle replies
    $(document).on('click', '.toggle-replies', function() {
        const answerId = $(this).data('answer-id');
        const $repliesSection = $(`#replies-${answerId}`);
        const $button = $(this);
        
        if ($repliesSection.is(':visible')) {
            $repliesSection.slideUp();
            $button.html(`<i class="fas fa-caret-down"></i> Show ${$repliesSection.children().length} ${$repliesSection.children().length === 1 ? 'reply' : 'replies'}`);
        } else {
            $repliesSection.slideDown();
            $button.html(`<i class="fas fa-caret-up"></i> Hide replies`);
        }
    });

    // Share button handler
    $(document).on('click', '.share-btn', function(e) {
        e.preventDefault();
        const userId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; ?>;
        if (!userId) {
            alert('Please login to share');
            return;
        }

        const $button = $(this);
        const questionId = <?php echo $question_id; ?>;
        const currentUrl = window.location.href;

        // Copy to clipboard
        navigator.clipboard.writeText(currentUrl).then(function() {
            // After copying, save the share interaction
            $.ajax({
                url: "question_details_handler.php",
                type: "POST",
                dataType: 'json',
                data: {
                    action: 'save_interaction',
                    question_id: questionId,
                    user_id: userId,
                    interaction_type: 'share'
                },
                success: function(response) {
                    if (response.status === 'success') {
                        // Update share count
                        $button.find('.share-count').text(formatNumber(response.data.shares));
                        alert('Link copied to clipboard!');
                    } else {
                        console.error('Error:', response.message);
                        alert('Failed to process share: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Ajax error:', error);
                    alert('An error occurred while processing your request');
                }
            });
        }).catch(function(err) {
            console.error('Failed to copy:', err);
            alert('Failed to copy to clipboard');
        });
    });

    // ... rest of your existing code ...
});

// Add clipboard.js if you want better clipboard support
function formatNumber(num) {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    } else if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num;
}

// Add these new CSS styles
const style = document.createElement('style');
style.textContent = `
    .like-btn {
        transition: transform 0.2s ease;
    }
    
    .like-btn:active {
        transform: scale(0.95);
    }
    
    .like-btn.pulse {
        animation: pulse 0.5s ease;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }
    
    .like-btn.active {
        color: #e91e63;
    }
    
    .like-btn.active i {
        color: #e91e63;
    }
`;
document.head.appendChild(style);
</script>

<!-- Add this CSS for better share button feedback -->
<style>
.share-btn {
    position: relative;
    transition: all 0.3s ease;
}

.share-btn:active {
    transform: scale(0.95);
}

.share-btn.copied {
    background-color: #4CAF50;
    color: white;
}

@keyframes copyFeedback {
    0% { opacity: 0; transform: translateY(0); }
    20% { opacity: 1; transform: translateY(-20px); }
    80% { opacity: 1; transform: translateY(-20px); }
    100% { opacity: 0; transform: translateY(-30px); }
}

.copy-feedback {
    position: absolute;
    top: -20px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0,0,0,0.8);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    animation: copyFeedback 1.5s ease-out forwards;
}
</style>

<!-- Add this CSS -->
<style>
.replies {
    margin-left: 40px;
    border-left: 2px solid #e1e1e1;
    padding-left: 15px;
    margin-top: 10px;
}

.reply {
    margin-top: 10px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 5px;
}

.replying-to {
    color: #1a73e8;
    font-weight: bold;
    margin-right: 5px;
}

.reply-indicator {
    color: #666;
    font-style: italic;
    margin-bottom: 5px;
}

.toggle-replies {
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
    padding: 5px 10px;
    font-size: 0.9em;
}

.toggle-replies:hover {
    color: #1a73e8;
}

.like-btn.active {
    color: #e91e63;
}

.like-btn.active i {
    color: #e91e63;
}

.answers-list .reply {
    border-left: 2px solid #e1e1e1;
    margin-left: 20px;
    padding-left: 15px;
    margin-top: 10px;
    background: #f8f9fa;
    border-radius: 5px;
}
</style>
</body>
</html>
