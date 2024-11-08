<?php
session_start();
include("discussion_handler.php");
require_once 'api_handler.php';
?>


<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Community Help Center - Discussions</title>
    <link rel="stylesheet" href="../css/discussion.css" />
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

    <!-- Main content -->
    <div class="discussions-header">
      <h2>Discussions</h2>
    </div>

    <main>
    <div class="discussion-c">
      <!-- Move ask question button to top -->
      <div class="top-buttons">
        <a href="discussionform.php"><button class="start-discussion-btn">Start a Discussion</button></a>
        <a href="askquestions.php"><button class="ask-question-btn">Ask A Question</button></a>
      </div>
    
      <?php foreach ($discussions as $discussion): ?>
      <a href="disscution_details.php?id=<?php echo $discussion['id']; ?>">
        <div class="discussions">
          <h4><?php echo $discussion['title']; ?></h4>
          <p>
            <?php
              //reconnect to db
              include("db_connection.php");
              $category = getCategory($conn, $discussion['category_id'])['name'];
              $conn->close();
            ?>

            <span class="category"> <i class="fas fa-tag"></i> <?php echo $category; ?> </span>
          </p>
          <p class="discussion-info">
            <?php
              //reconnect to db
              include("db_connection.php");
              $discussion['interactions'] = getDiscussionInteractions($conn, $discussion['id']);
              $comments_count = getCommentsCount($conn, $discussion['id']);
              $last_activity = formatDate(getLastActivity_D($conn, $discussion['id']));
              $user = getUser($conn, $discussion['user_id'])['name'];
              $interactions = $discussion['interactions'];
              $conn->close();
            ?>

            <span class="comments"> <i class="fas fa-comments"></i> <?php echo $comments_count; ?> comments </span>
            <span class="activity"> <i class="fas fa-clock"></i> <?php echo $last_activity; ?> </span>
            <span class="user"> <i class="fas fa-user"></i> <?php echo $user; ?> </span>
          </p>
          <p class="discussion-icons">
            <span class="icon-container">
              <i class="fas fa-heart"></i> <?php echo $interactions['likes']; ?>
            </span>
            <span class="icon-container">
              <i class="fas fa-eye"></i> <?php echo $interactions['views']; ?>
            </span>
            <span class="icon-container">
              <i class="fas fa-share"></i> <?php echo $interactions['shares']; ?>
            </span>
          </p>
        </div>
      </a>
    <?php endforeach; ?>
      
    </div>
    
        <aside class="questions-section">
        <h3>Asked Questions</h3>
       
        <?php foreach ($questions as $question): 
          
          include("db_connection.php");
                $question_id = $question['id'];
                $question['interactions'] = getQuestionInteractions($conn, $question_id);
                $question['answers'] = getAnswersCount($conn, $question_id);
                $question['last_activity'] = formatDate(getLastActivity_Q($conn, $question_id));
                $user_q = getUser($conn, $question['user_id'])['name'];
            $conn->close();
          
          
          ?>
          <a href="question_details.php?id=<?php echo $question_id; ?>">
            <div class="questions">
                <h4><?php echo $question['subject']; ?></h4>
                <p class="question-info">
                    <span class="answers"> <i class="fas fa-comments"></i> <?php echo $question['answers']; ?> answers </span>
                    <span class="posted"> <i class="fas fa-clock"></i> <?php echo $question['last_activity']; ?> </span>
                    <span class="user"> <i class="fas fa-user"></i> <?php echo $user_q; ?> </span>
                </p>
                <p class="discussion-icons">
                    <span class="icon-container"><i class="fas fa-heart"></i> <?php echo $question['interactions']['likes']; ?></span>
                    <span class="icon-container"> <i class="fas fa-eye"></i> <?php echo $question['interactions']['views']; ?></span>
                    <span class="icon-container"> <i class="fas fa-share"></i> <?php echo $question['interactions']['shares']; ?></span>
                </p>
            </div>
          </a>
        <?php endforeach; ?>
    
    </aside>
    </main>

    <?php include("footer.php"); ?>
    
  </body>
</html>

