<?php
    session_start();
    require_once 'db_connection.php';
    require_once 'functions.php';
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit();
    }

    // Fetch user data
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT * FROM users WHERE id = ?";

    // Add error handling for database operations
    try {
        // Fetch user data with error handling
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Database preparation failed");
        }
        
        if (!$stmt->bind_param("i", $user_id)) {
            throw new Exception("Parameter binding failed");
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Query execution failed");
        }
        
        $result = $stmt->get_result();
        if (!$result) {
            throw new Exception("Result retrieval failed");
        }

        $user = $result->fetch_assoc();
        if (!$user) {
            throw new Exception("User not found");
        }
    } catch (Exception $e) {
        // Log error and redirect to error page
        error_log("Database error: " . $e->getMessage());
        header("Location: error.php");
        exit();
    }

    // Fetch user's activities
    try {
        $sql = "SELECT * FROM (
            SELECT 
                'question' as type,
                q.id,
                q.subject as title,
                q.details as content,
                q.created_at,
                COALESCE(c.name, 'Uncategorized') as category_name,
                COALESCE(qi.likes, 0) as likes
            FROM questions q
            LEFT JOIN categories c ON q.category_id = c.id
            LEFT JOIN (
                SELECT question_id, COUNT(*) as likes 
                FROM question_interactions 
                WHERE interaction_type = 'like' 
                GROUP BY question_id
            ) qi ON q.id = qi.question_id
            WHERE q.user_id = ?
            
            UNION ALL
            
            SELECT 
                'answer' as type,
                a.id,
                q.subject as title,
                a.content,
                a.created_at,
                NULL as category_name,
                COALESCE(al.like_count, 0) as likes
            FROM answers a
            LEFT JOIN questions q ON a.question_id = q.id
            LEFT JOIN (
                SELECT answer_id, COUNT(*) as like_count 
                FROM answer_likes 
                GROUP BY answer_id
            ) al ON a.id = al.answer_id
            WHERE a.user_id = ?
            
            UNION ALL
            
            SELECT 
                'comment' as type,
                c.id,
                d.title as title,
                c.content,
                c.created_at,
                NULL as category_name,
                COALESCE(cl.like_count, 0) as likes
            FROM comments c
            LEFT JOIN discussions d ON c.discussion_id = d.id
            LEFT JOIN (
                SELECT comment_id, COUNT(*) as like_count 
                FROM comment_likes 
                GROUP BY comment_id
            ) cl ON c.id = cl.comment_id
            WHERE c.user_id = ?
            
            UNION ALL
            
            SELECT 
                'discussion' as type,
                d.id,
                d.title,
                d.content,
                d.created_at,
                NULL as category_name,
                COALESCE(di.likes, 0) as likes
            FROM discussions d
            LEFT JOIN (
                SELECT discussion_id, COUNT(*) as likes 
                FROM discussion_interactions 
                WHERE interaction_type = 'like' 
                GROUP BY discussion_id
            ) di ON d.id = di.discussion_id
            WHERE d.user_id = ?
        ) AS activities
        ORDER BY created_at DESC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
        $stmt->execute();
        $activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Properly group activities by type
        $grouped_activities = [
            'questions' => [],
            'discussions' => [],
            'answers' => [],
            'comments' => []
        ];

        foreach ($activities as $activity) {
            // Add to specific type group
            switch ($activity['type']) {
                case 'question':
                    $grouped_activities['questions'][] = $activity;
                    break;
                case 'discussion':
                    $grouped_activities['discussions'][] = $activity;
                    break;
                case 'answer':
                    $grouped_activities['answers'][] = $activity;
                    break;
                case 'comment':
                    $grouped_activities['comments'][] = $activity;
                    break;
            }
        }
        
        // Set 'all' after other groups are populated
        $grouped_activities['all'] = $activities;

    } catch (Exception $e) {
        $activities = [];
        $grouped_activities = [
            'all' => [],
            'questions' => [],
            'discussions' => [],
            'answers' => [],
            'comments' => []
        ];
    }

    // Add CSRF protection for forms
    $csrf_token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $csrf_token;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#008080" />
    <!-- Add mobile web app capable meta tags -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <title>Community Help Center - Account</title>
    <link rel="stylesheet" href="../css/account.css" />
    <link rel="stylesheet" href="../css/navbar_footer.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
