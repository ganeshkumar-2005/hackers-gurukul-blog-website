<?php
session_start();
$conn = new mysqli("localhost", "root", "", "hackers_gurukul_blog");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// ✅ Validate post ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: homepage.php");
    exit;
}
$post_id = (int)$_GET['id'];

// ✅ Fetch post details
$stmt = $conn->prepare("SELECT p.*, u.username FROM posts p 
    LEFT JOIN users u ON p.author_id = u.id WHERE p.id=?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
if (!$post) {
    echo "Post not found.";
    exit;
}

// ✅ Record initial view
$session_id = session_id();
$conn->query("CREATE TABLE IF NOT EXISTS views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    session_id VARCHAR(255),
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (post_id, session_id)
)");
$conn->query("INSERT IGNORE INTO views (post_id, session_id) VALUES ($post_id, '$session_id')");

// ✅ Handle new comment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['comment']) && isset($_SESSION['user_id'])) {
    $comment = trim($_POST['comment']);
    if ($comment !== "") {
        $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $post_id, $_SESSION['user_id'], $comment);
        $stmt->execute();
        header("Location: post_detail.php?id=" . $post_id);
        exit;
    }
}

// ✅ Fetch comments
$stmt = $conn->prepare("SELECT c.*, u.username FROM comments c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.post_id=? ORDER BY c.created_at DESC");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$comments = $stmt->get_result();

// ✅ Like feature
$conn->query("CREATE TABLE IF NOT EXISTS likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY(post_id, user_id)
)");
$likeCountRes = $conn->query("SELECT COUNT(*) AS total FROM likes WHERE post_id = $post_id");
$likeCount = $likeCountRes ? $likeCountRes->fetch_assoc()['total'] : 0;

$userLiked = false;
if (isset($_SESSION['user_id'])) {
    $checkLike = $conn->prepare("SELECT 1 FROM likes WHERE post_id=? AND user_id=?");
    $checkLike->bind_param("ii", $post_id, $_SESSION['user_id']);
    $checkLike->execute();
    $userLiked = $checkLike->get_result()->num_rows > 0;
}

// ✅ Determine media URL
$imagePath = $post['media_url'] ?? $post['image_url'] ?? $post['image'] ?? "";
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?php echo htmlspecialchars($post['title']); ?> - Hackers Gurukul Blog</title>
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
<header class="border-b border-slate-200/80 dark:border-slate-800/80">
  <div class="container mx-auto px-4">
    <nav class="flex items-center justify-between py-4">
      <a href="homepage.php" class="flex items-center gap-2">
        <svg class="h-6 w-6 text-primary" fill="none" viewBox="0 0 48 48">
          <path d="M6 6H42L36 24L42 42H6L12 24L6 6Z" fill="currentColor"></path>
        </svg>
        <span class="text-xl font-bold text-slate-900 dark:text-white">Hackers Gurukul Blog</span>
      </a>
      <div class="hidden md:flex items-center gap-8">
        <a href="homepage.php" class="hover:text-primary">Home</a>
        <a href="categories.php" class="hover:text-primary">Categories</a>
        <a href="about.php" class="hover:text-primary">About</a>
        <a href="contactpage.php" class="hover:text-primary">Contact</a>
      </div>
      <?php if(isset($_SESSION['user_id'])): ?>
        <a href="logout.php" class="px-4 py-2 bg-red-500 text-white rounded-lg text-sm font-bold hover:bg-red-600">Logout</a>
      <?php else: ?>
        <a href="loginpage.php" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-bold hover:bg-primary/90">Login</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<!-- 🔹 Post content -->
