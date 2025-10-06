<?php
// ✅ Database connection
$conn = new mysqli("localhost", "root", "", "hackers_gurukul_blog");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ✅ Securely fetch privacy-policy page
$slug = 'privacy-policy';
$stmt = $conn->prepare("SELECT title, content FROM pages WHERE slug=? LIMIT 1");
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();
$page = $result->fetch_assoc();
$stmt->close();

if (!$page) {
    die("<div style='padding:20px;color:#fff;background:#1e293b;font-family:sans-serif;'>⚠️ Page not found in database.</div>");
}

// ✅ Format content: allow admin HTML + preserve plain line breaks
$raw_content = $page['content'] ?? '';
$display_content = trim($raw_content) !== '' 
    ? nl2br($raw_content)  // preserves line breaks if admin used plain text
    : "<p class='text-slate-400 italic'>No Privacy Policy content has been added yet. Please update this page in the admin panel.</p>";
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($page['title']); ?> - Hackers Gurukul Blog</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Newsreader:wght@400;700&display=swap" rel="stylesheet">
  <script>
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: { primary: "#1193d4", "background-dark": "#101c22" },
          fontFamily: { display: ["Newsreader"] },
        },
      },
    };
  </script>
</head>
<body class="bg-background-dark text-slate-200 font-display min-h-screen flex flex-col">

<!-- 🔹 Navbar -->
<header class="bg-slate-900 border-b border-slate-800 fixed top-0 left-0 right-0 z-30">
  <div class="container mx-auto px-4 flex items-center justify-between h-16">
    <a href="homepage.php" class="flex items-center gap-2">
      <svg class="h-6 w-6 text-primary" viewBox="0 0 48 48"><path d="M6 6H42L36 24L42 42H6L12 24L6 6Z" fill="currentColor"/></svg>
      <span class="text-lg font-semibold text-white">Hackers Gurukul</span>
    </a>
    <nav class="hidden md:flex gap-6">
      <a href="homepage.php" class="hover:text-primary">Home</a>
      <a href="categories.php" class="hover:text-primary">Categories</a>
      <a href="contactpage.php" class="hover:text-primary">Contact</a>
      <a href="privacy-policy.php" class="text-primary font-semibold">Privacy Policy</a>
    </nav>
  </div>
</header>

<!-- 🔹 Content -->
<main class="pt-20 pb-16 container mx-auto max-w-4xl px-4 flex-grow">
  <h1 class="text-4xl font-bold mb-6 text-primary">
    <?php echo htmlspecialchars($page['title']); ?>
  </h1>
  <div class="prose dark:prose-invert max-w-none leading-relaxed space-y-4">
    <?php echo $display_content; ?>
  </div>
</main>

<!-- 🔹 Footer -->
<?php include 'footer.php'; ?>

</body>
</html>
