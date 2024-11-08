<?php
function logout() {
  session_unset();
  session_destroy();
  header("Location: index.php");
  exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logout();
}
?>

<div class="menu-toggle">
  <span></span>
  <span></span>
  <span></span>
</div>
<div class="nav-overlay"></div>

<nav class="navbar">
  <a href="gethelp.php">Get Help</a>
  <a href="askquestions.php">Ask Questions</a>
  <a href="discussion.php">Discussion</a>
  <a href="donate.php">Donate</a>
  <?php if (isset($_SESSION['user_id'])): ?>
    <a href="account.php">My Account</a>
    <a href="?action=logout" id="logoutbtn">Logout</a>
  <?php else: ?>
    <a id="loginbtn">Login</a>
  <?php endif; ?>
  
  <input type="search" class="searchbox" placeholder="Search" />
</nav>



<script src="../js/jqery.js"></script>
<script src="../js/popup.js"></script>
<!-- HTML Structure -->

<div id="customPopup" class="popup-overlay">
  <div class="popup-content">
    <div class="popup-header">
      <h2 id="popupTitle">Login</h2>
      <span class="close-btn">&times;</span>
    </div>

    <div id="loginForm" class="auth-form">
      <div class="form-group">
        <label for="email">Email Address:</label>
        <input type="email" id="email" name="email" required />
      </div>
      <div class="form-group">
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required />
      </div>
      <div class="error-message" id="errorMsg"></div>
      <div class="success-message" id="successMsg"></div>
      <div class="button-group">
        <button id="submitBtn" class="primary-btn">Login</button>
        <button id="switchBtn" class="secondary-btn">Sign Up</button>
      </div>
    </div>

    <div id="signupForm" class="auth-form hidden">
      <div class="form-group">
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required />
      </div>
      <div class="form-group">
        <label for="signup-email">Email Address:</label>
        <input type="email" id="signup-email" name="email" required />
      </div>
      <div class="form-group">
        <label for="signup-password">Password:</label>
        <input type="password" id="signup-password" name="password" required />
      </div>
      <div class="error-message" id="signupErrorMsg"></div>
      <div class="success-message" id="signupSuccessMsg"></div>
      <div class="button-group">
        <button id="signupSubmitBtn" class="primary-btn">Sign Up</button>
        <button id="switchToLoginBtn" class="secondary-btn">Login</button>
      </div>
    </div>
  </div>
</div>

<style>
:root {
  --teal-primary: #008080;
  --teal-dark: #006666;
  --teal-light: #e6f3f3;
  --teal-hover: #007070;
  --teal-super-light: #f0f7f7;
  --error-red: #dc3545;
  --success-green: #28a745;
  --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.1);
  --shadow-md: 0 4px 8px rgba(0, 0, 0, 0.15);
  --shadow-lg: 0 8px 16px rgba(0, 0, 0, 0.2);
  --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Enhanced Popup Animation & Styling */
@keyframes fadeScale {
  from {
    opacity: 0;
    transform: translate(-50%, -40%) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translate(-50%, -50%) scale(1);
  }
}

.popup-overlay {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1000;
  backdrop-filter: blur(8px);
  transition: var(--transition);
}

.popup-content {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background: var(--white);
  width: 90%;
  max-width: 420px;
  padding: 2rem;
  border-radius: 20px;
  box-shadow: var(--shadow-lg);
  animation: fadeScale 0.4s ease-out;
}

/* Enhanced Header Styling */
.popup-header {
  position: relative;
  text-align: center;
  margin-bottom: 2rem;
}

