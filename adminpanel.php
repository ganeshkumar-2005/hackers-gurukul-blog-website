<?php
/******************************************************
 * Admin Panel — Single File, Fully Updated (separate page_edit.php)
 * ----------------------------------------------------
 * Features:
 * - Analytics (top stats)
 * - Quick Add Post (goes to post_edit.php)
 * - Manage Posts (search, edit, delete)
 * - Manage Users (add/edit/promote/demote/delete)
 * - Manage Comments (delete)
 * - Manage Contact Messages (reply via mailto, delete)
 * - Manage Categories (add/rename/delete; shows slug + post counts)
 * - Static Pages (About/Privacy Policy/Terms) — SEPARATE editor (page_edit.php)
 * - Profile Settings (username/email/mobile/password + avatar upload)
 * - Tailwind UI consistent with homepage (dark)
 *
 * DB Notes (adjust if needed):
 * ----------------------------------------------------
 * CREATE TABLE IF NOT EXISTS pages (
 *   id INT AUTO_INCREMENT PRIMARY KEY,
 *   slug VARCHAR(120) NOT NULL UNIQUE,  -- 'about','privacy-policy','terms'
 *   title VARCHAR(200) NOT NULL,
 *   content MEDIUMTEXT NOT NULL,
 *   updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 *
 * INSERT IGNORE INTO pages (slug, title, content) VALUES
 * ('about','About Us','# About Us\n\nEdit from admin panel.'),
 * ('privacy-policy','Privacy Policy','# Privacy Policy\n\nEdit from admin panel.'),
 * ('terms','Terms & Conditions','# Terms & Conditions\n\nEdit from admin panel.');
 *
 * CREATE TABLE IF NOT EXISTS categories (
 *   id INT AUTO_INCREMENT PRIMARY KEY,
 *   name VARCHAR(100) NOT NULL UNIQUE,
 *   slug VARCHAR(120) NOT NULL UNIQUE,
 *   created_at DATETIME DEFAULT CURRENT_TIMESTAMP
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 *
 * CREATE TABLE IF NOT EXISTS activity_logs (
 *   id INT AUTO_INCREMENT PRIMARY KEY,
 *   user_id INT NULL,
 *   action VARCHAR(255),
 *   details TEXT,
 *   created_at DATETIME DEFAULT CURRENT_TIMESTAMP
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 ******************************************************/

if (session_status() === PHP_SESSION_NONE) session_start();
$conn = new mysqli("localhost", "root", "", "hackers_gurukul_blog");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Auth (admin only)
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
  header("Location: homepage.php"); exit;
}

// Helpers
function e($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
function log_action($conn,$user_id,$action,$details=""){
  $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details, created_at) VALUES (?,?,?,NOW())");
  if ($stmt){ $stmt->bind_param("iss",$user_id,$action,$details); $stmt->execute(); $stmt->close(); }
}
function slugify($text) {
  $text = preg_replace('~[^\pL\d]+~u', '-', $text);
  $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
  $text = preg_replace('~[^-\w]+~', '', $text);
  $text = trim($text, '-');
  $text = preg_replace('~-+~', '-', $text);
  $text = strtolower($text);
  return $text ?: 'n-a';
}

// Static pages (SEPARATE editor)
$STATIC_PAGES = [
  'about'           => ['title' => 'About Us',           'view' => 'about.php'           ],
  'privacy-policy'  => ['title' => 'Privacy Policy',     'view' => 'privacy-policy.php'  ],
  'terms'           => ['title' => 'Terms & Conditions', 'view' => 'terms.php'           ],
];

// Seed function for pages table
function seed_pages($conn, $map) {
  foreach ($map as $slug => $meta) {
    $q = $conn->prepare("SELECT 1 FROM pages WHERE slug=? LIMIT 1");
    $q->bind_param("s",$slug);
    $q->execute();
    $exists = $q->get_result()->fetch_row();
    $q->close();
    if (!$exists) {
      $content = "# {$meta['title']}\n\nEdit from admin panel.";
      $ins = $conn->prepare("INSERT INTO pages (slug, title, content, updated_at) VALUES (?,?,?,NOW())");
      $ins->bind_param("sss",$slug,$meta['title'],$content);
      $ins->execute();
      $ins->close();
    }
  }
}

// Current admin user
$me = null;
$meStmt = $conn->prepare("SELECT id, username, email, mobile, profile_pic FROM users WHERE id=? LIMIT 1");
$meStmt->bind_param("i", $_SESSION['user_id']);
$meStmt->execute();
$me = $meStmt->get_result()->fetch_assoc();
$meStmt->close();

$info_msg = $error_msg = "";

/* ==============================
   GET actions (redirect after)
   ============================== */
