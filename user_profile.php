<?php
// user_profile.php — fully updated

if (session_status() === PHP_SESSION_NONE) session_start();
$conn = new mysqli("localhost", "root", "", "hackers_gurukul_blog");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

if (empty($_SESSION['user_id'])) {
  header("Location: loginpage.php");
  exit;
}
$user_id = (int)$_SESSION['user_id'];

// CSRF token
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
$CSRF = $_SESSION['csrf'];

function flash($msg, $type='info'){
  $_SESSION['flash'][] = ['t'=>$type,'m'=>$msg];
}
if (!isset($_SESSION['flash'])) $_SESSION['flash'] = [];

/* ---------- GET actions (with CSRF) ---------- */
if (isset($_GET['action'], $_GET['token']) && hash_equals($CSRF, $_GET['token'])) {
  if ($_GET['action'] === 'delete' && isset($_GET['id']) && ctype_digit($_GET['id'])) {
    $pid = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM posts WHERE id=? AND author_id=?");
    $stmt->bind_param("ii", $pid, $user_id);
    $stmt->execute();
    $stmt->close();
    flash("🗑️ Post deleted successfully!", "success");
    header("Location: user_profile.php"); exit;
  }

  if ($_GET['action'] === 'publish' && isset($_GET['id']) && ctype_digit($_GET['id'])) {
    $pid = (int)$_GET['id'];
    $stmt = $conn->prepare("UPDATE posts SET status='published' WHERE id=? AND author_id=?");
    $stmt->bind_param("ii", $pid, $user_id);
    $stmt->execute();
    $stmt->close();
    flash("🚀 Draft published successfully!", "success");
    header("Location: user_profile.php"); exit;
  }
} elseif (isset($_GET['action'])) {
  flash("⚠️ Invalid or missing security token.", "error");
  header("Location: user_profile.php"); exit;
}

/* ---------- Fetch user (incl. password for verification) ---------- */
$stmt = $conn->prepare("SELECT username,email,mobile,bio,profile_pic,password FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$user) { die("User not found."); }

/* ---------- POST: Profile Update ---------- */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_profile'])) {
  if (!hash_equals($CSRF, $_POST['csrf'] ?? '')) {
    flash("⚠️ Invalid security token.", "error");
    header("Location: user_profile.php"); exit;
  }

  $username = trim($_POST['username'] ?? '');
  $email    = trim($_POST['email'] ?? '');
  $mobile   = trim($_POST['mobile'] ?? '');
  $bio      = trim($_POST['bio'] ?? '');

  // Basic validation
  if ($username === '' || $email === '') {
    flash("Name and Email are required.", "error");
    header("Location: user_profile.php"); exit;
  }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash("Please enter a valid email address.", "error");
    header("Location: user_profile.php"); exit;
  }

  // Uniqueness check for username/email
  $chk = $conn->prepare("SELECT id FROM users WHERE (email=? OR username=?) AND id<>? LIMIT 1");
  $chk->bind_param("ssi", $email, $username, $user_id);
  $chk->execute();
  $dup = $chk->get_result()->fetch_assoc();
  $chk->close();
  if ($dup) {
    flash("Username or Email is already taken.", "error");
    header("Location: user_profile.php"); exit;
  }

  // Handle avatar (safe types + size)
  $new_pic_path = null;
  if (!empty($_FILES['profile_pic']['name']) && is_uploaded_file($_FILES['profile_pic']['tmp_name'])) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($_FILES['profile_pic']['tmp_name']);
    $allowed = ['image/jpeg'=>'.jpg','image/png'=>'.png','image/webp'=>'.webp','image/gif'=>'.gif'];
    if (!isset($allowed[$mime])) {
      flash("Invalid image type. Allowed: JPG, PNG, WEBP, GIF.", "error");
      header("Location: user_profile.php"); exit;
    }
    if ($_FILES['profile_pic']['size'] > 2*1024*1024) {
      flash("Image too large (max 2MB).", "error");
      header("Location: user_profile.php"); exit;
    }
    $dir = "uploads/profile/";
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    $new_pic_path = $dir . time() . "_" . bin2hex(random_bytes(4)) . $allowed[$mime];
    if (!move_uploaded_file($_FILES['profile_pic']['tmp_name'], $new_pic_path)) {
      $new_pic_path = null;
      flash("Failed to upload image.", "error");
      header("Location: user_profile.php"); exit;
    }
  }

  // Update DB
  if ($new_pic_path) {
    $stmt = $conn->prepare("UPDATE users SET username=?, email=?, mobile=?, bio=?, profile_pic=? WHERE id=?");
    $stmt->bind_param("sssssi", $username,$email,$mobile,$bio,$new_pic_path,$user_id);
  } else {
    $stmt = $conn->prepare("UPDATE users SET username=?, email=?, mobile=?, bio=? WHERE id=?");
    $stmt->bind_param("ssssi", $username,$email,$mobile,$bio,$user_id);
  }

  if ($stmt->execute()) {
    // remove old file if replaced (only if it lives in uploads/profile)
    if ($new_pic_path && !empty($user['profile_pic']) && str_starts_with($user['profile_pic'], 'uploads/profile/') && file_exists($user['profile_pic'])) {
      @unlink($user['profile_pic']);
    }
    flash("✅ Profile updated successfully!", "success");
  } else {
    flash("DB error: ".$stmt->error, "error");
  }
  $stmt->close();
  header("Location: user_profile.php"); exit;
}