.popup-header h2 {
  color: var(--teal-primary);
  font-size: 1.75rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.popup-header::after {
  content: '';
  position: absolute;
  bottom: -1rem;
  left: 50%;
  transform: translateX(-50%);
  width: 60px;
  height: 4px;
  background: var(--teal-primary);
  border-radius: 2px;
}

.close-btn {
  position: absolute;
  top: -1rem;
  right: -1rem;
  width: 36px;
  height: 36px;
  background: var(--white);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 1.5rem;
  color: var(--teal-dark);
  box-shadow: var(--shadow-sm);
  transition: var(--transition);
}

.close-btn:hover {
  transform: rotate(90deg);
  background: var(--teal-light);
  color: var(--teal-primary);
}

/* Enhanced Form Styling */
.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  font-size: 0.9rem;
  color: var(--teal-dark);
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.form-group input {
  width: 100%;
  padding: 0.875rem 1rem;
  border: 2px solid var(--teal-light);
  border-radius: 12px;
  font-size: 1rem;
  transition: var(--transition);
  background: var(--teal-super-light);
}

.form-group input:focus {
  outline: none;
  border-color: var(--teal-primary);
  background: var(--white);
  box-shadow: 0 0 0 4px rgba(0, 128, 128, 0.1);
  transform: translateY(-2px);
}

/* Enhanced Button Styling */
.button-group {
  display: grid;
  grid-template-columns: 1.5fr 1fr;
  gap: 1rem;
  margin-top: 2rem;
}

.primary-btn,
.secondary-btn {
  padding: 0.875rem 1.5rem;
  border: none;
  border-radius: 12px;
  font-weight: 600;
  font-size: 1rem;
  cursor: pointer;
  transition: var(--transition);
  position: relative;
  overflow: hidden;
}

.primary-btn {
  background: var(--teal-primary);
  color: white;
}

.primary-btn:hover {
  background: var(--teal-dark);
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.primary-btn:active {
  transform: scale(0.98);
}

.secondary-btn {
  background: var(--teal-light);
  color: var(--teal-primary);
}

.secondary-btn:hover {
  background: var(--teal-super-light);
  transform: translateY(-2px);
}

/* Enhanced Message Styling */
.error-message,
.success-message {
  padding: 1rem;
  border-radius: 12px;
  font-size: 0.9rem;
  font-weight: 600;  /* made bolder */
  margin: 1rem 0;
  opacity: 0;
  transform: translateY(-10px);
  transition: var(--transition);
  display: none;
  text-align: center;  /* center the text */
}

.error-message.show,
.success-message.show {
  opacity: 1;
  transform: translateY(0);
  display: block;
}

.error-message {
  background: #ffeaea;
  color: var(--error-red);
  border: 1px solid rgba(220, 53, 69, 0.2);
}

.success-message {
  background-color: #e8f5e9;  /* light green background */
  color: #1b5e20;  /* darker green text */
  border: 1px solid #43a047;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Form Switch Animation */
.auth-form {
  transition: var(--transition);
}

.auth-form.hidden {
  display: none;
  opacity: 0;
  transform: translateX(20px);
}

.auth-form.active {
  display: block;
  opacity: 1;
  transform: translateX(0);
}

/* Loading State */
.button-loading {
  position: relative;
  pointer-events: none;
}

.button-loading::after {
  content: '';
  position: absolute;
  width: 20px;
  height: 20px;
  top: calc(50% - 10px);
  right: 1rem;
  border: 2px solid white;
  border-top-color: transparent;
  border-radius: 50%;
  animation: rotate 0.8s linear infinite;
}

@keyframes rotate {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 480px) {
  .popup-content {
    width: 95%;
    padding: 1.5rem;
  }

  .button-group {
    grid-template-columns: 1fr;
  }
}
</style>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Hamburger menu functionality
    const menuToggle = document.querySelector('.menu-toggle');
    const navbar = document.querySelector('.navbar');
    const navOverlay = document.querySelector('.nav-overlay');

    if (menuToggle) {
      menuToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.toggle('active');
        navbar.classList.toggle('active');
        navOverlay.classList.toggle('active');
        console.log('Menu clicked'); // Debug line
      });
    }

    // Close menu when clicking overlay
    if (navOverlay) {
      navOverlay.addEventListener('click', function() {
        menuToggle.classList.remove('active');
        navbar.classList.remove('active');
        this.classList.remove('active');
      });
    }

    // Close menu when clicking links
    const navLinks = document.querySelectorAll('.navbar a');
    navLinks.forEach(link => {
      link.addEventListener('click', () => {
        menuToggle.classList.remove('active');
        navbar.classList.remove('active');
        navOverlay.classList.remove('active');
      });
    });
  });

  document.getElementById("loginbtn").onclick = function () {
    showLoginForm();
  };

  function showLoginForm() {
    const popup = document.getElementById("customPopup");
    const loginForm = document.getElementById("loginForm");
    const signupForm = document.getElementById("signupForm");
    const popupTitle = document.getElementById("popupTitle");

    popup.style.display = "block";
    loginForm.classList.remove("hidden");
    signupForm.classList.add("hidden");
    popupTitle.textContent = "Login";

    clearMessages();
    clearInputs();
  }

  function showSignupForm() {
    const popup = document.getElementById("customPopup");
    const loginForm = document.getElementById("loginForm");
    const signupForm = document.getElementById("signupForm");
    const popupTitle = document.getElementById("popupTitle");

    popup.style.display = "block";
    loginForm.classList.add("hidden");
    signupForm.classList.remove("hidden");
    popupTitle.textContent = "Sign Up";

    clearMessages();
    clearInputs();
  }

  function clearMessages() {
    document.getElementById("errorMsg").classList.remove("show");
    document.getElementById("successMsg").classList.remove("show");
    document.getElementById("signupErrorMsg").classList.remove("show");
    document.getElementById("signupSuccessMsg").classList.remove("show");
  }

  function clearInputs() {
    const inputs = document.querySelectorAll(".auth-form input");
    inputs.forEach((input) => (input.value = ""));
  }

  function closePopup() {
    document.getElementById("customPopup").style.display = "none";
    clearMessages();
    clearInputs();
  }

  // Event Listeners
  document.querySelector(".close-btn").onclick = closePopup;

  document.getElementById("switchBtn").onclick = function () {
    showSignupForm();
  };

  document.getElementById("switchToLoginBtn").onclick = function () {
    showLoginForm();
  };

  // Handle form submissions
  document.getElementById("submitBtn").onclick = function (e) {
    e.preventDefault();
    const email = document.getElementById("email").value;
    const password = document.getElementById("password").value;

    if (!email || !password) {
      const errorMsg = document.getElementById("errorMsg");
      errorMsg.textContent = "Please enter both email and password";
      errorMsg.style.display = "block";
      return;
    }

    // Use Ajax to send form data to login_handler.php
    $.ajax({
      url: "login_handler.php",
      type: "POST",
      data: { email: email, password: password },
      success: function (response) {
        try {
          const res = JSON.parse(response);
          if (res.status === "success") {
            const successMsg = document.getElementById("successMsg");
            successMsg.textContent = "Welcome back!";
            successMsg.classList.add("show");
            setTimeout(() => {
              closePopup();
              // refresh the page after login
              location.reload();
            }, 1000);
          } else {
            const errorMsg = document.getElementById("errorMsg");
            errorMsg.textContent = res.message || "Invalid email or password.";
            errorMsg.style.display = "block";
          }
        } catch (error) {
          console.error("Parsing error:", error);
          const errorMsg = document.getElementById("errorMsg");
          errorMsg.textContent = "An unexpected error occurred. Please try again.";
          errorMsg.style.display = "block";
        }
      },
      error: function () {
        const errorMsg = document.getElementById("errorMsg");
        errorMsg.textContent = "Server error. Please try again later.";
        errorMsg.style.display = "block";
      },
    });
  };

  document.getElementById("signupSubmitBtn").onclick = function (e) {
    e.preventDefault();
    const name = document.getElementById("name").value;
    const email = document.getElementById("signup-email").value;
    const password = document.getElementById("signup-password").value;

    if (!name || !email || !password) {
      const errorMsg = document.getElementById("signupErrorMsg");
      errorMsg.textContent = "Please enter name, email, and password";
      errorMsg.classList.add("show");  // Changed from style.display
      return;
    }

    // Use Ajax to send form data to signup_handler.php
    $.ajax({
      url: "signup_handler.php",
      type: "POST",
      data: { name: name, email: email, password: password },
      success: function (response) {
        try {
          const res = JSON.parse(response);
          if (res.status === "success") {
            const successMsg = document.getElementById("signupSuccessMsg");
            successMsg.textContent = "Welcome to the community!";
            successMsg.classList.add("show");  // Already correct
            setTimeout(() => {
              closePopup();
              location.reload();
            }, 1000);
          } else {
            const errorMsg = document.getElementById("signupErrorMsg");
            errorMsg.textContent = res.message || "Unable to sign up. Please try again.";
            errorMsg.classList.add("show");  // Changed from style.display
          }
        } catch (error) {
          console.error("Parsing error:", error);
          const errorMsg = document.getElementById("signupErrorMsg");
          errorMsg.textContent = "An unexpected error occurred. Please try again.";
          errorMsg.classList.add("show");  // Changed from style.display
        }
      },
      error: function () {
        const errorMsg = document.getElementById("signupErrorMsg");
        errorMsg.textContent = "Server error. Please try again later.";
        errorMsg.classList.add("show");  // Changed from style.display
      },
    });
  };

  // Close popup when clicking outside
  window.onclick = function (event) {
    const popup = document.getElementById("customPopup");
    if (event.target === popup) {
      closePopup();
    }
  };

  document.querySelector('.menu-toggle').addEventListener('click', function() {
    this.classList.toggle('active');
    document.querySelector('.navbar').classList.toggle('active');
    document.querySelector('.nav-overlay').classList.toggle('active');
  });

  document.querySelector('.nav-overlay').addEventListener('click', function() {
    this.classList.remove('active');
    document.querySelector('.navbar').classList.remove('active');
    document.querySelector('.menu-toggle').classList.remove('active');
  });

  // Update the link click handlers
  document.querySelectorAll('.navbar a').forEach(link => {
    link.addEventListener('click', (e) => {
      e.stopPropagation(); // Prevent event bubbling
      const href = link.getAttribute('href');
      if (href && href !== '#') {
        // Add a small delay to show the click effect
        setTimeout(() => {
          window.location.href = href;
        }, 100);
      }
      document.querySelector('.navbar').classList.remove('active');
      document.querySelector('.menu-toggle').classList.remove('active');
      document.querySelector('.nav-overlay').classList.remove('active');
    });
  });

  // Prevent clicks inside navbar from closing it
  document.querySelector('.navbar').addEventListener('click', (e) => {
    e.stopPropagation();
  });

  // Close menu when clicking a link
  document.querySelectorAll('.navbar a').forEach(link => {
    link.addEventListener('click', () => {
      document.querySelector('.navbar').classList.remove('active');
      document.querySelector('.menu-toggle').classList.remove('active');
      document.querySelector('.nav-overlay').classList.remove('active');
    });
  });
  
  document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.querySelector('.menu-toggle');
    const navbar = document.querySelector('.navbar');
    const overlay = document.querySelector('.nav-overlay');
    let touchStartY = 0;
    let touchEndY = 0;
    
    // Add touch handling
    function handleMenu(action) {
        const body = document.body;
        if (action === 'open') {
            navbar.classList.add('active');
            overlay.classList.add('active');
            body.classList.add('menu-open');
            menuToggle.classList.add('active');
        } else {
            navbar.classList.remove('active');
            overlay.classList.remove('active');
            body.classList.remove('menu-open');
            menuToggle.classList.remove('active');
        }
    }

    // Handle menu toggle
    menuToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        if (navbar.classList.contains('active')) {
            handleMenu('close');
        } else {
            handleMenu('open');
        }
    });

    // Close menu when clicking overlay
    overlay.addEventListener('click', function(e) {
        e.preventDefault();
        handleMenu('close');
    });

    // Prevent touchmove on overlay
    overlay.addEventListener('touchmove', function(e) {
        e.preventDefault();
    }, { passive: false });

    // Handle navbar touch events
    navbar.addEventListener('touchstart', function(e) {
        touchStartY = e.touches[0].clientY;
    }, { passive: true });

    navbar.addEventListener('touchmove', function(e) {
        touchEndY = e.touches[0].clientY;
        const scrollTop = this.scrollTop;
        const scrollHeight = this.scrollHeight;
        const offsetHeight = this.offsetHeight;
        const contentHeight = scrollHeight - offsetHeight;
        
        // Allow scrolling only within the menu content
        if ((scrollTop === 0 && touchEndY > touchStartY) ||
            (scrollTop >= contentHeight && touchEndY < touchStartY)) {
            e.preventDefault();
        }
        e.stopPropagation();
    }, { passive: false });

    // Close menu when clicking a link
    document.querySelectorAll('.navbar a').forEach(link => {
        link.addEventListener('click', function(e) {
            e.stopPropagation();
            handleMenu('close');
            
            // Add delay for visual feedback
            if (this.getAttribute('href')) {
                e.preventDefault();
                setTimeout(() => {
                    window.location.href = this.getAttribute('href');
                }, 150);
            }
        });
    });

    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
        if (navbar.classList.contains('active') &&
            !navbar.contains(e.target) &&
            !menuToggle.contains(e.target)) {
            handleMenu('close');
        }
    });

    // Prevent ghost clicks
    let lastTap = 0;
    document.addEventListener('touchend', function(e) {
        const now = Date.now();
        if (now - lastTap < 300) {
            e.preventDefault();
        }
        lastTap = now;
    }, false);
});
</script>
