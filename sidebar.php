<?php
// sidebar.php - unified top navbar + admin sidebar

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hide Admin Panel button when set from parent
$hide_admin_button = $hide_admin_button ?? false;

// Session user info
$username = $_SESSION['username'] ?? 'Guest';
$role = $_SESSION['role'] ?? 'user';
$profile_pic = $_SESSION['profile_pic'] ?? 'uploads/profile/default.png';
?>

<!-- 🔹 Top Navbar -->
<header class="w-full bg-slate-900 text-white fixed top-0 left-0 z-40 shadow">
  <div class="container mx-auto px-4 py-3 flex items-center justify-between">
    <!-- Logo -->
    <a href="homepage.php" class="flex items-center gap-2">
      <svg class="h-6 w-6 text-primary" viewBox="0 0 48 48" fill="currentColor">
        <path d="M6 6H42L36 24L42 42H6L12 24L6 6Z"/>
      </svg>
      <span class="text-lg font-bold">Tech Blog</span>
    </a>

    <!-- Desktop Nav -->
    <nav class="hidden md:flex gap-6 items-center">
      <a href="homepage.php" class="text-slate-300 hover:text-primary">Home</a>
      <a href="categories.php" class="text-slate-300 hover:text-primary">Categories</a>
      <a href="contactpage.php" class="text-slate-300 hover:text-primary">Contact</a>
      <?php if (!$hide_admin_button && $role === 'admin'): ?>
        <a href="adminpanel.php" class="px-3 py-1 bg-red-600 rounded">Admin Panel</a>
      <?php endif; ?>
      <a href="create_post.php" class="px-3 py-1 bg-green-600 rounded">+ Create Post</a>
      <a href="user_profile.php">
        <img src="<?php echo htmlspecialchars($profile_pic); ?>" 
             alt="profile" 
             class="h-8 w-8 rounded-full border-2 border-primary object-cover">
      </a>
    </nav>

    <!-- Mobile Nav -->
    <div class="md:hidden flex items-center gap-3">
      <?php if (!$hide_admin_button && $role === 'admin'): ?>
        <a href="adminpanel.php" class="px-2 py-1 bg-red-600 rounded text-sm">Admin</a>
      <?php endif; ?>
      <a href="create_post.php" class="px-2 py-1 bg-green-600 rounded text-sm">Post</a>
    </div>
  </div>
</header>

<!-- 🔹 Left Sidebar (only visible in admin area on desktop) -->
<?php if ($role === 'admin'): ?>
<aside id="left-sidebar" class="hidden md:block w-64 bg-slate-900 text-slate-100 fixed top-16 left-0 bottom-0 p-6 shadow-lg">
  <h3 class="text-xl font-bold mb-6">⚙️ Admin Dashboard</h3>
  <nav class="space-y-3 text-slate-300">
    <a href="adminpanel.php#posts" class="flex items-center gap-3 hover:text-white"><span>📝</span> Posts</a>
    <a href="adminpanel.php#users" class="flex items-center gap-3 hover:text-white"><span>👥</span> Users</a>
    <a href="adminpanel.php#comments" class="flex items-center gap-3 hover:text-white"><span>💬</span> Comments</a>
    <a href="adminpanel.php#logs" class="flex items-center gap-3 hover:text-white"><span>📜</span> Logs</a>
    <a href="homepage.php" class="flex items-center gap-3 hover:text-white mt-6"><span>🏠</span> Back to Site</a>
  </nav>
</aside>
<?php endif; ?>

<!-- 🔹 Ensure layout padding -->
<style>
  body { padding-top: 64px; } /* top navbar height */
  @media (min-width: 768px) {
    body { padding-left: 16rem; } /* sidebar width w-64 = 16rem */
  }
</style>
