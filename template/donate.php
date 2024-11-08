<?php
  session_start();
  require_once 'api_handler.php';
?>



<!DOCTYPE html>
<html lang="en">
  <head> 
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Community Help Center - Donate</title>
    
    <link rel="stylesheet" href="../css/index.css" />
    <link rel="stylesheet" href="../css/donate.css" />
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

    <div class="donation-section">
      <div class="donation_campaign">
        <h2 class="donation-heading">Why Donate?</h2>
<div class="donation-c">
        <p class="donation-description">
          Your donation is more than just a contribution; it’s a powerful act of kindness that has the potential to change lives. By choosing to donate, you become an essential part of a mission to bring hope and support to those who need it most.
        </p>

        <p class="donation-description">
          Every single donation, no matter the size, helps us reach out to individuals and communities in desperate need. Your generosity provides food, shelter, education, and healthcare to those who would otherwise go without. It helps us respond
          to emergencies and natural disasters, bringing immediate relief and rebuilding shattered lives.
        </p>

        <p class="donation-description">But your impact goes beyond immediate relief. With your support, we can build sustainable solutions that empower people to thrive.</p>
      </div>
      </div>
      <div class="donation-form-section">
        <form id="donation-form" class="donation-form">
          <div class="amount-buttons">
            <button type="button" class="amount-btn" data-amount="10">$10</button>
            <button type="button" class="amount-btn" data-amount="25">$25</button>
            <button type="button" class="amount-btn" data-amount="50">$50</button>
          </div>

          <label for="name" class="donation-label">Name:</label>
          <input type="text" id="name" name="name" class="donation-input" required />

          <label for="email" class="donation-label">Email Address:</label>
          <input type="email" id="email" name="email" class="donation-input" required />

          <label for="amount" class="donation-label">Custom Amount:</label>
          <input type="number" id="amount" name="amount" class="donation-input" placeholder="Enter custom amount" required />

          <label for="payment-method" class="donation-label">Payment Method:</label>
          <select id="payment-method" name="payment-method" class="donation-select" required>
            <option value="">Select payment method</option>
            <option value="master-card">Master Card</option>
            <option value="paypal">PayPal</option>
            <option value="mobile-money">Mobile Money</option>
          </select>

          <div id="card-element">
            <!-- Stripe card element will be inserted here -->
          </div>

          <input type="submit" value="Donate Now" class="donation-submit-button" />
        </form>
      </div>
    </div>

    <script src="https://js.stripe.com/v3/"></script>
    <script>
      const stripe = Stripe('<?php echo STRIPE_PUBLIC_KEY; ?>');
      const elements = stripe.elements();
      const card = elements.create('card');
      card.mount('#card-element');
      
      document.getElementById('donation-form').addEventListener('submit', async (e) => {
          e.preventDefault();
          const {token, error} = await stripe.createToken(card);
          if (error) {
              alert(error.message);
          } else {
              processDonation(amount, token.id);
          }
      });
    </script>

    <!-- Footer -->
    <?php include("footer.php"); ?>
  </body>
</html>
