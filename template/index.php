<?php
  session_start();
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no" />
    <title>Community Help Center - Get Help</title>

    <link rel="stylesheet" href="../css/index.css" />

    <link rel="stylesheet" href="../css/navbar_footer.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
  </head>
  <body>
    <!-- Header and Navigation -->
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

    <!-- Main Section -->
    <main class="main">
      <div class="main_div">
        <div class="banner">
          <img src="banner.svg" alt="Banner" />
          <div class="banner-content">
            <h1>Community Help Center</h1>
            <p class="banner-text">Get the help you need, ask questions, and connect with the community.</p>
            <a href="gethelp.php">
              <button class="helpbtn">Get Help Now</button>
            </a>
          </div>
        </div>
      </div>

      <!-- Remove the separate help_section since button is now in banner -->
      <section class="dis_section">
        <a href="discussion.php" class="discussion-link">
          <div class="discussion">
            <div class="icon-container">
              <i class="fas fa-comments"></i>
              <!-- Icon for Join the talk -->
            </div>
            <h3>Join the talk</h3>
            <p>Join the conversation and make your voice heard. Help inspire and empower our community.</p>
          </div>
        </a>
        <a href="askquestions.php" class="discussion-link">
          <div class="discussion">
            <div class="icon-container">
              <i class="fas fa-question-circle"></i>
              <!-- Icon for Ask the community -->
            </div>
            <h3>Ask the community</h3>
            <p>Find answers, ask questions, or give feedback in our community.</p>
          </div>
        </a>
        <a href="donate.php" class="discussion-link">
          <div class="discussion">
            <div class="icon-container">
              <i class="fas fa-hand-holding-heart"></i>
              <!-- Icon for Donate -->
            </div>
            <h3>Donate</h3>
            <p>Support the community by making a donation to help improve services and resources.</p>
          </div>
        </a>
      </section>

      <!-- Free Resources Section -->
      <section class="resources_section">
        <h2>Free Resources & Apps</h2>
        <div class="resources-grid">
          <a href="https://www.211.org" class="resource-card" target="_blank">
            <div class="resource-icon">
              <i class="fas fa-phone-square-alt"></i>
            </div>
            <h3>211</h3>
            <p>Connect with local resources and assistance programs in your area</p>
          </a>

          <a href="https://www.benefitscheckup.org" class="resource-card" target="_blank">
            <div class="resource-icon">
              <i class="fas fa-search-dollar"></i>
            </div>
            <h3>Benefits CheckUp</h3>
            <p>Find benefits programs you may qualify for</p>
          </a>

          <a href="https://www.findhelp.org" class="resource-card" target="_blank">
            <div class="resource-icon">
              <i class="fas fa-hands-helping"></i>
            </div>
            <h3>FindHelp.org</h3>
            <p>Search for free or reduced-cost services in your area</p>
          </a>

          <a href="https://www.healthfinder.gov" class="resource-card" target="_blank">
            <div class="resource-icon">
              <i class="fas fa-heartbeat"></i>
            </div>
            <h3>Healthfinder</h3>
            <p>Access health resources and government health services</p>
          </a>
        </div>
      </section>

      <!-- Dynamic Community Resources -->
      <section class="dynamic-resources">
        <h2>Latest Community Updates</h2>
        <div class="updates-container">
          <?php
            require_once 'api_handler.php';
            $api = new APIHandler();
            
            try {
                $news = $api->fetchHealthNews();
                if (!empty($news['articles'])) {
                    echo '<div class="news-feed">';
                    foreach(array_slice($news['articles'], 0, 3) as $article) {
                        echo '<div class="news-item">';
                        echo '<div class="news-content">';
                        echo '<h3>' . htmlspecialchars($article['title']) . '</h3>';
                        echo '<p>' . htmlspecialchars($article['description']) . '</p>';
                        echo '<a href="' . htmlspecialchars($article['url']) . '" ' . 
                             ($article['url'] === '#' ? '' : 'target="_blank"') . 
                             ' class="read-more">Read more <i class="fas fa-arrow-right"></i></a>';
                        echo '</div>';
                        echo '</div>';
                    }
                    echo '</div>';
                }
            } catch (Exception $e) {
                echo '<div class="error-message">Unable to load updates. Please try again later.</div>';
            }
          ?>
        </div>
      </section>
      
    </main>
    
    <script>
      // Add Intersection Observer for smooth animations
      const observerOptions = {
        root: null,
        threshold: 0.1,
        rootMargin: '0px'
      };

      const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            observer.unobserve(entry.target);
          }
        });
      }, observerOptions);

      // Observe elements
      document.querySelectorAll('.discussion, .resource-card, .news-item').forEach(el => {
        el.classList.add('fade-in');
        observer.observe(el);
      });
    </script>
    
    <!-- Footer -->
    <?php include("footer.php"); ?>
  </body>
</html>