if (isset($_GET['promote'])) {
  $uid = (int)$_GET['promote'];
  $stmt = $conn->prepare("UPDATE users SET role='admin' WHERE id=?");
  if ($stmt){ $stmt->bind_param("i",$uid); $stmt->execute(); $stmt->close(); }
  log_action($conn,$_SESSION['user_id'],"Promote User","User ID: $uid");
  header("Location: adminpanel.php#users"); exit;
}
if (isset($_GET['demote'])) {
  $uid = (int)$_GET['demote'];
  if ($uid !== (int)$_SESSION['user_id']) {
    $stmt = $conn->prepare("UPDATE users SET role='user' WHERE id=?");
    if ($stmt){ $stmt->bind_param("i",$uid); $stmt->execute(); $stmt->close(); }
    log_action($conn,$_SESSION['user_id'],"Demote User","User ID: $uid");
  }
  header("Location: adminpanel.php#users"); exit;
}
if (isset($_GET['delete_user'])) {
  $uid = (int)$_GET['delete_user'];
  if ($uid !== (int)$_SESSION['user_id']) {
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    if ($stmt){ $stmt->bind_param("i",$uid); $stmt->execute(); $stmt->close(); }
    log_action($conn,$_SESSION['user_id'],"Delete User","User ID: $uid");
  }
  header("Location: adminpanel.php#users"); exit;
}
if (isset($_GET['delete_post'])) {
  $pid = (int)$_GET['delete_post'];
  $stmt = $conn->prepare("DELETE FROM posts WHERE id=?");
  if ($stmt){ $stmt->bind_param("i",$pid); $stmt->execute(); $stmt->close(); }
  log_action($conn,$_SESSION['user_id'],"Delete Post","Post ID: $pid");
  header("Location: adminpanel.php#posts"); exit;
}
if (isset($_GET['delete_comment'])) {
  $cid = (int)$_GET['delete_comment'];
  $stmt = $conn->prepare("DELETE FROM comments WHERE id=?");
  if ($stmt){ $stmt->bind_param("i",$cid); $stmt->execute(); $stmt->close(); }
  log_action($conn,$_SESSION['user_id'],"Delete Comment","Comment ID: $cid");
  header("Location: adminpanel.php#comments"); exit;
}
if (isset($_GET['delete_message'])) {
  $mid = (int)$_GET['delete_message'];
  $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id=?");
  if ($stmt){ $stmt->bind_param("i",$mid); $stmt->execute(); $stmt->close(); }
  log_action($conn,$_SESSION['user_id'],"Delete Message","Message ID: $mid");
  header("Location: adminpanel.php#messages"); exit;
}

// Seed pages button
if (isset($_GET['seed_pages'])) {
  seed_pages($conn, $STATIC_PAGES);
  header("Location: adminpanel.php?seeded=1#pages"); exit;
}

/* ==============================
   POST actions
   ============================== */
// Quick add post → redirect to post_edit.php
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['quick_add_post'])) {
  $title = trim($_POST['q_title'] ?? '');
  $desc  = trim($_POST['q_description'] ?? '');
  $cat   = trim($_POST['q_category'] ?? '');
  if ($title && $desc && $cat) {
    $stmt = $conn->prepare("INSERT INTO posts (title, description, category, content, author_id, status, created_at) VALUES (?,?,?,?,?,'published',NOW())");
    $blankContent = "";
    $stmt->bind_param("ssssi", $title, $desc, $cat, $blankContent, $_SESSION['user_id']);
    if ($stmt->execute()) {
      $newId = $stmt->insert_id;
      $stmt->close();
      log_action($conn,$_SESSION['user_id'],"Quick Add Post","Post ID: $newId");
      header("Location: post_edit.php?post_id=".$newId); exit;
    } else {
      $error_msg = "Failed to create post: ".$stmt->error;
      $stmt->close();
    }
  } else { $error_msg = "Please fill Title, Description, and Category."; }
}

// Add user
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_user'])) {
  $username = trim($_POST['username'] ?? '');
  $email    = trim($_POST['email'] ?? '');
  $mobile   = trim($_POST['mobile'] ?? '');
  $pass     = $_POST['password'] ?? '';
  if ($username && $email && $mobile && $pass) {
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username,email,mobile,password,role,created_at) VALUES (?,?,?,?,'user',NOW())");
    if ($stmt){
      $stmt->bind_param("ssss",$username,$email,$mobile,$hash);
      if ($stmt->execute()){
        log_action($conn,$_SESSION['user_id'],"Add User","Username: $username");
        $stmt->close(); header("Location: adminpanel.php#users"); exit;
      } else { $error_msg = "DB error: ".$stmt->error; $stmt->close(); }
    } else { $error_msg = "DB prepare error: ".$conn->error; }
  } else { $error_msg = "All user fields are required."; }
}

