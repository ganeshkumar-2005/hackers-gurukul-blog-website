<?php
session_start();

// ✅ Only allow logged-in users
if (!isset($_SESSION['user_id'])) {
    header("Location: loginpage.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "hackers_gurukul_blog");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// 🔎 Search
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$like = "%$search%";

// Featured posts
$sql = "SELECT p.*, u.username FROM posts p 
        LEFT JOIN users u ON p.author_id=u.id
        WHERE p.title LIKE ? OR p.content LIKE ?
        ORDER BY p.created_at DESC LIMIT 2";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $like, $like);
$stmt->execute();
$featured = $stmt->get_result();

// Trending posts
$sql = "SELECT p.*, u.username FROM posts p 
        LEFT JOIN users u ON p.author_id=u.id
        WHERE p.title LIKE ? OR p.content LIKE ?
        ORDER BY p.created_at DESC LIMIT 3";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $like, $like);
$stmt->execute();
$trending = $stmt->get_result();

// Pagination
$limit = 6;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$start = ($page - 1) * $limit;

$count_sql = "SELECT COUNT(*) as total FROM posts WHERE title LIKE ? OR content LIKE ?";
$stmt = $conn->prepare($count_sql);
$stmt->bind_param("ss", $like, $like);
$stmt->execute();
$total_posts = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_posts / $limit);

$sql = "SELECT p.*, u.username FROM posts p 
        LEFT JOIN users u ON p.author_id=u.id
        WHERE p.title LIKE ? OR p.content LIKE ?
        ORDER BY p.created_at DESC LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssii", $like, $like, $start, $limit);
$stmt->execute();
$all_posts = $stmt->get_result();

// Logged-in user info
$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT username, profile_pic, role FROM users WHERE id=?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Hackers Gurukul Blog</title>
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
<body class="bg-background-light dark:bg-background-dark font-display text-slate-800 dark:text-slate-200">
<div class="flex flex-col min-h-screen">

<!-- 🔹 Navbar -->
<header class="border-b border-slate-200/80 dark:border-slate-800/80 bg-slate-900">
  <div class="container mx-auto px-4 flex justify-between items-center py-4">
    <a href="homepage.php" class="flex items-center gap-2">
      <svg class="h-6 w-6 text-primary" fill="none" viewBox="0 0 48 48">
        <path d="M6 6H42L36 24L42 42H6L12 24L6 6Z" fill="currentColor"/>
      </svg>
      <span class="text-xl font-bold text-white">Hackers Gurukul Blog</span>
    </a>

    <nav class="hidden md:flex gap-8 text-white">
      <a href="homepage.php" class="hover:text-primary">Home</a>
      <a href="categories.php" class="hover:text-primary">Categories</a>
      <a href="#" class="hover:text-primary">About Us</a>
      <a href="contactpage.php" class="hover:text-primary">Contact</a>
    </nav>

    <div class="flex items-center gap-4">
      <!-- Search -->
      <form method="GET" action="homepage.php" class="flex">
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
          placeholder="Search" class="px-3 py-1 rounded-l-lg border border-slate-700 bg-slate-800 text-white"/>
        <button type="submit" class="px-4 py-1 bg-primary text-white rounded-r-lg">Search</button>
      </form>

      <!-- ✅ Admin Panel link (only for admins) -->
      <?php if ($user && $user['role'] === 'admin'): ?>
        <a href="adminpanel.php" class="px-4 py-2 bg-red-600 text-white rounded-lg font-semibold">Admin Panel</a>
      <?php endif; ?>

      <!-- Create Post -->
      <a href="create_post.php" class="px-4 py-2 bg-green-600 text-white rounded-lg">+ Create Post</a>

      <!-- Profile -->
      <?php if($user): ?>
        <a href="user_profile.php">
          <img src="<?php echo $user['profile_pic'] ?: 'uploads/profile/default.png'; ?>" 
               alt="Profile" class="h-9 w-9 rounded-full border-2 border-primary"/>
        </a>
      <?php endif; ?>
    </div>
  </div>
</header>

<!-- 🔹 For You + Trending -->
<section class="container mx-auto px-4 py-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
  <div class="lg:col-span-2">
    <h2 class="text-2xl font-bold mb-4">For You</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <?php while($f = $featured->fetch_assoc()): ?>
        <div class="bg-slate-900 p-4 rounded-lg shadow text-white">
          <div class="aspect-video rounded-lg bg-slate-700 mb-3 flex items-center justify-center text-slate-400">
            <?php if(!empty($f['image_url'])): ?>
              <img src="<?php echo $f['image_url']; ?>" class="w-full h-full object-cover rounded-lg"/>
            <?php else: ?>
              <span>No Image</span>
            <?php endif; ?>
          </div>
          <h3 class="font-bold text-lg">
            <a href="post_detail.php?id=<?php echo $f['id']; ?>" class="hover:text-primary">
              <?php echo htmlspecialchars($f['title']); ?>
            </a>
          </h3>
          <?php if(!empty($f['category'])): ?>
            <span class="inline-block mt-1 text-xs px-2 py-1 bg-primary/20 text-primary rounded-full">
              <?php echo htmlspecialchars($f['category']); ?>
            </span>
          <?php endif; ?>
          <p class="text-sm text-slate-400 mt-2"><?php echo htmlspecialchars($f['description']); ?></p>
        </div>
      <?php endwhile; ?>
    </div>
  </div>
  <aside>
    <h2 class="text-2xl font-bold mb-4">Trending Posts</h2>
    <div class="space-y-4">
      <?php while($t = $trending->fetch_assoc()): ?>
        <div>
          <a href="post_detail.php?id=<?php echo $t['id']; ?>" class="font-bold hover:text-primary">
            <?php echo htmlspecialchars($t['title']); ?>
          </a>
          <?php if(!empty($t['category'])): ?>
            <p class="text-xs text-primary"><?php echo htmlspecialchars($t['category']); ?></p>
          <?php endif; ?>
          <p class="text-sm text-slate-400"><?php echo htmlspecialchars($t['description']); ?></p>
        </div>
      <?php endwhile; ?>
    </div>
  </aside>
</section>

<!-- 🔹 All Posts -->
<section class="container mx-auto px-4 py-8">
  <h2 class="text-2xl font-bold mb-4">All Posts</h2>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php while($p = $all_posts->fetch_assoc()): ?>
      <div class="bg-slate-900 p-4 rounded-lg shadow text-white">
        <div class="aspect-video rounded-lg bg-slate-700 mb-3 flex items-center justify-center text-slate-400">
          <?php if(!empty($p['image_url'])): ?>
            <img src="<?php echo $p['image_url']; ?>" class="w-full h-full object-cover rounded-lg"/>
          <?php else: ?>
            <span>No Image</span>
          <?php endif; ?>
        </div>
        <h3 class="font-bold text-lg">
          <a href="post_detail.php?id=<?php echo $p['id']; ?>" class="hover:text-primary">
            <?php echo htmlspecialchars($p['title']); ?>
          </a>
        </h3>
        <?php if(!empty($p['category'])): ?>
          <span class="inline-block mt-1 text-xs px-2 py-1 bg-primary/20 text-primary rounded-full">
            <?php echo htmlspecialchars($p['category']); ?>
          </span>
        <?php endif; ?>
        <p class="text-sm text-slate-400 mt-2"><?php echo htmlspecialchars($p['description']); ?></p>
        <p class="mt-2 text-xs text-slate-500">By <?php echo $p['username'] ?? "Unknown"; ?> · <?php echo date("M d, Y", strtotime($p['created_at'])); ?></p>
      </div>
    <?php endwhile; ?>
  </div>
  <?php if($total_pages > 1): ?>
    <div class="flex justify-center mt-8 gap-2">
      <?php for($i=1; $i<=$total_pages; $i++): ?>
        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"
           class="px-3 py-1 rounded <?php echo ($i==$page) ? 'bg-primary text-white' : 'bg-slate-700 text-white'; ?>">
           <?php echo $i; ?>
        </a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</section>

<?php include 'footer.php'; ?>
</div>
</body>
</html>
