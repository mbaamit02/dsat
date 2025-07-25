<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/helpers.php';
require_once __DIR__ . '/app/models/User.php';

error_log("Loading index.php for user session: " . (isset($_SESSION['user']) ? $_SESSION['user']['email'] : 'guest'));

// Initialize variables for guest users
$currentPlan = 'Free Plan';
$subscriptions = [];

// Check if user is logged in and fetch subscription details if so
if (isset($_SESSION['user'])) {
    try {
        $userModel = new User($pdo);
        $userId = $_SESSION['user']['id'];
        // Check if method exists to avoid fatal errors
        if (method_exists($userModel, 'getUserSubscriptionDetails')) {
            $subscriptions = $userModel->getUserSubscriptionDetails($userId);
        }
        $currentPlan = $_SESSION['user']['subscription_plan'] ?? 'Free Plan';
    } catch (Exception $e) {
        error_log("Error fetching user subscription: " . $e->getMessage());
        $currentPlan = 'Free Plan';
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="bg-gray-100">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Digital SAT Practice Dashboard</title>
  <!-- Tailwind CSS via CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Alpine.js via CDN -->
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <style>
    body { 
      font-family: 'Inter', sans-serif;
      background-image: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    }
    .sidebar-text {
      transition: opacity 0.3s;
    }
    .card {
      transition: all 0.3s ease;
    }
    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 25px -5px rgba(0, 0, 0, 0.1);
    }
  </style>
</head>
<body class="bg-gray-100 text-gray-900">
  <!-- Outer container: full viewport height with no overall scroll -->
  <div class="flex h-screen overflow-hidden">
    <!-- Sidebar Container -->
    <aside id="sidebar" 
           class="w-64 bg-gradient-to-b from-blue-900 to-blue-800 shadow-xl h-full overflow-y-auto
                  fixed md:static left-0 top-0 transform -translate-x-full md:translate-x-0 transition-transform duration-300 z-50">
      <!-- Close button inside sidebar (visible on mobile only) -->
      <button id="closeSidebar" class="md:hidden absolute top-4 right-4 bg-red-500 text-white p-2 rounded-full">
        <i class="fas fa-times"></i>
      </button>
      <?php 
      try {
          require __DIR__ . '/app/views/layout/sidebar.php'; 
      } catch (Exception $e) {
          error_log("Error including sidebar.php: " . $e->getMessage());
          echo "<p class='text-white p-4'>Error loading sidebar. Please try again.</p>";
      }
      ?>
    </aside>
    
    <!-- Main content container -->
    <div class="flex-1 flex flex-col">
      <!-- Header -->
      <header class="sticky top-0 bg-white shadow-md z-10">
        <?php 
        try {
            require __DIR__ . '/app/views/layout/header.php'; 
        } catch (Exception $e) {
            error_log("Error including header.php: " . $e->getMessage());
            echo "<p class='text-red-500 p-4'>Error loading header. Please try again.</p>";
        }
        ?>
      </header>
      
      <!-- Main scrollable area -->
      <main class="flex-1 overflow-y-auto p-6">
        <!-- Error Message Display -->
        <?php if (isset($_SESSION['error'])): ?>
          <div class="mb-4 text-red-500 text-center bg-red-100 p-3 rounded-lg">
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
          </div>
        <?php endif; ?>
        
        <!-- Dashboard Card -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden p-6 mb-8">
          <h2 class="text-2xl font-bold mb-4">Digital SAT Practice Dashboard</h2>
          <?php if (isset($_SESSION['user'])): ?>
            <p class="mb-4 text-gray-700">Current Plan: <span class="font-semibold text-indigo-600"><?php echo htmlspecialchars($currentPlan); ?></span></p>
          <?php endif; ?>
          <p class="mb-6 text-gray-700">
            Get Digital SAT Practice Papers, PDFs, strategies & tips.
          </p>
          <div class="bg-gradient-to-r from-blue-500 to-purple-600 text-white text-center py-5 px-3 rounded-lg shadow-lg">
            <h2 class="text-xl font-bold mb-2">Ace Your DSAT Dreams with Aimlearno!</h2>
            <p class="mb-5">
              Your Ultimate Partner for Success! Personalized Prep, AI-Driven Insights, and Top-Notch Resources to Boost Your Score!
            </p>
            <button onclick="window.location.href='sat.php'" class="px-5 py-2 bg-white text-blue-500 font-semibold rounded hover:bg-gray-200 transition-colors duration-150">
              Let's Ace It!
            </button>
          </div>
        </div>

        <!-- Grid of Cards -->
        <div class="container mx-auto px-4 py-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Card 1: Upcoming DSAT Exams -->
            <div class="bg-white p-6 rounded-lg shadow card text-center">
              <h2 class="text-xl font-bold text-gray-800">Upcoming DSAT Exams!</h2>
              <p class="text-gray-700 mt-2">
                Stay ahead of the Curve: Plan for the Next DSAT Exams Preparation with Aimlearno.
              </p>
              <div class="mt-4 flex justify-center space-x-4">
                <button type="button" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded"
                        onclick="document.getElementById('examDatesModal').classList.remove('hidden')">
                  View Dates
                </button>
                <button class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded font-semibold"
                        onclick="document.getElementById('premiumModal').classList.remove('hidden')">
                  💎 Free Consultancy
                </button>
              </div>
            </div>
            
            <!-- Card 2: Self-Prep, Big Results! -->
            <div class="bg-white p-6 rounded-lg shadow card text-center">
              <h2 class="text-xl font-bold text-gray-800">Self-Prep, Big Results!</h2>
              <p class="text-gray-700 mt-2">
                Empower Yourself: Master the SAT with Focused Self-Preparation.
              </p>
              <div class="mt-4 flex justify-center space-x-4">
                <button type="button" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded"
                        onclick="document.getElementById('satPlanModal').classList.remove('hidden')">
                  Free Practice Papers
                </button>
                <button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded"
                        onclick="window.location.href='mock_test.php'">
                  Free Mock Test
                </button>
              </div>
            </div>
            
            <!-- Card 3: Personalized Help! -->
            <div class="bg-white p-6 rounded-lg shadow card text-center">
              <h2 class="text-xl font-bold text-gray-800">Personalized Help!</h2>
              <p class="text-gray-700 mt-2">
                Achieve More with One-on-One Guidance, Interactive Problem Solving.
              </p>
              <div class="mt-4 flex justify-center space-x-4">
                <button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded font-semibold"
                        onclick="document.getElementById('tutorModal').classList.remove('hidden')">
                  💬 1-on-1 Tutor
                </button>
                <button class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded font-semibold"
                        onclick="document.getElementById('premiumModal').classList.remove('hidden')">
                  💎 Upload Your Doubts
                </button>
              </div>
            </div>
            
            <!-- Card 4: Explore Universities! -->
            <div class="bg-white p-6 rounded-lg shadow card text-center">
              <h2 class="text-xl font-bold text-gray-800">Explore Universities!</h2>
              <p class="text-gray-700 mt-2">
                Dream Big: DSAT Opens Doors to Top Universities Worldwide.
              </p>
              <div class="mt-4 flex justify-center space-x-4">
                <button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded font-semibold"
                        onclick="document.getElementById('tutorModal').classList.remove('hidden')">
                  💬 Top Universities
                </button>
                <button class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded font-semibold"
                        onclick="document.getElementById('premiumModal').classList.remove('hidden')">
                  💎 Top Colleges
                </button>
              </div>
            </div>
          </div>
        </div>
      </main>
      
      <!-- Footer -->
      <footer class="bg-gray-50 border-t border-gray-200 p-4 text-center">
        <p class="text-sm text-gray-600">
          By using this site, you agree to our 
          <a href="<?php echo BASE_URL; ?>/public/terms.php" class="text-blue-500 hover:underline">Terms & Conditions</a> and 
          <a href="<?php echo BASE_URL; ?>/public/privacy.php" class="text-blue-500 hover:underline">Privacy Policy</a>.
        </p>
      </footer>
    </div>
  </div>

  <!-- Modals -->
  <div id="examDatesModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
    <div class="bg-white p-6 rounded-lg max-w-md w-full">
      <h3 class="text-lg font-bold mb-4">Upcoming DSAT Exam Dates</h3>
      <p class="text-gray-700">Check the official SAT website for the latest exam dates.</p>
      <button onclick="document.getElementById('examDatesModal').classList.add('hidden')" 
              class="mt-4 bg-blue-500 text-white px-4 py-2 rounded">
        Close
      </button>
    </div>
  </div>

  <div id="premiumModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
    <div class="bg-white p-6 rounded-lg max-w-md w-full">
      <h3 class="text-lg font-bold mb-4">Premium Consultancy</h3>
      <p class="text-gray-700">Unlock premium features with a subscription!</p>
      <?php if (!isset($_SESSION['user'])): ?>
        <button onclick="window.location.href='<?php echo BASE_URL; ?>/public/login.php?redirect_to=upgrade.php'" 
                class="mt-4 bg-yellow-500 text-white px-4 py-2 rounded">
          Login to Upgrade
        </button>
      <?php else: ?>
        <button onclick="window.location.href='<?php echo BASE_URL; ?>/public/upgrade.php'" 
                class="mt-4 bg-yellow-500 text-white px-4 py-2 rounded">
          Upgrade Now
        </button>
      <?php endif; ?>
      <button onclick="document.getElementById('premiumModal').classList.add('hidden')" 
              class="mt-2 bg-gray-300 text-gray-800 px-4 py-2 rounded">
        Close
      </button>
    </div>
  </div>

  <div id="satPlanModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
    <div class="bg-white p-6 rounded-lg max-w-md w-full">
      <h3 class="text-lg font-bold mb-4">Free Practice Papers</h3>
      <p class="text-gray-700">Access free SAT practice papers with a free account.</p>
      <?php if (!isset($_SESSION['user'])): ?>
        <button onclick="window.location.href='<?php echo BASE_URL; ?>/public/login.php?redirect_to=index.php'" 
                class="mt-4 bg-blue-500 text-white px-4 py-2 rounded">
          Login to Access
        </button>
      <?php else: ?>
        <button onclick="window.location.href='<?php echo BASE_URL; ?>/public/practice_papers.php'" 
                class="mt-4 bg-blue-500 text-white px-4 py-2 rounded">
          Get Papers
        </button>
      <?php endif; ?>
      <button onclick="document.getElementById('satPlanModal').classList.add('hidden')" 
              class="mt-2 bg-gray-300 text-gray-800 px-4 py-2 rounded">
        Close
      </button>
    </div>
  </div>

  <div id="tutorModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
    <div class="bg-white p-6 rounded-lg max-w-md w-full">
      <h3 class="text-lg font-bold mb-4">1-on-1 Tutoring</h3>
      <p class="text-gray-700">Get personalized tutoring with our premium plans.</p>
      <?php if (!isset($_SESSION['user'])): ?>
        <button onclick="window.location.href='<?php echo BASE_URL; ?>/public/login.php?redirect_to=upgrade.php'" 
                class="mt-4 bg-blue-500 text-white px-4 py-2 rounded">
          Login to Upgrade
        </button>
      <?php else: ?>
        <button onclick="window.location.href='<?php echo BASE_URL; ?>/public/upgrade.php'" 
                class="mt-4 bg-blue-500 text-white px-4 py-2 rounded">
          Upgrade Now
        </button>
      <?php endif; ?>
      <button onclick="document.getElementById('tutorModal').classList.add('hidden')" 
              class="mt-2 bg-gray-300 text-gray-800 px-4 py-2 rounded">
        Close
      </button>
    </div>
  </div>

  <!-- Sidebar Toggle Script -->
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const sidebar = document.getElementById('sidebar');
      const toggleSidebarMobile = document.getElementById('toggleSidebarMobile');
      const closeSidebar = document.getElementById('closeSidebar');
      const toggleSidebarMinimize = document.getElementById('toggleSidebarMinimize');
      const sidebarToggleIcon = document.getElementById('sidebarToggleIcon');

      // Desktop: Apply minimized state if saved (width >= 768px)
      if (window.innerWidth >= 768) {
        const sidebarState = localStorage.getItem('sidebarState');
        if (sidebarState === 'minimized') {
          sidebar.classList.remove('w-64');
          sidebar.classList.add('w-20');
          if (sidebarToggleIcon) {
            sidebarToggleIcon.classList.remove('fa-chevron-left');
            sidebarToggleIcon.classList.add('fa-chevron-right');
          }
          document.querySelectorAll('.sidebar-text').forEach(el => el.style.opacity = '0');
        }
      }

      // Mobile: Show sidebar
      const showSidebar = function () {
        sidebar.classList.remove('-translate-x-full');
        sidebar.classList.add('translate-x-0');
      };

      // Mobile: Hide sidebar
      const hideSidebar = function () {
        sidebar.classList.remove('translate-x-0');
        sidebar.classList.add('-translate-x-full');
      };

      if (toggleSidebarMobile) {
        toggleSidebarMobile.addEventListener('click', showSidebar);
      }
      if (closeSidebar) {
        closeSidebar.addEventListener('click', hideSidebar);
      }

      // Desktop: Minimize/expand sidebar toggle
      if (toggleSidebarMinimize && window.innerWidth >= 768) {
        toggleSidebarMinimize.addEventListener('click', function () {
          if (sidebar.classList.contains('w-64')) {
            sidebar.classList.remove('w-64');
            sidebar.classList.add('w-20');
            if (sidebarToggleIcon) {
              sidebarToggleIcon.classList.remove('fa-chevron-left');
              sidebarToggleIcon.classList.add('fa-chevron-right');
            }
            document.querySelectorAll('.sidebar-text').forEach(el => el.style.opacity = '0');
            localStorage.setItem('sidebarState', 'minimized');
          } else {
            sidebar.classList.remove('w-20');
            sidebar.classList.add('w-64');
            if (sidebarToggleIcon) {
              sidebarToggleIcon.classList.remove('fa-chevron-right');
              sidebarToggleIcon.classList.add('fa-chevron-left');
            }
            document.querySelectorAll('.sidebar-text').forEach(el => el.style.opacity = '1');
            localStorage.setItem('sidebarState', 'expanded');
          }
        });
      }
    });
  </script>
</body>
</html>