</head>
<body>
    <header class="head">
        <div class="logo">
            <a href="index.php">
                <img src="logo.png" class="logo" alt="Community Help Center Logo" />
            </a>
        </div>
        <?php include("nav_links.php"); ?>
    </header>
    <div class="container">
        <!-- Profile Section -->
        <aside class="profile-card">
            <form id="profilePicForm" enctype="multipart/form-data">
                <div class="profile-pic-container">
                    <img src="<?php 
                        if (!empty($user['profile_picture_path'])) {
                            echo '../' . $user['profile_picture_path'];
                        } else {
                            echo '../images/default_profile.jpg';
                        }
                    ?>" alt="Profile Picture" class="profile-pic">
                    <div class="edit-pic-overlay">
                        <i class="fas fa-camera"></i>
                        <input type="file" name="profile_picture" id="profilePicInput" accept="image/*" style="display: none;">
                    </div>
                </div>
            </form>
            <div class="profile-info">
                <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
                <button class="btn btn-primary">Edit Profile</button>
                <button class="btn btn-outline">Change Password</button>
                <button class="btn btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
                <button class="btn btn-download">
                    <i class="fas fa-download"></i> Download My Data
                </button>
            </div>
        </aside>

        <!-- Activity Section -->
        <main class="activity-container">
            <div class="activity-header">
                <h2>Your Activity</h2>
                <select class="btn btn-outline">
                    <option>Last 7 Days</option>
                    <option>Last 30 Days</option>
                    <option>All Time</option>
                </select>
            </div>

            <div class="activity-tabs">
                <div class="tab active" data-tab="all">All Activity</div>
                <div class="tab" data-tab="questions">Questions</div>
                <div class="tab" data-tab="discussions">Discussions</div>
                <div class="tab" data-tab="answers">Answers</div>
                <div class="tab" data-tab="comments">Comments</div>
            </div>

            <div class="activity-list">
                <?php if (empty($grouped_activities['all'])): ?>
                    <div class="no-activity">No activities found</div>
                <?php else: ?>
                    <?php 
                    // First, render all activities with proper data attributes
                    foreach ($grouped_activities as $type => $activities): 
                        foreach ($activities as $activity): ?>
                            <div class="activity-item" data-activity-type="<?php echo $type; ?>">
                                <div class="activity-icon">
                                    <i class="fas <?php 
                                        echo match($activity['type']) {
                                            'question' => 'fa-question-circle',
                                            'discussion' => 'fa-comments',
                                            'answer' => 'fa-check-circle',
                                            'comment' => 'fa-comment',
                                            default => 'fa-circle'
                                        };
                                    ?>"></i>
                                </div>
                                <div class="activity-content">
                                    <h4><?php 
                                        if (!empty($activity['title'])) {
                                            echo htmlspecialchars($activity['title']);
                                        } else {
                                            echo match($activity['type']) {
                                                'answer' => 'Response to a question',
                                                'comment' => 'Comment on discussion',
                                                default => 'Untitled'
                                            };
                                        }
                                    ?></h4>
                                    <p><?php 
                                        echo match($activity['type']) {
                                            'question' => 'Posted in ' . htmlspecialchars($activity['category_name']),
                                            'answer' => 'Answered: ' . htmlspecialchars(substr($activity['content'], 0, 100)) . '...',
                                            'comment' => 'Commented: ' . htmlspecialchars(substr($activity['content'], 0, 100)) . '...',
                                            default => ''
                                        };
                                    ?></p>
                                    <div class="activity-stats">
                                        <span><i class="fas fa-thumbs-up"></i> <?php echo $activity['likes']; ?> likes</span>
                                        <span class="activity-time"><?php echo formatDate($activity['created_at']); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php 
                        endforeach;
                    endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Add these modal dialogs before the closing body tag -->
    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Edit Profile</h2>
            <form id="editProfileForm">
                <input type="hidden" name="action" value="updateProfile">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="changePasswordModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Change Password</h2>
            <form id="changePasswordForm">
                <input type="hidden" name="action" value="changePassword">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn btn-primary">Change Password</button>
            </form>
        </div>
    </div>

    <?php include("footer.php"); ?>

    <script>
        // Initialize elements
        const editProfileModal = document.getElementById('editProfileModal');
        const changePasswordModal = document.getElementById('changePasswordModal');
        const editProfileBtn = document.querySelector('.profile-card .btn-primary');
        const changePasswordBtn = document.querySelector('.profile-card .btn-outline');
        const logoutBtn = document.querySelector('.profile-card .btn-logout');
        const closeBtns = document.getElementsByClassName('close');
        const tabs = document.querySelectorAll('.tab');
        const activityItems = document.querySelectorAll('.activity-item');

        // Profile picture handling
        document.querySelector('.edit-pic-overlay').addEventListener('click', function() {
            document.getElementById('profilePicInput').click();
        });

        document.getElementById('profilePicInput').addEventListener('change', function() {
            const formData = new FormData(document.getElementById('profilePicForm'));
            fetch('account_handler.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-Token': '<?php echo $csrf_token; ?>'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => alert('An error occurred while uploading the image'));
        });

        // Modal controls
        editProfileBtn.onclick = () => editProfileModal.style.display = 'block';
        changePasswordBtn.onclick = () => changePasswordModal.style.display = 'block';
        logoutBtn.onclick = () => window.location.href = 'logout.php';

        Array.from(closeBtns).forEach(btn => {
            btn.onclick = function() {
                editProfileModal.style.display = 'none';
                changePasswordModal.style.display = 'none';
            }
        });

        window.onclick = function(event) {
            if (event.target === editProfileModal || event.target === changePasswordModal) {
                editProfileModal.style.display = 'none';
                changePasswordModal.style.display = 'none';
            }
        };

        // Form submissions
        document.getElementById('editProfileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('account_handler.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-Token': '<?php echo $csrf_token; ?>'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }
            });
        });

        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('account_handler.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-Token': '<?php echo $csrf_token; ?>'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message);
                    this.reset();
                    changePasswordModal.style.display = 'none';
                } else {
                    alert(data.message);
                }
            });
        });

        // Activity tabs functionality
        function filterActivities(selectedType) {
            // Add loading state
            const selectedTab = document.querySelector(`.tab[data-tab="${selectedType}"]`);
            selectedTab.classList.add('loading');
            
            // Get all activity items
            const items = document.querySelectorAll('.activity-item');
            let visibleCount = 0;
            
            // Show/hide items based on type
            items.forEach(item => {
                const type = item.getAttribute('data-activity-type');
                if (selectedType === 'all' || type === selectedType) {
                    item.style.display = 'grid';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            // Handle no activities message
            const existingNoActivity = document.querySelector('.no-activity');
            if (existingNoActivity) {
                existingNoActivity.remove();
            }

            if (visibleCount === 0) {
                const activityList = document.querySelector('.activity-list');
                const noActivityDiv = document.createElement('div');
                noActivityDiv.className = 'no-activity';
                noActivityDiv.textContent = `No ${selectedType === 'all' ? 'activities' : selectedType} found`;
                activityList.appendChild(noActivityDiv);
            }

            // Update active tab state
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active', 'loading');
            });
            selectedTab.classList.add('active');
        }

        // Add click event listeners to tabs
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', (e) => {
                const selectedType = e.target.getAttribute('data-tab');
                filterActivities(selectedType);
            });
        });

        // Initialize with "all" tab active
        filterActivities('all');

        // Add loading state to buttons
        document.querySelectorAll('.btn').forEach(btn => {
            if (btn.tagName === 'SELECT') return; // Skip select elements
            btn.addEventListener('click', function(e) {
                if (!this.classList.contains('loading')) {
                    this.classList.add('loading');
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                    
                    setTimeout(() => {
                        this.classList.remove('loading');
                        this.innerHTML = originalText;
                    }, 1000);
                }
            });
        });

        // Smooth scroll to top when switching tabs
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });

        // Add tooltips for activity items
        activityItems.forEach(item => {
            item.setAttribute('title', 'Click to view details');
            item.style.cursor = 'pointer';
            item.addEventListener('click', function() {
                // Add a nice ripple effect on click
                const ripple = document.createElement('div');
                ripple.className = 'ripple';
                this.appendChild(ripple);
                setTimeout(() => ripple.remove(), 1000);
            });
        });

        // Add this to the existing script section
        const downloadBtn = document.querySelector('.btn-download');
        
        downloadBtn.addEventListener('click', async function() {
            this.classList.add('loading');
            try {
                const response = await fetch('download_handler.php', {
                    headers: {
                        'X-CSRF-Token': '<?php echo $csrf_token; ?>'
                    }
                });
                
                if (!response.ok) throw new Error('Download failed');
                
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'my_activity_data.csv';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                a.remove();
            } catch (error) {
                alert('Failed to download data. Please try again.');
            } finally {
                this.classList.remove('loading');
            }
        });

        // Replace the existing select handling code with this:
        const activitySelect = document.querySelector('.activity-header select');
        activitySelect.addEventListener('change', function(e) {
            // Simple change without animations
            const value = e.target.value;
            // Add your filtering logic here if needed
        });

        // Remove loading animation from select
        document.querySelectorAll('.btn').forEach(btn => {
            if (btn.tagName === 'SELECT') return; // Skip select elements
            btn.addEventListener('click', function(e) {
                if (!this.classList.contains('loading')) {
                    this.classList.add('loading');
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                    
                    setTimeout(() => {
                        this.classList.remove('loading');
                        this.innerHTML = originalText;
                    }, 1000);
                }
            });
        });

        // Add touch event support for mobile
        let touchStartX = 0;
        let touchEndX = 0;
        
        const tabsContainer = document.querySelector('.activity-tabs');
        
        tabsContainer.addEventListener('touchstart', e => {
            touchStartX = e.changedTouches[0].screenX;
        }, false);
        
        tabsContainer.addEventListener('touchend', e => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, false);
        
        function handleSwipe() {
            const currentTab = document.querySelector('.tab.active');
            const tabs = Array.from(document.querySelectorAll('.tab'));
            const currentIndex = tabs.indexOf(currentTab);
            
            if (touchEndX < touchStartX && currentIndex < tabs.length - 1) {
                // Swipe left
                tabs[currentIndex + 1].click();
            } else if (touchEndX > touchStartX && currentIndex > 0) {
                // Swipe right
                tabs[currentIndex - 1].click();
            }
        }
        
        // Improve modal handling for mobile
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.addEventListener('touchmove', e => {
                e.preventDefault();
            }, { passive: false });
        });
        
        // Add double-tap prevention
        document.addEventListener('touchend', function(event) {
            event.preventDefault();
            const now = Date.now();
            const DOUBLE_TAP_DELAY = 300;
            if (now - lastTap < DOUBLE_TAP_DELAY) {
                event.preventDefault();
            }
            lastTap = now;
        }, false);
    </script>
    <script>
        // Fix touch events on mobile
        document.addEventListener('DOMContentLoaded', function() {
            // Enable touch events for all interactive elements
            const interactiveElements = document.querySelectorAll('.btn, .tab, .activity-item, .edit-pic-overlay, select, input, a');
            
            interactiveElements.forEach(element => {
                // Prevent ghost clicks
                element.addEventListener('touchstart', function(e) {
                    this.touched = true;
                }, { passive: true });

                element.addEventListener('touchend', function(e) {
                    if (this.touched) {
                        e.preventDefault();
                        this.click();
                        this.touched = false;
                    }
                });

                // Remove focus on mobile after click
                element.addEventListener('click', function(e) {
                    if (this.tagName !== 'INPUT' && this.tagName !== 'SELECT') {
                        this.blur();
                    }
                });
            });

            // Fix scrolling in activity list
            const activityList = document.querySelector('.activity-list');
            if (activityList) {
                activityList.addEventListener('touchmove', function(e) {
                    e.stopPropagation();
                }, { passive: true });
            }

            // Fix modal scrolling
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                modal.addEventListener('touchmove', function(e) {
                    if (e.target === modal) {
                        e.preventDefault();
                    }
                }, { passive: false });
            });
        });
    </script>
</body>
</html>