// Update user
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_user'])) {
  $uid = (int)($_POST['id'] ?? 0);
  $username = trim($_POST['username'] ?? '');
  $email    = trim($_POST['email'] ?? '');
  $mobile   = trim($_POST['mobile'] ?? '');
  $pass     = $_POST['password'] ?? '';
  if ($username && $email && $mobile) {
    if ($pass !== '') {
      $hash = password_hash($pass, PASSWORD_DEFAULT);
      $stmt = $conn->prepare("UPDATE users SET username=?,email=?,mobile=?,password=? WHERE id=?");
      $stmt->bind_param("ssssi",$username,$email,$mobile,$hash,$uid);
    } else {
      $stmt = $conn->prepare("UPDATE users SET username=?,email=?,mobile=? WHERE id=?");
      $stmt->bind_param("sssi",$username,$email,$mobile,$uid);
    }
    if ($stmt->execute()){ log_action($conn,$_SESSION['user_id'],"Edit User","User ID: $uid"); $stmt->close(); header("Location: adminpanel.php#users"); exit; }
    else { $error_msg = "Error updating user: ".$stmt->error; $stmt->close(); }
  } else { $error_msg = "Username, Email and Mobile are required."; }
}

// Inline edit which user?
$edit_user_data = null;
if (isset($_GET['edit_user'])) {
  $uid = (int)$_GET['edit_user'];
  $stmt = $conn->prepare("SELECT id, username, email, mobile FROM users WHERE id=? LIMIT 1");
  $stmt->bind_param("i",$uid); $stmt->execute();
  $edit_user_data = $stmt->get_result()->fetch_assoc();
  $stmt->close();
}

/* Categories: add, rename, delete */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_category'])) {
  $name = trim($_POST['cat_name'] ?? '');
  if ($name) {
    $slug = slugify($name);
    $stmt = $conn->prepare("INSERT INTO categories (name, slug, created_at) VALUES (?,?,NOW())");
    if ($stmt){ 
      $stmt->bind_param("ss",$name,$slug); 
      if($stmt->execute()){ log_action($conn,$_SESSION['user_id'],"Add Category",$name); } 
      else { $error_msg="Category add failed: ".$stmt->error; } 
      $stmt->close(); 
    }
  } else { $error_msg = "Category name is required."; }
  header("Location: adminpanel.php#categories"); exit;
}
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['rename_category'])) {
  $id = (int)($_POST['cat_id'] ?? 0);
  $name = trim($_POST['new_name'] ?? '');
  if ($id && $name) {
    $slug = slugify($name);
    $stmt = $conn->prepare("UPDATE categories SET name=?, slug=? WHERE id=?");
    if ($stmt){ 
      $stmt->bind_param("ssi",$name,$slug,$id); 
      if($stmt->execute()){ log_action($conn,$_SESSION['user_id'],"Rename Category","ID: $id to $name"); } 
      else { $error_msg="Rename failed: ".$stmt->error; } 
      $stmt->close(); 
    }
  } else { $error_msg = "Provide valid category and new name."; }
  header("Location: adminpanel.php#categories"); exit;
}
if (isset($_GET['delete_category'])) {
  $cid = (int)$_GET['delete_category'];
  // optional: forbid delete if posts exist in that category (by name, legacy schema)
  $cnt = 0;
  $c = $conn->prepare("SELECT COUNT(*) c FROM posts WHERE category IN (SELECT name FROM categories WHERE id=?)");
  if ($c){ $c->bind_param("i",$cid); $c->execute(); $cnt = (int)$c->get_result()->fetch_assoc()['c']; $c->close(); }
  if ($cnt>0) {
    $error_msg = "Cannot delete: category has posts.";
  } else {
    $d = $conn->prepare("DELETE FROM categories WHERE id=?");
    if ($d){ $d->bind_param("i",$cid); $d->execute(); $d->close(); log_action($conn,$_SESSION['user_id'],"Delete Category","ID: $cid"); }
  }
  header("Location: adminpanel.php#categories"); exit;
}

/* Profile settings */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_profile'])) {
  $uname = trim($_POST['p_username'] ?? '');
  $email = trim($_POST['p_email'] ?? '');
  $mobile= trim($_POST['p_mobile'] ?? '');
  $pass  = $_POST['p_password'] ?? '';
  $pic   = $me['profile_pic'] ?? '';

  if (!empty($_FILES['p_avatar']['name'])) {
    $dir = "uploads/profile/";
    if (!is_dir($dir)) { mkdir($dir,0777,true); }
    $fn = time()."_".basename($_FILES['p_avatar']['name']);
    $target = $dir.$fn;
    if (move_uploaded_file($_FILES['p_avatar']['tmp_name'],$target)) {
      $pic = $target;
    } else {
      $error_msg = "Avatar upload failed.";
    }
  }

  if ($uname && $email) {
    if ($pass!=='') {
      $hash = password_hash($pass, PASSWORD_DEFAULT);
      $stmt = $conn->prepare("UPDATE users SET username=?, email=?, mobile=?, password=?, profile_pic=? WHERE id=?");
      $stmt->bind_param("sssssi",$uname,$email,$mobile,$hash,$pic,$_SESSION['user_id']);
    } else {
      $stmt = $conn->prepare("UPDATE users SET username=?, email=?, mobile=?, profile_pic=? WHERE id=?");
      $stmt->bind_param("ssssi",$uname,$email,$mobile,$pic,$_SESSION['user_id']);
    }
    if ($stmt->execute()){ $info_msg="Profile updated."; $stmt->close(); header("Location: adminpanel.php#profile"); exit; }
    else { $error_msg="Profile update failed: ".$stmt->error; $stmt->close(); }
  } else { $error_msg="Username and Email are required."; }
}

