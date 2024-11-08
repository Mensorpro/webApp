<?php
  session_start();
  if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('You must be logged in to start a discussion.')</script>";
    echo "<script>window.location.href = 'discussion.php'</script>";
  }

  include("get_categories.php");

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Community Help Center - Start a Discussion</title>

    <link rel="stylesheet" href="../css/index.css" />
    <link rel="stylesheet" href="../css/disscutionform.css" />
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
      <?php
        include("nav_links.php");
      ?>
    </header>

    <div class="discussion-section">
      <h2 class="discussion-heading">Start a Discussion</h2>

      <form action="discussionform_handler.php" method="post" enctype="multipart/form-data" class="discussion-form">

        <label for="subject" class="discussion-label">Subject:</label>
        <input type="text" id="subject" name="subject" class="discussion-input" required />

        <label for="category" class="question-label">Category:</label>
        <select id="category" name="category_id" class="question-select" required>
        <?php
          foreach ($categories as $category) {
            echo "<option value='".$category['id']."'id='".$category['id']."' >".$category['name']."</option>";
          }
          ?>
        </select>

        <label for="discussion-content" class="discussion-label">Discussion Content:</label>
        <textarea id="discussion-content" name="discussion-content" rows="4" class="discussion-textarea" required></textarea>

        <label for="file" class="discussion-label">Attach Files (optional):</label>
        <input type="file" id="file" name="file" class="discussion-file-input" />

        <input type="submit" value="Start Discussion" class="discussion-submit-button" />
      </form>
    </div>

    <!-- Footer -->
    <?php include("footer.php"); ?>
  </body>
</html>
