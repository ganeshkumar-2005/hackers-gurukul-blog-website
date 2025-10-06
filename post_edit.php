<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$conn = new mysqli("localhost", "root", "", "hackers_gurukul_blog");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: homepage.php");
    exit;
}

$post_id = intval($_GET['post_id'] ?? 0);

// ✅ Fetch post safely
$stmt = $conn->prepare("SELECT * FROM posts WHERE id=? LIMIT 1");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$post) {
    die("⚠️ Post not found.");
}

$info_msg = $error_msg = "";

// ✅ Update post
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $category = trim($_POST['category']);
    $description = trim($_POST['description']);
    $content = trim($_POST['content']);

    // Safe default for image
    $image_path = $post['image'] ?? null;

    // ✅ Handle new image upload
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "uploads/posts/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $filename = time() . "_" . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $filename;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            // delete old image if exists
            if (!empty($post['image']) && file_exists($post['image'])) {
                @unlink($post['image']);
            }
            $image_path = $target_file;
        } else {
            $error_msg = "❌ Failed to upload image.";
        }
    }

    if ($title && $category && $description && $content) {
        $stmt = $conn->prepare("UPDATE posts SET title=?, category=?, description=?, content=?, image=? WHERE id=?");
        $stmt->bind_param("sssssi", $title, $category, $description, $content, $image_path, $post_id);
        if ($stmt->execute()) {
            $info_msg = "✅ Post updated successfully.";
            // re-fetch updated post
            $stmt2 = $conn->prepare("SELECT * FROM posts WHERE id=? LIMIT 1");
            $stmt2->bind_param("i", $post_id);
            $stmt2->execute();
            $post = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();
        } else {
            $error_msg = "❌ Error updating post: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_msg = "⚠️ All fields except image are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Edit Post - Hackers Gurukul Blog</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Newsreader:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/easymde/dist/easymde.min.css">
<script src="https://unpkg.com/easymde/dist/easymde.min.js"></script>
<script>
tailwind.config = {
  darkMode: "class",
  theme: {
    extend: {
      colors: {
        primary: "#1193d4",
        "background-dark": "#101c22",
      },
      fontFamily: { display: ["Newsreader"] },
    },
  },
};
</script>
</head>
<body class="bg-background-dark text-slate-200 font-display min-h-screen flex flex-col">

<!-- 🔹 Navbar -->
<header class="bg-slate-900 border-b border-slate-800">
  <div class="container mx-auto px-4 flex justify-between items-center h-16">
    <a href="homepage.php" class="flex items-center gap-2">
      <svg class="h-6 w-6 text-primary" viewBox="0 0 48 48" fill="none"><path d="M6 6H42L36 24L42 42H6L12 24L6 6Z" fill="currentColor"/></svg>
      <span class="text-lg font-bold text-white">Hackers Gurukul Blog</span>
    </a>
    <a href="adminpanel.php#posts" class="px-4 py-2 bg-gray-600 text-white rounded-md">⬅ Back</a>
  </div>
</header>

<!-- 🔹 Edit Post Form -->
<main class="flex-grow container mx-auto px-4 py-8 max-w-5xl grid grid-cols-1 lg:grid-cols-2 gap-10">
  
  <!-- Form -->
  <div>
    <div class="bg-slate-900 p-6 rounded-2xl shadow border border-slate-800">
      <h1 class="text-3xl font-bold mb-6">✏️ Edit Post</h1>

      <?php if($info_msg): ?><div class="bg-green-600/20 p-3 mb-4 rounded"><?php echo $info_msg; ?></div><?php endif; ?>
      <?php if($error_msg): ?><div class="bg-red-600/20 p-3 mb-4 rounded"><?php echo $error_msg; ?></div><?php endif; ?>

      <form method="POST" enctype="multipart/form-data" class="space-y-5">
        <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($post['title']); ?>" 
               class="w-full px-3 py-2 rounded bg-slate-800 text-slate-200" required>

        <input type="text" name="category" id="category" value="<?php echo htmlspecialchars($post['category']); ?>" 
               class="w-full px-3 py-2 rounded bg-slate-800 text-slate-200" required>

        <textarea name="description" id="description" rows="3" 
                  class="w-full px-3 py-2 rounded bg-slate-800 text-slate-200" required><?php echo htmlspecialchars($post['description']); ?></textarea>

        <!-- Markdown Editor -->
        <textarea name="content" id="content"><?php echo htmlspecialchars($post['content']); ?></textarea>

        <!-- Image Upload -->
        <div>
          <label class="block mb-1 font-semibold">Image</label>
          <input type="file" name="image" id="image" class="block w-full text-slate-300">
          <?php if (!empty($post['image'])): ?>
            <div class="mt-3">
              <p class="text-sm text-slate-400 mb-1">Current:</p>
              <img src="<?php echo htmlspecialchars($post['image']); ?>" class="max-h-40 rounded border border-slate-700" id="currentPreview">
            </div>
          <?php endif; ?>
          <div id="newPreview" class="mt-3 hidden">
            <p class="text-sm text-slate-400 mb-1">New Preview:</p>
            <img class="max-h-40 rounded border border-slate-700" />
          </div>
        </div>

        <div class="flex justify-end gap-3">
          <button type="submit" class="px-6 py-2 bg-primary rounded-lg text-white">💾 Save Changes</button>
          <a href="adminpanel.php#posts" class="px-6 py-2 bg-gray-600 rounded-lg text-white">Cancel</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Live Preview -->
  <div>
    <h2 class="text-2xl font-bold mb-4">🔍 Live Preview</h2>
    <div id="previewCard" class="bg-slate-900 p-6 rounded-lg shadow space-y-3 border border-slate-800">
      <h3 id="previewTitle" class="text-xl font-bold"><?php echo htmlspecialchars($post['title']); ?></h3>
      <p id="previewCategory" class="text-sm text-slate-400">Category: <?php echo htmlspecialchars($post['category']); ?></p>
      <p id="previewDescription" class="text-slate-300"><?php echo htmlspecialchars($post['description']); ?></p>
      <div id="previewImage">
        <?php if (!empty($post['image'])): ?>
          <img src="<?php echo htmlspecialchars($post['image']); ?>" class="rounded-lg"/>
        <?php endif; ?>
      </div>
      <div id="previewContent" class="prose prose-invert"><?php echo nl2br(htmlspecialchars($post['content'])); ?></div>
    </div>
  </div>

</main>

<!-- 🔹 JS -->
<script>
let easyMDE = new EasyMDE({ element: document.getElementById("content"), spellChecker: false });

function updatePreview() {
  document.getElementById("previewTitle").innerText = document.getElementById("title").value;
  document.getElementById("previewCategory").innerText = "Category: " + document.getElementById("category").value;
  document.getElementById("previewDescription").innerText = document.getElementById("description").value;
  document.getElementById("previewContent").innerHTML = easyMDE.markdown(easyMDE.value());
}

["title","description","category"].forEach(id => {
  document.getElementById(id).addEventListener("input", updatePreview);
});
easyMDE.codemirror.on("change", updatePreview);

document.getElementById("image").addEventListener("change", function(event){
  const file = event.target.files[0];
  const previewBox = document.getElementById("newPreview");
  const img = previewBox.querySelector("img");
  if(file){
    const reader = new FileReader();
    reader.onload = e => { img.src = e.target.result; previewBox.classList.remove("hidden"); };
    reader.readAsDataURL(file);
  }
});
</script>

</body>
</html>
