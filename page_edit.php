<?php
// ✅ Start session and DB connection
if (session_status() === PHP_SESSION_NONE) session_start();

$conn = new mysqli("localhost", "root", "", "hackers_gurukul_blog");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ✅ Only Admin Access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: homepage.php");
    exit;
}

// ✅ Allowed static pages (must match your DB slug values)
$allowed_pages = [
    "about"           => "About Us",
    "privacy-policy"  => "Privacy Policy",
    "terms"           => "Terms & Conditions"
];

// ✅ Get page key from URL and validate
$page_key = $_GET['page'] ?? '';
if (!array_key_exists($page_key, $allowed_pages)) {
    die("<p style='padding:20px;font-family:sans-serif;color:orange;'>⚠️ Invalid page requested.</p>");
}

$info_msg = $error_msg = "";
$title = $allowed_pages[$page_key];

// ✅ Try to fetch page content
$stmt = $conn->prepare("SELECT content FROM pages WHERE slug=? LIMIT 1");
$stmt->bind_param("s", $page_key);
$stmt->execute();
$result = $stmt->get_result();
$page_row = $result->fetch_assoc();
$current_content = $page_row['content'] ?? '';
$stmt->close();

// ✅ If not exists, seed default content (so the page can be edited easily)
if (!$page_row) {
    $default_content = "## {$title}\n\nThis is the {$title} page. You can edit this content from the admin panel.";
    $stmt = $conn->prepare("INSERT INTO pages (slug, title, content, updated_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("sss", $page_key, $title, $default_content);
    $stmt->execute();
    $stmt->close();
    $current_content = $default_content;
}

// ✅ Handle Update Request
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $new_content = trim($_POST['content']);
    if ($new_content !== '') {
        $stmt = $conn->prepare("
            INSERT INTO pages (slug, title, content, updated_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE content=VALUES(content), updated_at=NOW()
        ");
        $stmt->bind_param("sss", $page_key, $title, $new_content);
        if ($stmt->execute()) {
            $info_msg = "✅ {$title} page updated successfully!";
            $current_content = $new_content;
        } else {
            $error_msg = "❌ Error updating page: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_msg = "⚠️ Content cannot be empty.";
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Edit <?php echo htmlspecialchars($title); ?> - Hackers Gurukul Blog</title>

  <!-- Tailwind CSS + EasyMDE Markdown Editor -->
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Newsreader:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/easymde/dist/easymde.min.css">
  <script src="https://unpkg.com/easymde/dist/easymde.min.js"></script>

  <script>
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: { primary: "#1193d4", "background-dark": "#0e1a20" },
          fontFamily: { display: ["Newsreader"] }
        }
      }
    };
  </script>
</head>
<body class="bg-background-dark text-slate-200 font-display min-h-screen flex flex-col">

<!-- 🔹 Top Navbar -->
<header class="bg-slate-900 border-b border-slate-800">
  <div class="container mx-auto px-4 flex justify-between items-center h-16">
    <a href="adminpanel.php#pages" class="flex items-center gap-2 text-white">
      <svg class="h-6 w-6 text-primary" viewBox="0 0 48 48">
        <path d="M6 6H42L36 24L42 42H6L12 24L6 6Z" fill="currentColor"/>
      </svg>
      <span class="text-lg font-bold">Back to Admin Panel</span>
    </a>
    <a href="homepage.php" class="text-slate-400 hover:text-primary">🏠 View Site</a>
  </div>
</header>

<!-- 🔹 Editor Section -->
<main class="flex-grow container mx-auto px-4 py-8 max-w-4xl">
  <div class="bg-slate-900 p-6 rounded-xl shadow border border-slate-800">
    <h1 class="text-3xl font-bold mb-6">✏️ Edit <?php echo htmlspecialchars($title); ?></h1>

    <?php if($info_msg): ?>
      <div class="bg-green-600/20 text-green-200 p-3 mb-4 rounded"><?php echo $info_msg; ?></div>
    <?php endif; ?>
    <?php if($error_msg): ?>
      <div class="bg-red-600/20 text-red-200 p-3 mb-4 rounded"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <textarea name="content" id="content"><?php echo htmlspecialchars($current_content); ?></textarea>
      <div class="flex justify-between">
        <button type="submit" class="px-6 py-2 bg-primary rounded-lg text-white">💾 Save Changes</button>
        <a href="adminpanel.php#pages" class="px-6 py-2 bg-gray-600 rounded-lg text-white">⬅ Back</a>
      </div>
    </form>
  </div>
</main>

<script>
  const easyMDE = new EasyMDE({
    element: document.getElementById("content"),
    spellChecker: false,
    autofocus: true,
    placeholder: "Start writing your page content here...",
    minHeight: "300px"
  });
</script>

</body>
</html>