/* ==============================
   Fetch data for views
   ============================== */
$total_users    = (int)($conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'] ?? 0);
$total_posts    = (int)($conn->query("SELECT COUNT(*) c FROM posts")->fetch_assoc()['c'] ?? 0);
$total_comments = (int)($conn->query("SELECT COUNT(*) c FROM comments")->fetch_assoc()['c'] ?? 0);
$total_msgs     = (int)($conn->query("SELECT COUNT(*) c FROM contact_messages")->fetch_assoc()['c'] ?? 0);
$total_logs     = (int)($conn->query("SELECT COUNT(*) c FROM activity_logs")->fetch_assoc()['c'] ?? 0);

$postSearch = trim($_GET['q'] ?? '');
if ($postSearch!=='') {
  $like = "%$postSearch%";
  $stmt = $conn->prepare("SELECT p.*, u.username FROM posts p LEFT JOIN users u ON p.author_id=u.id
                          WHERE p.title LIKE ? OR p.description LIKE ? ORDER BY p.created_at DESC");
  $stmt->bind_param("ss",$like,$like); $stmt->execute();
  $posts = $stmt->get_result();
} else {
  $posts = $conn->query("SELECT p.*, u.username FROM posts p LEFT JOIN users u ON p.author_id=u.id ORDER BY p.created_at DESC");
}

$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");

$comments = $conn->query("SELECT c.*, u.username, p.title FROM comments c
                          LEFT JOIN users u ON c.user_id=u.id
                          LEFT JOIN posts p ON c.post_id=p.id
                          ORDER BY c.created_at DESC");

$messages = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC");

$logs = $conn->query("SELECT l.*, u.username FROM activity_logs l 
                      LEFT JOIN users u ON l.user_id=u.id 
                      ORDER BY l.created_at DESC LIMIT 50");

/* Categories with counts */
$categories = $conn->query("
  SELECT c.id, c.name, c.slug,
         (SELECT COUNT(*) FROM posts p WHERE p.category = c.name) as post_count
  FROM categories c ORDER BY c.created_at DESC
");

/* Static pages list from `pages` table */
seed_pages($conn, $STATIC_PAGES); // ensure rows exist
$pages_list = $conn->query("SELECT slug, title, updated_at FROM pages WHERE slug IN ('about','privacy-policy','terms') ORDER BY FIELD(slug,'about','privacy-policy','terms')");
$pages_by_slug = [];
if ($pages_list) { while($r=$pages_list->fetch_assoc()){ $pages_by_slug[$r['slug']]=$r; } }

?>
<!doctype html>
<html lang="en" class="dark">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Admin Panel - Hackers Gurukul Blog</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Newsreader:wght@400;700&display=swap" rel="stylesheet"/>
  <script>
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: { primary: "#1193d4", "background-dark":"#0e1a20" },
          fontFamily: { display: ["Newsreader"] }
        }
      }
    };
  </script>
  <style>
    .sidebar { width: 18rem; }
    @media (min-width: 768px) { main { margin-left: 18rem; } }
    header { height: 64px; }
    td, th { word-break: break-word; }
  </style>
</head>
<body class="bg-background-dark text-slate-200 font-display min-h-screen">

  <!-- Top Navbar (matches homepage) -->
  <header class="bg-slate-900 border-b border-slate-800 fixed top-0 left-0 right-0 z-30">
    <div class="container mx-auto px-4 flex items-center justify-between h-16">
      <a href="homepage.php" class="flex items-center gap-2">
        <svg class="h-6 w-6 text-primary" viewBox="0 0 48 48"><path d="M6 6H42L36 24L42 42H6L12 24L6 6Z" fill="currentColor"/></svg>
        <span class="text-lg font-semibold text-white">Hackers Gurukul Blog</span>
      </a>
      <nav class="hidden md:flex gap-6 text-slate-200">
        <a href="homepage.php" class="hover:text-primary">Home</a>
        <a href="categories.php" class="hover:text-primary">Categories</a>
        <a href="contactpage.php" class="hover:text-primary">Contact</a>
      </nav>
      <div class="flex items-center gap-4">
        <form class="hidden md:flex" method="get" action="#posts">
          <input class="px-3 py-1 rounded-l bg-slate-800" name="q" value="<?php echo e($postSearch); ?>" placeholder="Search posts"/>
          <button class="px-3 py-1 rounded-r bg-primary text-white">Search</button>
        </form>
        <a href="create_post.php" class="px-3 py-1 bg-green-600 text-white rounded-md">+ Create Post</a>
        <a href="user_profile.php" class="block h-9 w-9 rounded-full overflow-hidden border-2 border-primary">
          <img src="<?php echo e($me['profile_pic'] ?: 'uploads/profile/default.png'); ?>" class="h-full w-full object-cover" alt="Profile"/>
        </a>
      </div>
    </div>
  </header>

  <div class="flex pt-16">
    <!-- Sidebar -->
    <aside class="sidebar fixed left-0 top-16 bottom-0 bg-slate-900 border-r border-slate-800 p-6 hidden md:block overflow-auto">
      <h3 class="text-xl font-semibold mb-6 text-slate-100">⚙️ Admin Dashboard</h3>
      <nav class="flex flex-col gap-3 text-slate-200">
        <a href="#analytics"   class="hover:text-primary">📊 Analytics</a>
        <a href="#quickpost"   class="hover:text-primary">⚡ Quick Post</a>
        <a href="#posts"       class="hover:text-primary">📝 Posts</a>
        <a href="#categories"  class="hover:text-primary">🏷️ Categories</a>
        <a href="#users"       class="hover:text-primary">👥 Users</a>
        <a href="#messages"    class="hover:text-primary">📩 Messages</a>
        <a href="#comments"    class="hover:text-primary">💬 Comments</a>
        <a href="#pages"       class="hover:text-primary">📄 Static Pages</a>
        <a href="#profile"     class="hover:text-primary">👤 Profile</a>
        <a href="#logs"        class="hover:text-primary">📜 Logs</a>
        <a href="homepage.php" class="hover:text-primary mt-4">🏠 Back to Site</a>
        <a href="logout.php"   class="hover:text-red-400 mt-2">🔒 Logout</a>
      </nav>
    </aside>

    <!-- Main -->
    <main class="flex-1 p-6">
      <div class="container mx-auto">

        <?php if (!empty($info_msg)): ?><div class="bg-green-600/20 p-3 mb-4 rounded text-green-100"><?php echo e($info_msg); ?></div><?php endif; ?>
        <?php if (!empty($error_msg)): ?><div class="bg-red-600/20 p-3 mb-4 rounded text-red-100"><?php echo e($error_msg); ?></div><?php endif; ?>

        <!-- Analytics -->
        <section id="analytics" class="mb-10">
          <h1 class="text-3xl font-bold mb-4">Admin Panel</h1>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
            <div class="bg-slate-800 p-6 rounded-xl">
              <div class="text-4xl font-bold text-primary"><?php echo $total_users; ?></div>
              <p class="text-slate-400 mt-2">Users</p>
            </div>
            <div class="bg-slate-800 p-6 rounded-xl">
              <div class="text-4xl font-bold text-green-400"><?php echo $total_posts; ?></div>
              <p class="text-slate-400 mt-2">Posts</p>
            </div>
            <div class="bg-slate-800 p-6 rounded-xl">
              <div class="text-4xl font-bold text-yellow-400"><?php echo $total_comments; ?></div>
              <p class="text-slate-400 mt-2">Comments</p>
            </div>
            <div class="bg-slate-800 p-6 rounded-xl">
              <div class="text-4xl font-bold text-blue-400"><?php echo $total_msgs; ?></div>
              <p class="text-slate-400 mt-2">Messages</p>
            </div>
            <div class="bg-slate-800 p-6 rounded-xl">
              <div class="text-4xl font-bold text-red-400"><?php echo $total_logs; ?></div>
              <p class="text-slate-400 mt-2">Logs</p>
            </div>
          </div>
        </section>

        <!-- Quick Post -->
        <section id="quickpost" class="mb-12">
          <div class="bg-slate-900 p-5 rounded-xl border border-slate-800">
            <h2 class="text-2xl font-semibold mb-4">⚡ Quick Add Post</h2>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-3">
              <input type="hidden" name="quick_add_post" value="1"/>
              <input name="q_title" class="px-3 py-2 rounded bg-slate-800" placeholder="Title" required>
              <input name="q_description" class="px-3 py-2 rounded bg-slate-800 md:col-span-2" placeholder="Short Description" required>
              <input name="q_category" class="px-3 py-2 rounded bg-slate-800" placeholder="Category" required>
              <div class="md:col-span-4">
                <button class="px-4 py-2 bg-green-600 rounded text-white">Create & Edit</button>
              </div>
            </form>
          </div>
        </section>

        <!-- Posts -->
        <section id="posts" class="mb-12">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <h2 class="text-2xl font-semibold">Manage Posts</h2>
            <div class="flex gap-3">
              <form method="get" action="#posts" class="flex">
                <input class="px-3 py-2 rounded-l bg-slate-800" name="q" value="<?php echo e($postSearch); ?>" placeholder="Search posts"/>
                <button class="px-4 py-2 rounded-r bg-primary text-white">Search</button>
              </form>
              <a href="create_post.php" class="px-4 py-2 bg-green-600 rounded text-white">+ Create Post</a>
            </div>
          </div>
          <div class="bg-slate-900 rounded-xl overflow-hidden">
            <table class="w-full text-left">
              <thead class="bg-slate-800"><tr><th class="p-3">Title</th><th class="p-3">Author</th><th class="p-3">Created</th><th class="p-3">Actions</th></tr></thead>
              <tbody>
                <?php if ($posts && $posts->num_rows): while($p=$posts->fetch_assoc()): ?>
                  <tr class="border-t border-slate-800">
                    <td class="p-3"><?php echo e($p['title']); ?></td>
                    <td class="p-3"><?php echo e($p['username'] ?? 'Unknown'); ?></td>
                    <td class="p-3"><?php echo e(date("M d, Y H:i", strtotime($p['created_at']))); ?></td>
                    <td class="p-3">
                      <a href="post_edit.php?post_id=<?php echo (int)$p['id']; ?>" class="text-blue-400 hover:underline">Edit</a>
                      <span class="mx-2">|</span>
                      <a href="?delete_post=<?php echo (int)$p['id']; ?>" class="text-red-400 hover:underline" onclick="return confirm('Delete this post?')">Delete</a>
                    </td>
                  </tr>
                <?php endwhile; else: ?>
                  <tr><td class="p-3" colspan="4">No posts found.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>

        <!-- Categories -->
<section id="categories" class="mb-12">
  <h2 class="text-2xl font-semibold mb-4">Manage Categories</h2>
  
  <!-- Add Category -->
  <div class="bg-slate-900 p-5 rounded-xl border border-slate-800 mb-4">
    <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-3">
      <input type="hidden" name="add_category" value="1"/>
      <input name="cat_name" class="px-3 py-2 rounded bg-slate-800 md:col-span-3" placeholder="New category name" required>
      <button class="px-4 py-2 bg-green-600 rounded text-white">Add</button>
    </form>
  </div>

  <!-- Category Table -->
  <div class="bg-slate-900 rounded-xl overflow-hidden">
    <table class="w-full text-left">
      <thead class="bg-slate-800">
        <tr>
          <th class="p-3">Name</th>
          <th class="p-3">Slug</th>
          <th class="p-3">Posts</th>
          <th class="p-3">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        // ✅ Correct query: join by slug instead of name
        $categories = $conn->query("
          SELECT c.id, c.name, c.slug,
                 (SELECT COUNT(*) FROM posts p WHERE p.category = c.slug) AS post_count
          FROM categories c ORDER BY c.created_at DESC
        ");
        
        if ($categories && $categories->num_rows > 0):
          while($c = $categories->fetch_assoc()): ?>
            <tr class="border-t border-slate-800">
              <td class="p-3"><?php echo htmlspecialchars($c['name']); ?></td>
              <td class="p-3"><?php echo htmlspecialchars($c['slug']); ?></td>
              <td class="p-3"><?php echo (int)$c['post_count']; ?></td>
              <td class="p-3">
                <form method="POST" class="inline-flex gap-2 items-center" action="#categories">
                  <input type="hidden" name="rename_category" value="1"/>
                  <input type="hidden" name="cat_id" value="<?php echo (int)$c['id']; ?>"/>
                  <input name="new_name" class="px-2 py-1 rounded bg-slate-800 text-white" placeholder="Rename">
                  <button class="px-3 py-1 bg-blue-600 rounded text-white">Save</button>
                </form>
                <a href="?delete_category=<?php echo (int)$c['id']; ?>#categories" 
                   class="ml-3 text-red-400 hover:underline" 
                   onclick="return confirm('Delete this category?')">Delete</a>
              </td>
            </tr>
        <?php endwhile; else: ?>
          <tr><td class="p-3" colspan="4">No categories found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>


        <!-- Users -->
        <section id="users" class="mb-12">
          <h2 class="text-2xl font-semibold mb-4">Manage Users</h2>
          <?php if ($edit_user_data): ?>
            <form method="POST" action="adminpanel.php#users" class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-3 bg-slate-900 p-4 rounded-xl">
              <input type="hidden" name="id" value="<?php echo (int)$edit_user_data['id']; ?>"/>
              <input type="text"   name="username" value="<?php echo e($edit_user_data['username']); ?>" class="px-3 py-2 rounded bg-slate-800" placeholder="Username"/>
              <input type="email"  name="email"    value="<?php echo e($edit_user_data['email']); ?>"    class="px-3 py-2 rounded bg-slate-800" placeholder="Email"/>
              <input type="text"   name="mobile"   value="<?php echo e($edit_user_data['mobile']); ?>"   class="px-3 py-2 rounded bg-slate-800" placeholder="Mobile"/>
              <input type="password" name="password" placeholder="New Password (leave blank to keep)" class="px-3 py-2 rounded bg-slate-800"/>
              <div class="md:col-span-4">
                <button type="submit" name="update_user" class="px-4 py-2 bg-blue-600 rounded text-white">Update User</button>
                <a href="adminpanel.php#users" class="ml-2 px-4 py-2 bg-gray-600 rounded text-white">Cancel</a>
              </div>
            </form>
          <?php endif; ?>

          <form method="POST" action="adminpanel.php#users" class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-3 bg-slate-900 p-4 rounded-xl">
            <input type="text" name="username" placeholder="Username" class="px-3 py-2 rounded bg-slate-800"/>
            <input type="email" name="email" placeholder="Email" class="px-3 py-2 rounded bg-slate-800"/>
            <input type="text" name="mobile" placeholder="Mobile" class="px-3 py-2 rounded bg-slate-800"/>
            <input type="password" name="password" placeholder="Password" class="px-3 py-2 rounded bg-slate-800"/>
            <div class="md:col-span-4">
              <button type="submit" name="add_user" class="mt-2 px-4 py-2 bg-green-600 rounded text-white">Add User</button>
            </div>
          </form>

          <div class="bg-slate-900 rounded-xl overflow-hidden">
            <table class="w-full text-left">
              <thead class="bg-slate-800"><tr><th class="p-3">Username</th><th class="p-3">Email</th><th class="p-3">Mobile</th><th class="p-3">Role</th><th class="p-3">Joined</th><th class="p-3">Actions</th></tr></thead>
              <tbody>
                <?php if ($users && $users->num_rows): while($u=$users->fetch_assoc()): ?>
                  <tr class="border-t border-slate-800">
                    <td class="p-3"><?php echo e($u['username']); ?></td>
                    <td class="p-3"><?php echo e($u['email']); ?></td>
                    <td class="p-3"><?php echo e($u['mobile']); ?></td>
                    <td class="p-3"><?php echo e(ucfirst($u['role'] ?? 'user')); ?></td>
                    <td class="p-3"><?php echo e(date("M d, Y", strtotime($u['created_at']))); ?></td>
                    <td class="p-3">
                      <a href="?edit_user=<?php echo (int)$u['id']; ?>#users" class="text-blue-400 hover:underline">Edit</a>
                      <?php if (($u['role'] ?? '') === 'user'): ?>
                        <span class="mx-1">|</span><a href="?promote=<?php echo (int)$u['id']; ?>" class="text-green-400 hover:underline" onclick="return confirm('Promote this user?')">Promote</a>
                      <?php elseif (($u['role'] ?? '') === 'admin' && (int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                        <span class="mx-1">|</span><a href="?demote=<?php echo (int)$u['id']; ?>" class="text-yellow-400 hover:underline" onclick="return confirm('Demote this admin?')">Demote</a>
                      <?php endif; ?>
                      <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                        <span class="mx-1">|</span><a href="?delete_user=<?php echo (int)$u['id']; ?>" class="text-red-400 hover:underline" onclick="return confirm('Delete this user?')">Delete</a>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endwhile; else: ?>
                  <tr><td class="p-3" colspan="6">No users found.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>

        <!-- Messages -->
        <section id="messages" class="mb-12">
          <h2 class="text-2xl font-semibold mb-4">Contact Messages</h2>
          <div class="bg-slate-900 rounded-xl overflow-hidden">
            <table class="w-full text-left">
              <thead class="bg-slate-800"><tr><th class="p-3">Name</th><th class="p-3">Email</th><th class="p-3">Subject</th><th class="p-3">Message (preview)</th><th class="p-3">Date</th><th class="p-3">Actions</th></tr></thead>
              <tbody>
                <?php if ($messages && $messages->num_rows): while($m=$messages->fetch_assoc()): ?>
                  <tr class="border-t border-slate-800">
                    <td class="p-3"><?php echo e($m['name']); ?></td>
                    <td class="p-3"><?php echo e($m['email']); ?></td>
                    <td class="p-3"><?php echo e($m['subject']); ?></td>
                    <td class="p-3"><?php echo e(mb_strimwidth($m['message'],0,120,'...')); ?></td>
                    <td class="p-3"><?php echo e(date("M d, Y H:i", strtotime($m['created_at']))); ?></td>
                    <td class="p-3">
                      <a href="mailto:<?php echo e($m['email']); ?>?subject=Re:%20<?php echo rawurlencode($m['subject']); ?>" class="text-blue-400 hover:underline">Reply</a>
                      <span class="mx-1">|</span>
                      <a href="?delete_message=<?php echo (int)$m['id']; ?>" class="text-red-400 hover:underline" onclick="return confirm('Delete this message?')">Delete</a>
                    </td>
                  </tr>
                <?php endwhile; else: ?>
                  <tr><td class="p-3" colspan="6">No contact messages.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>

        <!-- Comments -->
        <section id="comments" class="mb-12">
          <h2 class="text-2xl font-semibold mb-4">Manage Comments</h2>
          <div class="bg-slate-900 rounded-xl overflow-hidden">
            <table class="w-full text-left">
              <thead class="bg-slate-800"><tr><th class="p-3">Comment</th><th class="p-3">User</th><th class="p-3">Post</th><th class="p-3">Date</th><th class="p-3">Actions</th></tr></thead>
              <tbody>
                <?php if ($comments && $comments->num_rows): while($c=$comments->fetch_assoc()): ?>
                  <tr class="border-t border-slate-800">
                    <td class="p-3"><?php echo e($c['comment']); ?></td>
                    <td class="p-3"><?php echo e($c['username'] ?? 'Unknown'); ?></td>
                    <td class="p-3"><?php echo e($c['title'] ?? 'Unknown'); ?></td>
                    <td class="p-3"><?php echo e(date("M d, Y H:i", strtotime($c['created_at']))); ?></td>
                    <td class="p-3"><a href="?delete_comment=<?php echo (int)$c['id']; ?>" class="text-red-400 hover:underline" onclick="return confirm('Delete this comment?')">Delete</a></td>
                  </tr>
                <?php endwhile; else: ?>
                  <tr><td class="p-3" colspan="5">No comments yet.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>

        <!-- Static Pages (Separate Editor) -->
        <section id="pages" class="mb-12">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-2xl font-semibold">Static Pages (About / Privacy / Terms)</h2>
            <a href="adminpanel.php?seed_pages=1#pages" class="px-4 py-2 bg-green-600 rounded text-white" onclick="return confirm('Create default static pages if missing?')">Seed Default Pages</a>
          </div>
          <div class="bg-slate-900 rounded-xl overflow-hidden">
            <table class="w-full text-left">
              <thead class="bg-slate-800"><tr><th class="p-3">Title</th><th class="p-3">Slug</th><th class="p-3">Updated</th><th class="p-3">Actions</th></tr></thead>
              <tbody>
                <?php foreach ($STATIC_PAGES as $slug => $meta):
                  $row = $pages_by_slug[$slug] ?? ['title'=>$meta['title'],'updated_at'=>null];
                  $updated = $row['updated_at'] ? date("M d, Y H:i", strtotime($row['updated_at'])) : '—';
                ?>
                  <tr class="border-t border-slate-800">
                    <td class="p-3"><?php echo e($meta['title']); ?></td>
                    <td class="p-3"><?php echo e($slug); ?></td>
                    <td class="p-3"><?php echo e($updated); ?></td>
                    <td class="p-3 space-x-3">
                      <a class="text-blue-400 hover:underline" href="page_edit.php?page=<?php echo e($slug); ?>">Edit</a>
                      <a class="text-slate-300 hover:underline" href="<?php echo e($meta['view']); ?>" target="_blank">View</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php if (isset($_GET['seeded'])): ?>
            <div class="mt-3 bg-green-600/20 p-3 rounded text-green-100">✅ Static pages are seeded/ready.</div>
          <?php endif; ?>
        </section>

        <!-- Profile -->
        <section id="profile" class="mb-12">
          <h2 class="text-2xl font-semibold mb-4">Profile Settings</h2>
          <div class="bg-slate-900 p-5 rounded-xl border border-slate-800">
            <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <input type="hidden" name="update_profile" value="1"/>
              <div>
                <label class="block mb-1">Username</label>
                <input name="p_username" value="<?php echo e($me['username']); ?>" class="w-full px-3 py-2 rounded bg-slate-800" required>
              </div>
              <div>
                <label class="block mb-1">Email</label>
                <input name="p_email" type="email" value="<?php echo e($me['email']); ?>" class="w-full px-3 py-2 rounded bg-slate-800" required>
              </div>
              <div>
                <label class="block mb-1">Mobile</label>
                <input name="p_mobile" value="<?php echo e($me['mobile']); ?>" class="w-full px-3 py-2 rounded bg-slate-800">
              </div>
              <div>
                <label class="block mb-1">New Password (optional)</label>
                <input name="p_password" type="password" class="w-full px-3 py-2 rounded bg-slate-800" placeholder="Leave blank to keep current">
              </div>
              <div class="md:col-span-2">
                <label class="block mb-1">Avatar</label>
                <div class="flex items-center gap-4">
                  <img src="<?php echo e($me['profile_pic'] ?: 'uploads/profile/default.png'); ?>" class="h-12 w-12 rounded-full border-2 border-primary object-cover" alt="Avatar">
                  <input type="file" name="p_avatar" accept="image/*" class="text-slate-300">
                </div>
              </div>
              <div class="md:col-span-2">
                <button class="px-4 py-2 bg-primary rounded text-white">Save Profile</button>
              </div>
            </form>
          </div>
        </section>

        <!-- Logs -->
        <section id="logs" class="mb-12">
          <h2 class="text-2xl font-semibold mb-4">Activity Logs</h2>
          <div class="bg-slate-900 rounded-xl overflow-hidden">
            <table class="w-full text-left">
              <thead class="bg-slate-800"><tr><th class="p-3">User</th><th class="p-3">Action</th><th class="p-3">Details</th><th class="p-3">Time</th></tr></thead>
              <tbody>
                <?php if ($logs && $logs->num_rows): while($l=$logs->fetch_assoc()): ?>
                  <tr class="border-t border-slate-800">
                    <td class="p-3"><?php echo e($l['username'] ?? 'System'); ?></td>
                    <td class="p-3 text-blue-300"><?php echo e($l['action']); ?></td>
                    <td class="p-3"><?php echo e($l['details']); ?></td>
                    <td class="p-3"><?php echo e(date("M d, Y H:i", strtotime($l['created_at']))); ?></td>
                  </tr>
                <?php endwhile; else: ?>
                  <tr><td class="p-3" colspan="4">No logs available.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>

      </div>
    </main>
  </div>
</body>
</html>
