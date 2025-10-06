<?php
// ✅ Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: loginpage.php");
    exit;
}

// ✅ Database connection
$conn = new mysqli("localhost", "root", "", "hackers_gurukul_blog");
if ($conn->connect_error) { 
    die("Connection failed: " . $conn->connect_error); 
}

// ✅ Fetch categories from posts
$categories_sql = "SELECT DISTINCT category FROM posts WHERE category IS NOT NULL AND category != '' ORDER BY category ASC";
$categories = $conn->query($categories_sql);

// ✅ Fetch logged-in user info
$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT username, profile_pic FROM users WHERE id=?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Categories - Hackers Gurukul Blog</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Newsreader:wght@400;700&display=swap" rel="stylesheet"/>
<script>
tailwind.config = {
  darkMode: "class",
  theme: {
    extend: {
      colors: {
        primary: "#1193d4",
        "background-light": "#f6f7f8",
        "background-dark": "#101c22",
      },
      fontFamily: { display: ["Newsreader"] },
    },
  },
};
</script>
</head>
<body class="bg-background-dark font-display text-slate-200">
<div class="flex flex-col min-h-screen">

<!-- 🔹 Navbar -->
<header class="border-b border-slate-800 bg-slate-900 fixed top-0 left-0 right-0 z-30">
  <div class="container mx-auto px-4 flex justify-between items-center py-4">
    <a href="homepage.php" class="flex items-center gap-2">
      <svg class="h-6 w-6 text-primary" fill="none" viewBox="0 0 48 48">
        <path d="M6 6H42L36 24L42 42H6L12 24L6 6Z" fill="currentColor"></path>
      </svg>
      <span class="text-xl font-bold text-white">Hackers Gurukul</span>
    </a>
    <nav class="hidden md:flex gap-8 text-white">
      <a href="homepage.php" class="hover:text-primary">Home</a>
      <a href="categories.php" class="text-primary font-bold">Categories</a>
      <a href="about.php" class="hover:text-primary">About Us</a>
      <a href="contactpage.php" class="hover:text-primary">Contact</a>
    </nav>
    <div class="flex items-center gap-4">
      <?php if($user): ?>
        <a href="user_profile.php" title="<?php echo htmlspecialchars($user['username']); ?>">
          <img src="<?php echo $user['profile_pic'] ?: 'uploads/profile/default.png'; ?>" 
               alt="Profile" class="h-9 w-9 rounded-full border-2 border-primary object-cover"/>
        </a>
      <?php else: ?>
        <a href="loginpage.php" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/80">Login</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<!-- 🔹 Categories Section -->
<main class="container mx-auto px-4 py-24 flex-grow">
  <h1 class="text-3xl font-bold mb-8 flex items-center gap-2 text-white">
    📂 Categories
  </h1>

  <?php if ($categories && $categories->num_rows > 0): ?>
    <?php while($cat = $categories->fetch_assoc()): ?>
      <h2 class="text-2xl font-semibold mt-10 mb-4 text-primary">
        <?php echo htmlspecialchars($cat['category']); ?>
      </h2>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php
          $stmt = $conn->prepare("
              SELECT p.*, u.username 
              FROM posts p 
              LEFT JOIN users u ON p.author_id=u.id
              WHERE p.category=? ORDER BY p.created_at DESC
          ");
          $stmt->bind_param("s", $cat['category']);
          $stmt->execute();
          $posts = $stmt->get_result();

          if ($posts->num_rows > 0):
            while($p = $posts->fetch_assoc()):

              // ✅ Fix image display (check correct column name)
              $imagePath = !empty($p['image_url']) 
                           ? $p['image_url'] 
                           : (!empty($p['image']) ? $p['image'] : '');

              // ✅ Like counts
              $likeCountRes = $conn->query("SELECT COUNT(*) AS total FROM likes WHERE post_id = {$p['id']}");
              $likeCount = $likeCountRes->fetch_assoc()['total'];

              // ✅ Check if user liked
              $userLiked = false;
              if (isset($_SESSION['user_id'])) {
                  $checkLike = $conn->prepare("SELECT 1 FROM likes WHERE post_id=? AND user_id=?");
                  $checkLike->bind_param("ii", $p['id'], $_SESSION['user_id']);
                  $checkLike->execute();
                  $userLiked = $checkLike->get_result()->num_rows > 0;
                  $checkLike->close();
              }
        ?>
          <div class="bg-slate-900 p-4 rounded-lg shadow border border-slate-800 hover:border-primary transition flex flex-col">
            <div class="aspect-video rounded-lg bg-slate-700 mb-3 overflow-hidden">
              <?php if(!empty($imagePath)): ?>
                <img src="<?php echo htmlspecialchars($imagePath); ?>" class="w-full h-full object-cover"/>
              <?php else: ?>
                <div class="w-full h-full flex items-center justify-center text-slate-400 text-sm">No Image</div>
              <?php endif; ?>
            </div>
            <h3 class="font-bold text-lg line-clamp-2">
              <a href="post_detail.php?id=<?php echo $p['id']; ?>" class="hover:text-primary">
                <?php echo htmlspecialchars($p['title']); ?>
              </a>
            </h3>
            <p class="text-sm text-slate-400 mt-2 line-clamp-3 flex-grow">
              <?php echo htmlspecialchars($p['description']); ?>
            </p>
            <p class="mt-2 text-xs text-slate-500">
              By <?php echo htmlspecialchars($p['username'] ?? "Unknown"); ?> · 
              <?php echo date("M d, Y", strtotime($p['created_at'])); ?>
            </p>

            <!-- ❤️ Like Button -->
            <form action="like_post.php" method="POST" class="mt-3">
              <input type="hidden" name="post_id" value="<?php echo $p['id']; ?>">
              <button type="submit" 
                class="flex items-center gap-1 text-sm <?php echo $userLiked ? 'text-primary' : 'text-gray-400 hover:text-primary'; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 18.657l-6.828-6.829a4 4 0 010-5.656z"/>
                </svg>
                <span><?php echo $likeCount; ?></span>
              </button>
            </form>
          </div>
        <?php endwhile; else: ?>
          <p class="text-slate-400 col-span-full italic">No posts found in this category.</p>
        <?php endif; $stmt->close(); ?>
      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <p class="text-slate-400">No categories found.</p>
  <?php endif; ?>
</main>

<!-- 🔹 Footer -->
<?php include 'footer.php'; ?>

</div>
</body>
</html>