/* ---------- POST: Change Password ---------- */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['change_password'])) {
  if (!hash_equals($CSRF, $_POST['csrf'] ?? '')) {
    flash("⚠️ Invalid security token.", "error");
    header("Location: user_profile.php"); exit;
  }

  $current = $_POST['current_password'] ?? '';
  $new     = $_POST['new_password'] ?? '';
  $confirm = $_POST['confirm_password'] ?? '';

  if ($current==='' || $new==='' || $confirm==='') {
    flash("All password fields are required.", "error");
    header("Location: user_profile.php"); exit;
  }
  if (!password_verify($current, $user['password'])) {
    flash("Your current password is incorrect.", "error");
    header("Location: user_profile.php"); exit;
  }
  if (strlen($new) < 8) {
    flash("New password must be at least 8 characters.", "error");
    header("Location: user_profile.php"); exit;
  }
  if ($new !== $confirm) {
    flash("New password and confirmation do not match.", "error");
    header("Location: user_profile.php"); exit;
  }

  $hash = password_hash($new, PASSWORD_DEFAULT);
  $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
  $stmt->bind_param("si", $hash, $user_id);
  if ($stmt->execute()) {
    flash("🔐 Password changed successfully.", "success");
  } else {
    flash("Failed to update password.", "error");
  }
  $stmt->close();
  header("Location: user_profile.php"); exit;
}

/* ---------- Stats ---------- */
$total_posts     = (int)($conn->query("SELECT COUNT(*) c FROM posts WHERE author_id=$user_id")->fetch_assoc()['c'] ?? 0);
$total_comments  = (int)($conn->query("SELECT COUNT(*) c FROM comments WHERE user_id=$user_id")->fetch_assoc()['c'] ?? 0);
$hasLikesTbl     = (bool)$conn->query("SHOW TABLES LIKE 'likes'")->num_rows;
$total_likes     = $hasLikesTbl ? (int)($conn->query("SELECT COUNT(*) c FROM likes WHERE user_id=$user_id")->fetch_assoc()['c'] ?? 0) : 0;

