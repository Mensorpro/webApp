<?php
  session_start();
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Community Help Center - Emergency Contacts</title>

    <link rel="stylesheet" href="../css/index.css" />
    <link rel="stylesheet" href="../css/gethelp.css" />
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

    <!-- Main Section -->
    <main>
      <h1>How can we help you?</h1>
      <section class="help_section">
        <div class="help">
          <h2><i class="fas fa-phone-alt"></i>Emergency Contacts</h2>
          <p>
            Quick access to emergency services is crucial in times of need. Below are essential contacts for immediate assistance, including police, fire, medical emergencies, and specialized hotlines. These resources are available 24/7 to provide
            support during crises. Whether you are dealing with an accident, fire, medical emergency, or any other urgent situation, knowing who to contact can make all the difference in ensuring swift and efficient help.
          </p>
          <p>
            In moments of distress, having reliable emergency numbers can save valuable time and lives. The contacts listed here are designed to respond to a variety of emergency situations, from health concerns to safety threats. If you find
            yourself in need of help, don't hesitate to reach out to these services. Stay calm, provide them with as much detail as possible, and let them assist you in navigating the situation effectively.
          </p>
        </div>

        <div class="contacts">
          <h3>Call Now</h3>
          <div class="contact-buttons">
            <button class="contact-btn">
              <i class="fas fa-shield-alt"></i>
              Police
            </button>
            <button class="contact-btn">
              <i class="fas fa-ambulance"></i>
              Ambulance
            </button>
            <button class="contact-btn">
              <i class="fas fa-fire"></i>
              Fire Department
            </button>
            <button class="contact-btn">
              <i class="fas fa-brain"></i>
              Mental Health Crisis
            </button>
            <button class="contact-btn">
              <i class="fas fa-home"></i>
              Domestic Violence
            </button>
            <button class="contact-btn">
              <i class="fas fa-hands-helping"></i>
              Sexual Assault
            </button>
            <button class="contact-btn">
              <i class="fas fa-skull-crossbones"></i>
              Poison Control
            </button>
            <button class="contact-btn">
              <i class="fas fa-child"></i>
              Child Protection
            </button>
            <button class="contact-btn">
              <i class="fas fa-house-damage"></i>
              Disaster Relief
            </button>
            <button class="contact-btn">
              <i class="fas fa-car"></i>
              Roadside Assistance
            </button>
            <button class="contact-btn">
              <i class="fas fa-paw"></i>
              Animal Control
            </button>
            <button class="contact-btn">
              <i class="fas fa-heart"></i>
              Suicide Prevention
            </button>
          </div>
        </div>
      </section>
      
    </main>

    <!-- Footer -->
    <?php include("footer.php"); ?>
  </body>
</html>
