// ... HTML code remains the same until the script section ...

<script>
$(document).ready(function() {
    // Handle comments/replies
    $(document).on('click', '.reply-btn', function() {
        const commentId = $(this).closest('.comment').data('comment-id');
        const authorName = $(this).closest('.comment').find('.author-name').text();
        const $commentBox = $('#comment-text');
        
        $commentBox.focus();
        $commentBox.val('');
        $commentBox.data('parent-comment-id', commentId);
        $('#post-comment-btn').text('Post Reply');
        
        $('html, body').animate({
            scrollTop: $commentBox.offset().top - 100
        }, 500);
    });

    // Update the like button handler
    $(document).on('click', '.topic-footer .like-btn', function(e) {
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
                    $button.toggleClass('active', response.data.liked);
                    const $likeCount = $button.find('.count');
                    animateCount($likeCount, response.data.likes);
                    $button.addClass('pulse');
                    setTimeout(() => $button.removeClass('pulse'), 500);
                } else {
                    console.error('Error:', response.message);
                    alert('Failed to process like: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax error:', error);
                console.error('Response:', xhr.responseText); // Add this line for debugging
                alert('An error occurred while processing your request. Please try again.');
            }
        });
    });

    // Update share button handler
    $(document).on('click', '.share-btn', function(e) {
        e.preventDefault();
        const userId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; ?>;
        if (!userId) {
            alert('Please login to share');
            return;
        }

        const $button = $(this);
        const discussionId = <?php echo $discussion_id; ?>;
        const currentUrl = window.location.href;

        // Only proceed if not already shared
        if (!$button.hasClass('shared')) {
            navigator.clipboard.writeText(currentUrl).then(function() {
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
                            $button.addClass('shared');
                            const $shareCount = $button.find('.count');
                            animateCount($shareCount, response.data.shares);
                            $button.addClass('pulse');
                            setTimeout(() => $button.removeClass('pulse'), 500);
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
        } else {
            alert('You have already shared this discussion!');
        }
    });

    // Helper function to animate count changes
    function animateCount($element, newValue) {
        const currentValue = parseInt($element.text().replace(/[^0-9]/g, '')) || 0;
        const duration = 500;
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
            
            if (progress === 1) clearInterval(timer);
        }, 16);
    }
});
</script>