$recent_posts    = (int)($conn->query("SELECT COUNT(*) c FROM posts WHERE author_id=$user_id AND created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetch_assoc()['c'] ?? 0);
$recent_comments = (int)($conn->query("SELECT COUNT(*) c FROM comments WHERE user_id=$user_id AND created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetch_assoc()['c'] ?? 0);
$recent_likes    = $hasLikesTbl ? (int)($conn->query("SELECT COUNT(*) c FROM likes WHERE user_id=$user_id AND created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetch_assoc()['c'] ?? 0) : 0;

$post_change    = $total_posts ? round(($recent_posts / $total_posts) * 100) : 0;
$comment_change = $total_comments ? round(($recent_comments / $total_comments) * 100) : 0;
$like_change    = $total_likes ? round(($recent_likes / $total_likes) * 100) : 0;

/* ---------- Posts lists ---------- */
$published = $conn->prepare("SELECT id,title,created_at FROM posts WHERE author_id=? AND status='published' ORDER BY created_at DESC");
$published->bind_param("i", $user_id); $published->execute(); $published_posts = $published->get_result();
$drafts = $conn->prepare("SELECT id,title,created_at FROM posts WHERE author_id=? AND status='draft' ORDER BY created_at DESC");
$drafts->bind_param("i", $user_id); $drafts->execute(); $draft_posts = $drafts->get_result();

$published_count = $published_posts->num_rows;
$draft_count     = $draft_posts->num_rows;

// Profile completeness
$fields = [
  "Username" => !empty($user['username']),
  "Email" => !empty($user['email']),
  "Mobile" => !empty($user['mobile']),
  "Bio" => !empty($user['bio']),
  "Profile Picture" => !empty($user['profile_pic'])
];
$percent_complete = round((array_sum($fields) / count($fields)) * 100);
$stars = (int)ceil($percent_complete / 20);
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
  <meta charset="utf-8"/>
  <title>User Profile - Hackers Gurukul Blog</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Newsreader:wght@400;700&display=swap" rel="stylesheet"/>
</head>
<body class="min-h-screen bg-gradient-to-br from-gray-900 via-slate-900 to-black font-display text-gray-200">
<div class="flex flex-col min-h-screen">

<header class="bg-slate-900 border-b border-slate-800 sticky top-0 z-50">
  <div class="container mx-auto px-4 flex justify-between items-center py-4">
    <a href="homepage.php" class="flex items-center gap-2">
      <svg class="h-6 w-6 text-primary" viewBox="0 0 48 48" fill="none"><path d="M6 6H42L36 24L42 42H6L12 24L6 6Z" fill="currentColor"/></svg>
      <span class="text-xl font-bold text-white">Hackers Gurukul Blog</span>
    </a>
    <a href="logout.php" class="px-4 py-2 bg-red-500 rounded-lg hover:bg-red-600">Logout</a>
  </div>
</header>

<main class="container mx-auto px-4 py-8 max-w-6xl grid grid-cols-1 lg:grid-cols-3 gap-8">

  <!-- Left: Profile card -->
  <div class="bg-slate-900/90 p-6 rounded-2xl shadow-lg">
    <div class="flex flex-col items-center text-center">
      <img id="profilePreview" src="<?php echo htmlspecialchars($user['profile_pic'] ?: 'uploads/profile/default.png'); ?>" class="w-28 h-28 rounded-full object-cover mb-3 ring-4 ring-primary/50" alt="Avatar">
      <h2 class="text-2xl font-bold"><?php echo htmlspecialchars($user['username']); ?></h2>
      <p class="text-gray-400"><?php echo htmlspecialchars($user['email']); ?></p>
    </div>

    <?php if (!empty($_SESSION['flash'])): ?>
      <div class="mt-4 space-y-2">
        <?php foreach($_SESSION['flash'] as $f): ?>
          <div class="px-3 py-2 rounded <?php echo $f['t']==='success'?'bg-green-600/20 text-green-200':($f['t']==='error'?'bg-red-600/20 text-red-200':'bg-slate-700/50 text-slate-100'); ?>">
            <?php echo $f['m']; ?>
          </div>
        <?php endforeach; $_SESSION['flash']=[]; ?>
      </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="mt-5 space-y-4">
      <input type="hidden" name="csrf" value="<?php echo $CSRF; ?>">
      <div>
        <label class="block text-sm font-medium">Name</label>
        <input name="username" value="<?php echo htmlspecialchars($user['username']); ?>" class="w-full px-3 py-2 rounded bg-slate-800 text-white">
      </div>
      <div>
        <label class="block text-sm font-medium">Email</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full px-3 py-2 rounded bg-slate-800 text-white">
      </div>
      <div>
        <label class="block text-sm font-medium">Mobile</label>
        <input name="mobile" value="<?php echo htmlspecialchars($user['mobile'] ?? ''); ?>" class="w-full px-3 py-2 rounded bg-slate-800 text-white">
      </div>
      <div>
        <label class="block text-sm font-medium">Bio</label>
        <textarea name="bio" rows="3" class="w-full px-3 py-2 rounded bg-slate-800 text-white"><?php echo htmlspecialchars($user['bio']); ?></textarea>
      </div>
      <div>
        <label class="block text-sm font-medium">Change Profile Picture</label>
        <input type="file" name="profile_pic" accept="image/*" onchange="previewImage(event)" class="text-sm">
      </div>
      <button type="submit" name="update_profile" class="w-full px-6 py-2 bg-primary hover:bg-primary/80 text-white rounded-lg">
        Update Profile
      </button>
    </form>

    <!-- Password change -->
    <div class="mt-8 border-t border-slate-800 pt-6">
      <h3 class="text-lg font-bold mb-3">Change Password</h3>
      <form method="POST" class="space-y-3">
        <input type="hidden" name="csrf" value="<?php echo $CSRF; ?>">
        <input type="password" name="current_password" placeholder="Current password" class="w-full px-3 py-2 rounded bg-slate-800 text-white">
        <input type="password" name="new_password" placeholder="New password (min 8 chars)" class="w-full px-3 py-2 rounded bg-slate-800 text-white">
        <input type="password" name="confirm_password" placeholder="Confirm new password" class="w-full px-3 py-2 rounded bg-slate-800 text-white">
        <button type="submit" name="change_password" class="w-full px-6 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg">Update Password</button>
      </form>
    </div>

    <!-- Completeness -->
    <div class="mt-8 bg-slate-800/80 p-5 rounded-lg shadow">
      <h2 class="text-lg font-bold mb-4">Profile Completeness</h2>
      <div class="w-full bg-gray-700 rounded-full h-2.5 mb-2">
        <div class="bg-green-500 h-2.5 rounded-full" style="width: <?php echo $percent_complete; ?>%"></div>
      </div>
      <p class="text-xs text-gray-400"><?php echo $percent_complete; ?>% Complete</p>
      <div class="flex justify-center mt-3">
        <?php for($i=1;$i<=5;$i++): ?>
          <span class="<?php echo $i <= $stars ? 'text-yellow-400' : 'text-gray-600'; ?>">★</span>
        <?php endfor; ?>
      </div>
    </div>
  </div>

  <!-- Right: Activity + Posts -->
  <div class="lg:col-span-2 flex flex-col gap-8">
    <div class="bg-slate-900/90 p-6 rounded-2xl shadow-lg">
      <h2 class="text-lg font-bold mb-4">Recent Activity</h2>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div class="p-4 border border-gray-700 rounded-xl">
          <p class="text-sm text-gray-400">Posts</p>
          <p class="text-3xl font-bold"><?php echo $total_posts; ?></p>
          <p class="text-xs <?php echo $post_change>0?'text-green-500':($post_change<0?'text-red-500':'text-gray-400'); ?>"><?php echo $post_change; ?>% from last week</p>
        </div>
        <div class="p-4 border border-gray-700 rounded-xl">
          <p class="text-sm text-gray-400">Comments</p>
          <p class="text-3xl font-bold"><?php echo $total_comments; ?></p>
          <p class="text-xs <?php echo $comment_change>0?'text-green-500':($comment_change<0?'text-red-500':'text-gray-400'); ?>"><?php echo $comment_change; ?>% from last week</p>
        </div>
        <div class="p-4 border border-gray-700 rounded-xl">
          <p class="text-sm text-gray-400">Likes</p>
          <p class="text-3xl font-bold"><?php echo $total_likes; ?></p>
          <p class="text-xs <?php echo $like_change>0?'text-green-500':($like_change<0?'text-red-500':'text-gray-400'); ?>"><?php echo $like_change; ?>% from last week</p>
        </div>
      </div>
    </div>

    <div class="bg-slate-900/90 p-6 rounded-2xl shadow-lg">
      <h2 class="text-lg font-bold mb-4">Your Posts</h2>
      <div class="flex border-b mb-6">
        <button id="tabPublished" class="px-4 py-2 font-semibold border-b-2 border-primary text-primary">
          Published <span class="ml-1 text-xs bg-primary/10 px-2 py-0.5 rounded-full"><?php echo $published_count; ?></span>
        </button>
        <button id="tabDrafts" class="px-4 py-2 font-semibold text-gray-400 hover:text-primary">
          Drafts <span class="ml-1 text-xs bg-yellow-200/20 text-yellow-300 px-2 py-0.5 rounded-full"><?php echo $draft_count; ?></span>
        </button>
      </div>

      <div id="publishedSection">
        <?php if($published_count): while($p=$published_posts->fetch_assoc()): ?>
          <div class="p-4 border border-gray-700 rounded-lg flex justify-between items-center mb-3">
            <div>
              <a href="post_detail.php?id=<?php echo (int)$p['id']; ?>" class="font-semibold hover:text-primary"><?php echo htmlspecialchars($p['title']); ?></a>
              <p class="text-xs text-gray-400"><?php echo date("M d, Y", strtotime($p['created_at'])); ?></p>
            </div>
            <div class="flex gap-2">
              <a href="post_edit.php?post_id=<?php echo (int)$p['id']; ?>" class="px-3 py-1 bg-blue-500 text-white text-sm rounded-lg">Edit</a>
              <a href="user_profile.php?action=delete&id=<?php echo (int)$p['id']; ?>&token=<?php echo $CSRF; ?>" onclick="return confirm('Delete this post?')" class="px-3 py-1 bg-red-500 text-white text-sm rounded-lg">Delete</a>
            </div>
          </div>
        <?php endwhile; else: ?>
          <p class="text-gray-400">No published posts yet.</p>
        <?php endif; ?>
      </div>

      <div id="draftSection" class="hidden">
        <?php if($draft_count): while($d=$draft_posts->fetch_assoc()): ?>
          <div class="p-4 border border-gray-700 rounded-lg flex justify-between items-center mb-3 bg-slate-800/60">
            <div>
              <span class="font-semibold text-yellow-300"><?php echo htmlspecialchars($d['title']); ?></span>
              <p class="text-xs text-gray-400"><?php echo date("M d, Y", strtotime($d['created_at'])); ?></p>
            </div>
            <div class="flex gap-2">
              <a href="post_edit.php?post_id=<?php echo (int)$d['id']; ?>" class="px-3 py-1 bg-blue-500 text-white text-sm rounded-lg">Edit</a>
              <a href="user_profile.php?action=publish&id=<?php echo (int)$d['id']; ?>&token=<?php echo $CSRF; ?>" onclick="return confirm('Publish this draft?')" class="px-3 py-1 bg-green-600 text-white text-sm rounded-lg">Publish</a>
              <a href="user_profile.php?action=delete&id=<?php echo (int)$d['id']; ?>&token=<?php echo $CSRF; ?>" onclick="return confirm('Delete this draft?')" class="px-3 py-1 bg-red-500 text-white text-sm rounded-lg">Delete</a>
            </div>
          </div>
        <?php endwhile; else: ?>
          <p class="text-gray-400">No drafts saved yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>

<?php include 'footer.php'; ?>
</div>

<script>
function previewImage(e){
  const f = e.target.files[0];
  if(!f) return;
  const r = new FileReader();
  r.onload = ev => { document.getElementById('profilePreview').src = ev.target.result; };
  r.readAsDataURL(f);
}

document.getElementById("tabPublished").addEventListener("click", function(){
  document.getElementById("publishedSection").classList.remove("hidden");
  document.getElementById("draftSection").classList.add("hidden");
  this.classList.add("text-primary","border-b-2","border-primary");
  document.getElementById("tabDrafts").classList.remove("text-primary","border-b-2","border-primary");
});
document.getElementById("tabDrafts").addEventListener("click", function(){
  document.getElementById("draftSection").classList.remove("hidden");
  document.getElementById("publishedSection").classList.add("hidden");
  this.classList.add("text-primary","border-b-2","border-primary");
  document.getElementById("tabPublished").classList.remove("text-primary","border-b-2","border-primary");
});
</script>
</body>
</html>