<main class="flex-grow container mx-auto px-4 py-8 max-w-3xl">
  <!-- Title -->
  <h1 class="text-4xl font-bold"><?php echo htmlspecialchars($post['title']); ?></h1>
  <?php if($post['description']): ?>
    <p class="mt-2 text-lg text-slate-600 dark:text-slate-300"><?php echo htmlspecialchars($post['description']); ?></p>
  <?php endif; ?>

  <!-- Author + Category -->
  <div class="mt-2 flex items-center gap-3 text-sm text-slate-500">
    <span>By <?php echo htmlspecialchars($post['username'] ?? "Unknown"); ?></span>
    <span>· <?php echo date("M d, Y", strtotime($post['created_at'])); ?></span>
    <?php if($post['category']): ?>
      <span class="bg-primary/10 text-primary px-2 py-0.5 rounded-full"><?php echo htmlspecialchars($post['category']); ?></span>
    <?php endif; ?>
  </div>

  <!-- 📡 Real-time Views -->
  <div id="views-box" class="mt-3 text-sm bg-primary/10 border border-primary/30 rounded-lg p-2">
    👁 <span id="total-views">0</span> total · <span id="active-views">0 people reading now</span>
  </div>

  <!-- Media -->
  <?php if($imagePath): 
    $ext = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
  ?>
    <div class="mt-6">
      <?php if (in_array($ext, ["jpg","jpeg","png","gif","webp"])): ?>
        <img src="<?php echo $imagePath; ?>" class="w-full rounded-lg shadow"/>
      <?php elseif (in_array($ext, ["mp4","webm","ogg"])): ?>
        <video controls class="w-full rounded-lg shadow">
          <source src="<?php echo $imagePath; ?>" type="video/<?php echo $ext; ?>">
        </video>
      <?php else: ?>
        <a href="<?php echo $imagePath; ?>" class="text-primary underline" target="_blank">Download File</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- Content -->
  <article class="prose dark:prose-invert mt-6 max-w-none">
    <?php echo nl2br(htmlspecialchars($post['content'])); ?>
  </article>

  <!-- ❤️ Like button -->
  <div class="mt-6 flex items-center">
    <?php if(isset($_SESSION['user_id'])): ?>
      <button id="likeBtn" data-liked="<?php echo $userLiked ? '1' : '0'; ?>" 
              class="flex items-center gap-1 text-sm <?php echo $userLiked ? 'text-primary' : 'text-gray-400 hover:text-primary'; ?>">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
          <path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 18.657l-6.828-6.829a4 4 0 010-5.656z"/>
        </svg>
        <span id="likeCount"><?php echo $likeCount; ?></span> Likes
      </button>
    <?php else: ?>
      <p class="text-sm text-slate-500">Login to like this post.</p>
    <?php endif; ?>
  </div>

  <!-- 💬 Comments -->
  <section class="mt-12">
    <h2 class="text-2xl font-bold mb-4">Comments</h2>
    <?php if(isset($_SESSION['user_id'])): ?>
      <form method="POST" class="mb-6">
        <textarea name="comment" rows="3" required placeholder="Write your comment..." 
          class="w-full px-4 py-3 border rounded-lg bg-white dark:bg-slate-900 focus:ring-primary"></textarea>
        <button type="submit" class="mt-2 px-6 py-2 bg-primary text-white rounded-lg font-bold hover:bg-primary/90">Post Comment</button>
      </form>
    <?php else: ?>
      <p class="text-slate-500">Please <a href="loginpage.php" class="text-primary font-bold">login</a> to comment.</p>
    <?php endif; ?>

    <div class="space-y-6 mt-6">
      <?php if($comments->num_rows > 0): ?>
        <?php while($c = $comments->fetch_assoc()): ?>
          <div class="border-b border-slate-200 dark:border-slate-700 pb-4">
            <p class="font-bold"><?php echo htmlspecialchars($c['username']); ?></p>
            <p class="text-xs text-slate-500"><?php echo date("M d, Y H:i", strtotime($c['created_at'])); ?></p>
            <p class="mt-2"><?php echo nl2br(htmlspecialchars($c['comment'])); ?></p>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p class="text-slate-500">No comments yet. Be the first!</p>
      <?php endif; ?>
    </div>
  </section>
</main>

<?php include 'footer.php'; ?>

</div>

<!-- ❤️ AJAX Like + Views Script -->
<script>
const postId = <?php echo $post_id; ?>;

// ❤️ Like Button
document.getElementById('likeBtn')?.addEventListener('click', () => {
  fetch('like_post.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'post_id=' + postId
  })
  .then(res => res.json())
  .then(data => {
    if (data.likes !== undefined) {
      document.getElementById('likeCount').textContent = data.likes;
      const btn = document.getElementById('likeBtn');
      btn.dataset.liked = data.liked ? '1' : '0';
      btn.classList.toggle('text-primary', data.liked);
      btn.classList.toggle('text-gray-400', !data.liked);
    }
  });
});

// 👁 Real-time Views
function updateViews() {
  fetch('update_views.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'post_id=' + postId
  })
  .then(res => res.json())
  .then(data => {
    document.getElementById('total-views').textContent = data.total;
    document.getElementById('active-views').textContent = 
      `${data.active} ${data.active == 1 ? 'person' : 'people'} reading now`;
  });
}
updateViews();
setInterval(updateViews, 5000);
</script>
</body>
</html>
